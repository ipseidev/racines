#!/usr/bin/env bash
#
# scripts/dev.sh — démarrage de l'environnement de développement (laradev).
#
# Sans option : Sail, les logs et Vite en mode développement, sur
# http://localhost:8001. C'est le mode de tous les jours.
#
# Avec --tunnel : deux tunnels Cloudflare publics — un pour l'application, un
# pour MinIO — et le `.env` est corrigé pour les employer. C'est le mode des
# **tests sur téléphone réel** et des **rappels de fournisseurs** (Gladia,
# Stripe, Resend), qui ont besoin d'une URL joignable depuis Internet.
#
# Pourquoi deux tunnels et pas un. La page d'enregistrement envoie l'audio
# **directement** au stockage, par une URL présignée : le navigateur du
# téléphone doit donc joindre MinIO, pas seulement l'application. Un seul
# tunnel donnerait une page qui s'ouvre et un envoi qui échoue — l'erreur la
# plus pénible à diagnostiquer de tout ce projet.
#
# En mode tunnel, les assets sont **compilés** au lieu d'être servis par Vite :
# le serveur de développement écoute sur localhost:5176, qu'un téléphone ne
# peut pas joindre.
#
# Les valeurs d'origine du `.env` sont sauvegardées dans .laradev.state et
# restaurées par larakill. Laisser LINKS_DOMAIN pointer sur un tunnel mort
# casserait tous les liens à jeton au test suivant.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${SCRIPT_DIR}/.."

SAIL="vendor/bin/sail"
ENV_FILE=".env"
STATE_FILE=".laradev.state"
APP_LOG="/tmp/cloudflared-racines-app.log"
MINIO_LOG="/tmp/cloudflared-racines-minio.log"

USE_TUNNEL=0
WITH_CLAMAV=0
FRESH=0

for arg in "$@"; do
    case "${arg}" in
        --tunnel)  USE_TUNNEL=1 ;;
        --clamav)  WITH_CLAMAV=1 ;;
        --fresh)   FRESH=1 ;;
        -h|--help)
            sed -n '2,25p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
            printf '\nOptions :\n'
            printf '  --tunnel   Deux tunnels Cloudflare + .env corrigé (téléphones, webhooks)\n'
            printf '  --clamav   Démarre aussi l’antivirus (premier lancement : ~3 min)\n'
            printf '  --fresh    Recrée la base et rejoue les seeders\n'
            exit 0 ;;
        *) printf '[dev] option inconnue : %s (voir --help)\n' "${arg}" >&2; exit 2 ;;
    esac
done

log()  { printf "\033[1;36m[dev]\033[0m %s\n" "$*"; }
warn() { printf "\033[1;33m[dev]\033[0m %s\n" "$*" >&2; }
die()  { printf "\033[1;31m[dev]\033[0m %s\n" "$*" >&2; exit 1; }

cleanup() {
    # Jamais de nouvel échec dans un gestionnaire de sortie : on fait au mieux.
    "${SCRIPT_DIR}/kill.sh" --quiet || true
}
trap cleanup EXIT INT TERM

# --- 1. Préalables -----------------------------------------------------------

[[ -x "${SAIL}" ]] || die "${SAIL} absent — lancez d'abord « composer install »."
[[ -f "${ENV_FILE}" ]] || die "${ENV_FILE} absent."
docker info >/dev/null 2>&1 || die "Docker ne répond pas — démarrez Docker Desktop."

if [[ ${USE_TUNNEL} -eq 1 ]]; then
    command -v cloudflared >/dev/null || die "cloudflared absent (brew install cloudflared)."
fi

env_value() {
    grep -E "^${1}=" "${ENV_FILE}" | head -1 | cut -d= -f2- | tr -d '"' || true
}

env_set() {
    local key="$1" value="$2"

    if grep -qE "^${key}=" "${ENV_FILE}"; then
        # Le séparateur | évite d'échapper les slashs des URL.
        sed -i '' "s|^${key}=.*|${key}=${value}|" "${ENV_FILE}"
    else
        printf '\n%s=%s\n' "${key}" "${value}" >> "${ENV_FILE}"
    fi
}

APP_PORT="$(env_value APP_PORT)"
: "${APP_PORT:=8001}"

# --- 2. Ports libres avant de démarrer --------------------------------------

log "Libération des ports laissés par une session précédente…"
"${SCRIPT_DIR}/kill.sh" --quiet --skip-host || warn "Nettoyage partiel — on continue."

# --- 3. Sail ----------------------------------------------------------------

SERVICES=(laravel.test pgsql redis mailpit minio horizon scheduler)

if [[ ${WITH_CLAMAV} -eq 1 ]]; then
    SERVICES+=(clamav)
    log "L'antivirus est inclus : le premier démarrage télécharge un demi-gigaoctet de signatures."
else
    # Sans le démon, le scan **refuse** tout fichier : c'est volontaire, un
    # fichier non scanné n'est pas un fichier propre. On bascule donc le
    # scanner simulé, et on le dit.
    env_set ANTIVIRUS_SCANNER fake
fi

log "Démarrage des conteneurs : ${SERVICES[*]}"
"${SAIL}" up -d "${SERVICES[@]}"

log "Attente de l'application sur le port ${APP_PORT}…"
for _ in {1..60}; do
    if curl -sf -o /dev/null "http://localhost:${APP_PORT}/up" 2>/dev/null; then
        break
    fi
    sleep 1
done

