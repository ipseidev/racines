<?php

declare(strict_types=1);

namespace App\Features;

use App\Settings\PilotSettings;
use Illuminate\Http\Request;

/**
 * Lequel des deux prix de prévente ce visiteur voit.
 *
 * H3 se mesure ici : 99 € ou 129 €, et on ne saura lequel porte qu'en
 * comparant. La portée est un **cookie anonyme** parce que l'affectation doit
 * précéder le compte : quelqu'un qui découvre le produit n'a pas encore de
 * compte, et le prix ne peut pas changer entre sa première visite et son
 * achat.
 *
 * L'affectation est un hachage du cookie, donc **stable** : la même personne
 * revoit le même prix dix visites plus tard. Un prix qui bouge d'une visite à
 * l'autre n'est pas une expérience, c'est une raison de ne pas acheter.
 */
final class PreventePrice
{
    public string $name = 'prevente-price';

    public const COOKIE = 'pv';

    public const COOKIE_DAYS = 90;

    /**
     * @return int Le prix en centimes.
     */
    public function resolve(string $identifier): int
    {
        $prices = self::prices();

        // Hachage plutôt que tirage au sort : rejouable, réparti, et sans
        // état à stocker.
        $bucket = hexdec(substr(hash('sha256', $identifier), 0, 8)) % count($prices);

        return $prices[$bucket];
    }

    /**
     * Le prix vu par ce visiteur, d'après son cookie.
     */
    public static function forRequest(Request $request): int
    {
        $identifier = $request->cookie(self::COOKIE);

        if (! is_string($identifier) || $identifier === '') {
            // Sans cookie, on rend le premier prix : c'est le cas d'un
            // robot ou d'un navigateur qui refuse les cookies, et il ne doit
            // pas fausser la répartition.
            return self::prices()[0];
        }

        return (new self)->resolve($identifier);
    }

    /**
     * @return list<int>
     */
    public static function prices(): array
    {
        $prices = array_values(array_map(
            static fn (mixed $price): int => (int) $price,
            app(PilotSettings::class)->prevente_prices_cents,
        ));

        return $prices === [] ? [9_900] : $prices;
    }
}
