#!/usr/bin/env bash
#
# scripts/kill.sh — arrête ce que scripts/dev.sh a démarré (larakill).
#
# Dans cet ordre :
#   1. Les tunnels Cloudflare, côté hôte.
#   2. Le superviseur « concurrently », côté hôte.
#   3. Les processus longs dans le conteneur : pail, vite (+ esbuild), serve.
#   4. La restauration du `.env` si dev.sh l'avait corrigé pour un tunnel.
#   5. L'attente que le port de Vite soit réellement libre — Docker ne
#      propage pas les signaux de façon fiable, et la fermeture d'une socket
#      TCP peut traîner au-delà d'un simple sleep.
#
# Les conteneurs Sail restent debout : « sail stop » les arrête, et on ne
# veut pas éteindre Postgres à chaque redémarrage de Vite.
#
# Options :
#   --quiet       n'affiche que les avertissements
#   --skip-host   ne touche pas aux tunnels ni au superviseur (dev.sh s'en
#                 charge lui-même au démarrage)
#   --keep-env    ne restaure pas le .env (utile si on relance un tunnel)

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${SCRIPT_DIR}/.."

SAIL="vendor/bin/sail"
ENV_FILE=".env"
STATE_FILE=".laradev.state"

QUIET=0
SKIP_HOST=0
KEEP_ENV=0

for arg in "$@"; do
    case "${arg}" in
        --quiet) QUIET=1 ;;
        --skip-host) SKIP_HOST=1 ;;
        --keep-env) KEEP_ENV=1 ;;
        *) printf "[kill] option inconnue : %s\n" "${arg}" >&2; exit 2 ;;
    esac
done

log()  { [[ ${QUIET} -eq 1 ]] || printf "\033[1;36m[kill]\033[0m %s\n" "$*"; }
warn() { printf "\033[1;33m[kill]\033[0m %s\n" "$*" >&2; }

VITE_PORT="$(grep -E '^VITE_PORT=' "${ENV_FILE}" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"' || true)"
: "${VITE_PORT:=5173}"

# --- 1. Côté hôte ------------------------------------------------------------

if [[ ${SKIP_HOST} -eq 0 ]]; then
    if pgrep -f "cloudflared tunnel --url http://localhost" >/dev/null 2>&1; then
        log "Fermeture des tunnels Cloudflare…"
        pkill -f "cloudflared tunnel --url http://localhost" 2>/dev/null || true
    else
        log "Aucun tunnel Cloudflare en cours."
    fi

    if pgrep -f "concurrently.*npm run dev" >/dev/null 2>&1; then
        log "Arrêt du superviseur…"
        pkill -f "concurrently.*npm run dev" 2>/dev/null || true
    fi
fi

# --- 2. Restauration du .env -------------------------------------------------

if [[ ${KEEP_ENV} -eq 0 && -f "${STATE_FILE}" ]]; then
    log "Restauration des valeurs d'origine du .env…"

    while IFS='=' read -r key value; do
        [[ -z "${key}" ]] && continue

        if grep -qE "^${key}=" "${ENV_FILE}"; then
            sed -i '' "s|^${key}=.*|${key}=${value}|" "${ENV_FILE}"
        fi
    done < "${STATE_FILE}"

    rm -f "${STATE_FILE}"

    # Sans ce vidage, la configuration en cache garderait l'URL d'un tunnel
    # mort et tous les liens à jeton répondraient 404.
    [[ -x "${SAIL}" ]] && "${SAIL}" artisan config:clear >/dev/null 2>&1 || true
fi

# --- 3. Dans le conteneur ----------------------------------------------------

if [[ ! -x "${SAIL}" ]] || ! "${SAIL}" ps --services --filter status=running 2>/dev/null | grep -q '^laravel.test$'; then
    log "Conteneur applicatif arrêté — rien à nettoyer dedans."
    log "Terminé."
    exit 0
fi

log "Arrêt des processus longs du conteneur (pail, vite, serve)…"
# IMPORTANT : le script passe par l'entrée standard (bash -s) et non par
# « bash -c '…' ». `pkill -f` parcourt toutes les lignes de commande, et un
# `bash -c` contenant ces motifs se reconnaîtrait lui-même — il se tuerait
# avant d'avoir fini le nettoyage.
"${SAIL}" exec -T laravel.test bash -s <<'EOSCRIPT'
    pkill -9 -f "artisan pail"           2>/dev/null || true
    pkill -9 -f "artisan serve"          2>/dev/null || true
    pkill -9 -f "node_modules/.bin/vite" 2>/dev/null || true
    pkill -9 -f "^sh -c vite$"           2>/dev/null || true
    pkill -9 -f "vite-plus.*dev"         2>/dev/null || true
    pkill -9 -f "esbuild.*--service"     2>/dev/null || true
    exit 0
EOSCRIPT

# --- 4. Attendre que le port soit vraiment libre -----------------------------
#
# La fermeture d'une socket TCP traîne parfois au-delà d'un sleep, et un
# EADDRINUSE de Vite au démarrage suivant fait tomber tout le reste avec lui.
# On lit /proc/net/tcp directement : `fuser <port>/tcp` renvoie vide dans ce
# conteneur même quand une socket est clairement en écoute.

HEX_PORT="$(printf '%04X' "${VITE_PORT}")"
i=0

while [[ ${i} -lt 10 ]]; do
    blocked="$("${SAIL}" exec -T laravel.test bash -s "${HEX_PORT}" <<'EOSCRIPT' 2>/dev/null || true
awk -v p="$1" '$4 == "0A" { split($2, a, ":"); if (a[2] == p) print p }' \
    /proc/net/tcp /proc/net/tcp6 2>/dev/null | sort -u | paste -sd, -
EOSCRIPT
)"

    if [[ -z "${blocked}" ]]; then
        log "Port ${VITE_PORT} libre."
        log "Terminé."
        exit 0
    fi

    sleep 0.5
    ((i++))
done

warn "Le port ${VITE_PORT} est encore occupé après le nettoyage."
warn "Piste : vendor/bin/sail restart"
exit 1
