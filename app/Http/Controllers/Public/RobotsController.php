<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use Illuminate\Http\Response;

/**
 * `robots.txt`, servi par une route et non par un fichier (T-225).
 *
 * Le protocole exige une **adresse absolue** pour le plan de site, et un
 * fichier statique ne connaît pas le domaine : il aurait fallu l'y écrire en
 * dur, donc le voir mentir en préproduction. La route la construit.
 *
 * Rien n'est interdit à l'exploration, et c'est un choix : les pages qui ne
 * doivent pas être indexées le disent elles-mêmes en `noindex` — le tunnel, le
 * remerciement, le témoin du test. Les interdire ici serait contre-productif,
 * un robot qui ne peut pas lire la page ne peut pas lire son `noindex`, et il
 * indexe alors l'adresse nue. Les pages à jeton vivent sur un autre domaine et
 * portent l'en-tête `X-Robots-Tag`.
 */
final class RobotsController
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow:',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
