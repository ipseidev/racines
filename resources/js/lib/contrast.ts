/**
 * Rapport de contraste WCAG 2.2, miroir de App\Support\Contrast côté serveur.
 * Sert à prévenir dans l'interface avant que le serveur ne refuse.
 */
const AA_NORMAL_TEXT = 4.5;

function channels(hex: string): [number, number, number] {
    let clean = hex.trim().replace(/^#/, '');

    if (clean.length === 3) {
        clean = clean
            .split('')
            .map((character) => character + character)
            .join('');
    }

    if (!/^[0-9a-fA-F]{6}$/.test(clean)) {
        throw new Error(`Couleur hexadécimale invalide : ${hex}`);
    }

    return [
        parseInt(clean.slice(0, 2), 16),
        parseInt(clean.slice(2, 4), 16),
        parseInt(clean.slice(4, 6), 16),
    ];
}

function linearize(channel: number): number {
    const value = channel / 255;

    return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
}

function luminance(hex: string): number {
    const [red, green, blue] = channels(hex);

    return (
        0.2126 * linearize(red) +
        0.7152 * linearize(green) +
        0.0722 * linearize(blue)
    );
}

export function contrastRatio(first: string, second: string): number {
    const a = luminance(first);
    const b = luminance(second);
    const lighter = Math.max(a, b);
    const darker = Math.min(a, b);

    return Math.round(((lighter + 0.05) / (darker + 0.05)) * 100) / 100;
}

export function isReadable(
    foreground: string,
    background: string,
    threshold: number = AA_NORMAL_TEXT,
): boolean {
    return contrastRatio(foreground, background) >= threshold;
}
