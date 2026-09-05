import AxeBuilder from '@axe-core/playwright';
import type { Page } from '@playwright/test';

/*
 * Le scan d'accessibilité, et l'attente qui doit le précéder.
 *
 * Six fichiers en avaient chacun leur copie, et aucune n'attendait. Or les
 * pages entrent en fondu : axe mesuré en plein fondu rapporte des contrastes
 * que personne ne voit jamais — l'opacité intermédiaire délave le texte **et**
 * son fond, et le rapport tombe sous le seuil. C'est ainsi qu'on a cru à un
 * défaut de la palette de marque : le blanc sur terracotta était mesuré à
 * 3,78:1 alors qu'il vaut 5,70:1 une fois la page posée (T-161).
 *
 * Le pire n'est pas le faux positif, c'est son intermittence : la garde passe
 * ou échoue selon la charge de la machine, et on finit par la croire cassée
 * plutôt que la lire.
 */
const DEFAULT_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

/**
 * Attend que la page soit posée.
 *
 * Les animations **sans fin** sont exclues, sinon l'attente ne finirait
 * jamais : le halo de l'enregistrement, l'onde de la page d'accueil et les
 * indicateurs de chargement tournent tant que la page est ouverte, et c'est
 * voulu.
 */
export async function settled(page: Page): Promise<void> {
    await page.waitForFunction(() =>
        document.getAnimations().every((animation) => {
            const iterations = animation.effect?.getTiming().iterations ?? 1;

            return iterations === Infinity || animation.playState !== 'running';
        }),
    );
}

/** Les violations qui bloquent : `serious` et `critical`, page posée. */
export async function blockingViolations(
    page: Page,
    tags: string[] = DEFAULT_TAGS,
) {
    await settled(page);

    const results = await new AxeBuilder({ page }).withTags(tags).analyze();

    return results.violations.filter((violation) =>
        ['serious', 'critical'].includes(violation.impact ?? ''),
    );
}
