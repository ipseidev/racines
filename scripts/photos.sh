#!/usr/bin/env bash
#
# Régénère les WebP de la page d'accueil depuis leurs sources.
#
# L'échelle des largeurs doit rester celle de resources/js/lib/photo.ts, et
# celle du préchargement du héros dans resources/views/app.blade.php : un
# barreau annoncé sans fichier derrière est un 404 dans le srcSet, un fichier
# sans barreau est du poids mort. Un test Vitest et un test Pest tiennent les
# deux bouts.
#
# La qualité 78 reproduit les fichiers d'origine à quelques centaines d'octets
# près. `-sharp_yuv` évite le bavement des rouges dans les réductions fortes,
# `-m 6` cherche plus longtemps au profit du poids.
#
# Usage : scripts/photos.sh
set -euo pipefail

cd "$(dirname "$0")/.."

readonly QUALITY=78
readonly WIDTHS=(400 550 700 900 1100)

if ! command -v cwebp >/dev/null; then
    echo "cwebp est absent : brew install webp" >&2
    exit 1
fi

# Chaque source avec sa largeur native. Le héros et les étapes viennent de
# photographies en 1400, la capture de relecture d'un PNG portrait en 780.
readonly SOURCES=(
    "public/img/landing/hero.jpg:1400"
    "public/img/landing/etape-1.jpg:1400"
    "public/img/landing/etape-2.jpg:1400"
    "public/img/landing/etape-3.jpg:1400"
    "public/img/landing/etape-4.jpg:1400"
    "public/img/landing/livre.jpg:1400"
    "public/img/landing/relecture.png:780"
)

for entry in "${SOURCES[@]}"; do
    source="${entry%:*}"
    native="${entry#*:}"
    base="${source%.*}"

    cwebp -quiet -q "$QUALITY" -m 6 -sharp_yuv "$source" -o "$base.webp"
    printf '%-40s %6d o\n' "$(basename "$base.webp")" "$(wc -c <"$base.webp")"

    for width in "${WIDTHS[@]}"; do
        if [ "$width" -ge "$native" ]; then
            continue
        fi

        cwebp -quiet -q "$QUALITY" -m 6 -sharp_yuv -resize "$width" 0 \
            "$source" -o "$base-$width.webp"
        printf '%-40s %6d o\n' "$(basename "$base-$width.webp")" \
            "$(wc -c <"$base-$width.webp")"
    done
done
