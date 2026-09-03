import { createHmac } from 'node:crypto';

/**
 * Un code TOTP, calculé dans le test.
 *
 * La double authentification du back-office est obligatoire depuis le bloc 11.
 * Un test bout en bout doit donc savoir produire un code — sinon la seule
 * façon de le faire passer serait de désactiver la garde pour les tests, et
 * une garde désactivée en test est une garde qu'on ne teste pas.
 *
 * Trente lignes suffisent : RFC 6238 avec SHA-1, six chiffres, fenêtre de
 * trente secondes. Le secret est celui semé par `E2ELinksSeeder`.
 */
const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function decodeBase32(secret: string): Buffer {
    const clean = secret.replace(/=+$/, '').toUpperCase();
    let bits = 0;
    let value = 0;
    const bytes: number[] = [];

    for (const character of clean) {
        const index = ALPHABET.indexOf(character);

        if (index === -1) {
            continue;
        }

        value = (value << 5) | index;
        bits += 5;

        if (bits >= 8) {
            bits -= 8;
            bytes.push((value >>> bits) & 0xff);
        }
    }

    return Buffer.from(bytes);
}

export function totp(secret: string, atSeconds = Date.now() / 1000): string {
    const counter = Math.floor(atSeconds / 30);
    const message = Buffer.alloc(8);
    message.writeUInt32BE(Math.floor(counter / 2 ** 32), 0);
    message.writeUInt32BE(counter % 2 ** 32, 4);

    const digest = createHmac('sha1', decodeBase32(secret))
        .update(message)
        .digest();
    const offset = digest[digest.length - 1] & 0x0f;

    const binary =
        ((digest[offset] & 0x7f) << 24) |
        ((digest[offset + 1] & 0xff) << 16) |
        ((digest[offset + 2] & 0xff) << 8) |
        (digest[offset + 3] & 0xff);

    return String(binary % 1_000_000).padStart(6, '0');
}

/** Le secret semé pour le compte d'administration du bout en bout. */
export const E2E_TOTP_SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';
