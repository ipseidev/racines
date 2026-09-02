/**
 * Reconnaissance sommaire de la plateforme, pour l'écran d'aide au micro.
 *
 * On ne s'en sert pas pour décider d'un comportement — jamais de branche
 * fonctionnelle sur l'agent utilisateur — mais pour montrer la bonne capture
 * d'écran : « Réglages › Safari › Micro » n'aide personne sur Android.
 */
export type Platform = 'ios' | 'android' | 'samsung' | 'other';

export function detectPlatform(
    userAgent = globalThis.navigator?.userAgent ?? '',
): Platform {
    const agent = userAgent.toLowerCase();

    if (agent.includes('samsungbrowser')) {
        return 'samsung';
    }

    if (
        /iphone|ipad|ipod/.test(agent) ||
        (agent.includes('macintosh') && 'ontouchend' in globalThis.document)
    ) {
        return 'ios';
    }

    if (agent.includes('android')) {
        return 'android';
    }

    return 'other';
}