curl -sf -o /dev/null "http://localhost:${APP_PORT}/up" 2>/dev/null \
    || warn "L'application ne répond pas encore sur /up — elle finit peut-être de démarrer."

# --- 4. Tunnels --------------------------------------------------------------

APP_TUNNEL=""
MINIO_TUNNEL=""

start_tunnel() {
    local port="$1" logfile="$2" label="$3" url=""

    : > "${logfile}"
    cloudflared tunnel --url "http://localhost:${port}" --no-autoupdate >"${logfile}" 2>&1 &

    for _ in {1..40}; do
        url="$(grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' "${logfile}" 2>/dev/null | head -1 || true)"
        [[ -n "${url}" ]] && break
        sleep 1
    done

    [[ -n "${url}" ]] || die "Le tunnel ${label} n'est pas monté en 40 s (voir ${logfile})."

    printf '%s' "${url}"
}

if [[ ${USE_TUNNEL} -eq 1 ]]; then
    # On garde les valeurs d'origine **avant** de toucher au .env : larakill
    # les remettra, et un LINKS_DOMAIN resté sur un tunnel mort casserait
    # tous les liens à jeton au test suivant.
    {
        printf 'APP_URL=%s\n' "$(env_value APP_URL)"
        printf 'LINKS_DOMAIN=%s\n' "$(env_value LINKS_DOMAIN)"
        printf 'R2_PUBLIC_ENDPOINT=%s\n' "$(env_value R2_PUBLIC_ENDPOINT)"
        printf 'ANTIVIRUS_SCANNER=%s\n' "$(env_value ANTIVIRUS_SCANNER)"
    } > "${STATE_FILE}"

    log "Ouverture du tunnel de l'application…"
    APP_TUNNEL="$(start_tunnel "${APP_PORT}" "${APP_LOG}" application)"

    log "Ouverture du tunnel du stockage…"
    MINIO_TUNNEL="$(start_tunnel 9001 "${MINIO_LOG}" stockage)"

    env_set APP_URL "${APP_TUNNEL}"
    # Le domaine des liens : sans lui, /r/…, /l/… et /i/… répondent 404 sur le
    # tunnel, parce que leurs routes sont contraintes à un domaine.
    env_set LINKS_DOMAIN "${APP_TUNNEL#https://}"
    # L'adresse **vue par le navigateur** du téléphone pour les envois
    # présignés. C'est la raison du second tunnel.
    env_set R2_PUBLIC_ENDPOINT "${MINIO_TUNNEL}"

    log "Compilation des assets (un téléphone ne peut pas joindre Vite en local)…"
    "${SAIL}" npm run build >/dev/null
fi

"${SAIL}" artisan config:clear >/dev/null
log "Cache de configuration vidé."

# --- 5. Base de données ------------------------------------------------------

if [[ ${FRESH} -eq 1 ]]; then
    log "Recréation de la base et des données de démonstration…"
    "${SAIL}" artisan migrate:fresh --seed
else
    "${SAIL}" artisan migrate --force >/dev/null
fi

# --- 6. Le bandeau -----------------------------------------------------------

C_BORDER="\033[1;32m"
C_LABEL="\033[0;90m"
C_VALUE="\033[1;36m"
C_ACCENT="\033[1;33m"
C_RESET="\033[0m"
BW="═════════════════════════════════════════════════════════════════════════════"

line() { printf "${C_BORDER}║${C_RESET}  ${C_LABEL}%-12s${C_RESET} →  ${C_VALUE}%-58s${C_RESET} ${C_BORDER}║${C_RESET}\n" "$1" "$2"; }

printf "\n${C_BORDER}╔${BW}╗${C_RESET}\n"
printf "${C_BORDER}║${C_RESET}  📖  ${C_ACCENT}%-68s${C_RESET} ${C_BORDER}║${C_RESET}\n" "Environnement prêt"
printf "${C_BORDER}╠${BW}╣${C_RESET}\n"
line "Application" "http://localhost:${APP_PORT}"
line "Back-office" "http://localhost:${APP_PORT}/admin"
line "Courriels" "http://localhost:8027"
line "Stockage" "http://localhost:8901"
line "Horizon" "http://localhost:${APP_PORT}/horizon"

if [[ ${USE_TUNNEL} -eq 1 ]]; then
    printf "${C_BORDER}╟${BW}╢${C_RESET}\n"
    line "Tunnel app" "${APP_TUNNEL}"
    line "Tunnel stockage" "${MINIO_TUNNEL}"
    line "Assets" "compilés (pas de rechargement à chaud)"
fi

printf "${C_BORDER}╟${BW}╢${C_RESET}\n"
line "Arrêt" "Ctrl+C, ou larakill depuis un autre terminal"
printf "${C_BORDER}╚${BW}╝${C_RESET}\n\n"

# --- 7. Ce qui tourne au premier plan ---------------------------------------

if [[ ${USE_TUNNEL} -eq 1 ]]; then
    # Vite ne sert à rien ici : les assets sont compilés. On garde les logs,
    # qui sont l'outil de diagnostic d'un test sur téléphone.
    log "Journaux en direct (Ctrl+C pour tout arrêter)…"
    "${SAIL}" artisan pail --timeout=0
else
    npx --yes concurrently \
        -c "#a3e635,#fdba74" \
        --names "vite,logs" \
        --kill-others \
        --prefix "[{name}]" \
        "${SAIL} npm run dev" \
        "${SAIL} artisan pail --timeout=0"
fi
