<?php

declare(strict_types=1);

namespace App\Providers;

use App\Health\AuditChainCheck;
use App\Health\ClamavCheck;
use App\Health\R2ReachableCheck;
use App\Health\ReplicationLagCheck;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

/**
 * Les contrôles de santé, déclarés **ici** et non dans `config/health.php`.
 *
 * Deux raisons, et la seconde est la vraie.
 *
 * La première : `config:cache` sérialise la configuration en PHP littéral, et
 * un objet ne se sérialise pas. Un tableau de contrôles dans le fichier de
 * configuration fait échouer le déploiement (T-207).
 *
 * La seconde : le paquet **ne lit jamais** `config('health.checks')`. Cette
 * clé n'existe pas chez lui. Elle avait l'air d'une déclaration, elle n'était
 * qu'un tableau que personne n'ouvrait — `health:check` répondait « All
 * done! » sans exécuter un seul contrôle, et `/health` renvoyait un `200`
 * avec une liste de résultats vide. Le registre, c'est `Health::checks()`, et
 * lui seul.
 *
 * Les quatre derniers contrôles sont propres au produit et disent chacun une
 * promesse du dossier : le stockage porte les voix, le journal d'audit porte
 * la preuve, l'antivirus garde la porte des photos, et la réplication tient
 * l'engagement de non-perte. Les génériques disent seulement que la machine
 * tourne.
 */
final class HealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Health::checks([
            DatabaseCheck::new(),
            CacheCheck::new(),
            RedisCheck::new(),
            HorizonCheck::new(),
            // Le planificateur bat toutes les minutes ; dix minutes de
            // silence veulent dire que les relances, les envois et les
            // sauvegardes se sont arrêtés sans que rien ne le dise.
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(10),
            UsedDiskSpaceCheck::new()->warnWhenUsedSpaceIsAbovePercentage(80),

            R2ReachableCheck::new(),
            AuditChainCheck::new(),
            ClamavCheck::new(),
            ReplicationLagCheck::new(),
        ]);
    }
}
