<?php

declare(strict_types=1);

namespace App\Health;

use App\Services\Storage\MediaStorage;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Throwable;

/**
 * Le stockage des médias répond-il ?
 *
 * Le contrôle qui compte le plus après la base : sans R2, un narrateur peut
 * encore ouvrir sa page et parler, mais **rien de ce qu'il dit n'est
 * conservé**. Le produit continue de paraître normal jusqu'à la confirmation,
 * qui n'arrive jamais — et c'est la panne la plus coûteuse du système, parce
 * qu'on ne redemande pas à quelqu'un de quatre-vingts ans de tout raconter
 * une seconde fois.
 *
 * On interroge un **objet sentinelle** plutôt que de lister le bucket : une
 * liste réussit sur des droits en lecture seule, alors que la question posée
 * est « pouvons-nous encore écrire et relire ici ». La sentinelle est écrite
 * au premier passage, puis relue à chaque fois.
 */
final class R2ReachableCheck extends Check
{
    private const SENTINELLE = 'health/sentinel.txt';

    public function run(): Result
    {
        $result = Result::make()->meta(['key' => self::SENTINELLE]);
        $storage = app(MediaStorage::class);

        try {
            $depart = microtime(true);

            try {
                $storage->head(self::SENTINELLE);
            } catch (Throwable) {
                // Premier passage, ou sentinelle effacée : on la repose.
                $storage->put(self::SENTINELLE, 'ok', 'text/plain');
                $storage->head(self::SENTINELLE);
            }

            $ms = (int) round((microtime(true) - $depart) * 1000);

            return $ms > 3_000
                ? $result->warning("Le stockage répond en {$ms} ms.")->shortSummary("{$ms} ms")
                : $result->ok()->shortSummary("{$ms} ms");
        } catch (Throwable $exception) {
            return $result->failed('Stockage des médias injoignable : '.$exception->getMessage());
        }
    }
}
