<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Audit\ChainVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * L'exercice de restauration, trimestriel (doc 04 §11).
 *
 * Le dossier promet un RTO de 72 heures. Une promesse de restauration qu'on
 * n'a jamais exécutée n'est pas une promesse, c'est un espoir — et la manière
 * dont une restauration échoue est toujours la même : l'archive existe, elle
 * se télécharge, et il manque une extension, un rôle, ou la moitié des
 * tables. On ne le découvre qu'en le faisant.
 *
 * Ce que la commande fait, dans cet ordre :
 *
 *  1. **Un dump de la base courante**, avec l'outil qui prendra celui de
 *     production — `pg_dump`, pas une abstraction.
 *  2. **Une base neuve**, `drill_<horodatage>`, et la restauration dedans.
 *  3. **Les vérifications** : la chaîne d'audit est-elle intacte après le
 *     voyage, et les comptes concordent-ils table par table ? Une
 *     restauration qui rend 90 % des histoires est un échec silencieux.
 *  4. **Un rapport daté** dans `docs/runbooks/drills/`, avec la durée
 *     mesurée — c'est elle qu'on compare au RTO, pas une cible recopiée.
 *  5. **La base d'exercice effacée.** Oubliée, elle grossit chaque trimestre
 *     et finit par remplir le disque du serveur qu'elle devait protéger.
 *
 * Ce qu'elle ne fait **jamais** : écrire dans la base de l'application. Un
 * exercice qui peut abîmer ce qu'il vérifie est lui-même l'incident.
 */
final class RestoreDrill extends Command
{
    protected $signature = 'restore:drill
        {--dump= : un fichier .sql à restaurer ; un dump de la base courante sinon}
        {--keep : garder la base d’exercice pour l’inspecter}
        {--rapports= : où écrire le rapport ; docs/runbooks/drills sinon}';

    protected $description = 'Restaure une sauvegarde dans une base jetable et vérifie qu’elle est complète';

    /** Les tables dont l'écart se verrait le jour d'un vrai incident. */
    private const COMPTES = ['projects', 'stories', 'recordings', 'transcripts', 'consents', 'audit_logs'];

    public function handle(): int
    {
        if (app()->isProduction()) {
            // L'exercice se joue sur staging. En production, il prendrait un
            // dump complet de la base vivante aux heures de service, et
            // créerait une seconde base sur le même serveur managé.
            $this->components->error('L’exercice de restauration se joue sur staging, jamais en production.');

            return self::FAILURE;
        }

        $depart = microtime(true);
        $base = 'drill_'.now()->format('Ymd_His').'_'.Str::lower(Str::random(4));
        $dump = (string) ($this->option('dump') ?: storage_path('app/drills/'.$base.'.sql'));
        $ownDump = $this->option('dump') === null;

        $avant = $this->compter(null);

        try {
            if ($ownDump) {
                File::ensureDirectoryExists(dirname($dump));
                $this->components->task('Dump de la base courante', fn (): bool => $this->dump($dump));
            }

            $this->components->task("Création de la base {$base}", fn (): bool => $this->creer($base));
            $this->components->task('Restauration', fn (): bool => $this->restaurer($base, $dump));

            $apres = $this->compter($base);
            $chaine = $this->verifierChaine($base);

            $duree = microtime(true) - $depart;
            $chemin = $this->rapport($base, $avant, $apres, $chaine, $duree, $dump);

            $this->newLine();
            $this->components->twoColumnDetail('Durée mesurée', $this->duree($duree));
            $this->components->twoColumnDetail('Chaîne d’audit', $chaine ? '<fg=green>intacte</>' : '<fg=red>rompue</>');
            $this->components->twoColumnDetail('Rapport', $chemin);

            $ecarts = $this->ecarts($avant, $apres);

            if ($ecarts !== [] || ! $chaine) {
                $this->newLine();
                $this->components->error('Exercice en échec : '.($ecarts === [] ? 'chaîne d’audit rompue' : implode(', ', $ecarts)));

                return self::FAILURE;
            }

            $this->components->info('Restauration vérifiée. Le rapport est daté et versionné.');

            return self::SUCCESS;
        } finally {
            if (! $this->option('keep')) {
                $this->supprimer($base);

                if ($ownDump) {
                    File::delete($dump);
                }
            }
        }
    }

    private function dump(string $chemin): bool
    {
        return $this->psqlProcess(['pg_dump', '--no-owner', '--no-privileges', '--clean', '--if-exists',
            '--dbname='.$this->url((string) config('database.connections.pgsql.database')),
            '--file='.$chemin,
        ]);
    }

    private function creer(string $base): bool
    {
        // `CREATE DATABASE` ne passe pas dans une transaction, et la
        // connexion de test en ouvre une : on parle donc au serveur par
        // `psql`, comme le fera la personne qui restaure pour de vrai.
        return $this->psqlProcess(['psql', '--dbname='.$this->url('postgres'), '-v', 'ON_ERROR_STOP=1',
            '-c', 'CREATE DATABASE "'.$base.'"',
        ]);
    }

    private function restaurer(string $base, string $dump): bool
    {
        return $this->psqlProcess(['psql', '--dbname='.$this->url($base), '-v', 'ON_ERROR_STOP=1', '-f', $dump]);
    }

    private function supprimer(string $base): void
    {
        $this->psqlProcess(['psql', '--dbname='.$this->url('postgres'), '-c',
            'DROP DATABASE IF EXISTS "'.$base.'" WITH (FORCE)',
        ]);
    }

    /**
     * Les comptes, table par table.
     *
     * Lus par `psql` des **deux** côtés, y compris pour la base source.
     * Passer par Eloquent pour l'une et par `psql` pour l'autre compare deux
     * points de vue différents : la connexion de l'application peut être au
     * milieu d'une transaction, et le dump ne voit que ce qui est validé —
     * l'exercice conclurait alors à une perte de données là où il n'y a
     * qu'une écriture en cours.
     *
     * @return array<string, int>
     */
    private function compter(?string $base): array
    {
        $base ??= (string) config('database.connections.pgsql.database');
        $comptes = [];

        foreach (self::COMPTES as $table) {
            $valeur = $this->scalaire($base, 'select count(*) from "'.$table.'"');

            // Une table absente compte pour -1 : l'écart saute aux yeux dans
            // le rapport au lieu de passer pour un zéro légitime.
            $comptes[$table] = is_numeric($valeur) ? (int) $valeur : -1;
        }

        return $comptes;
    }

    /**
     * La chaîne d'audit tient-elle après le voyage ?
     *
     * C'est la vérification qui compte le plus : le journal est rendu
     * inaltérable par un trigger, et une restauration qui casserait le
     * chaînage rendrait inutilisable la seule preuve de ce qui s'est passé.
     *
     * Elle passe par **le même vérificateur que `audit:verify`**, sur une
     * connexion pointée vers la base d'exercice. La première version posait
     * la question en SQL et se contentait de comparer les maillons : dix fois
     * plus faible, elle aurait laissé passer une ligne réécrite — soit
     * exactement ce que le journal existe pour rendre impossible.
     */
    private function verifierChaine(string $base): bool
    {
        config(['database.connections.drill' => array_merge(
            (array) config('database.connections.pgsql'),
            ['database' => $base],
        )]);

        DB::purge('drill');

        try {
            return app(ChainVerifier::class)->breaks('drill') === [];
        } catch (Throwable) {
            // Une base restaurée sans table d'audit n'est pas « intacte » :
            // c'est le pire des cas, et il ne doit pas passer pour un succès.
            return false;
        } finally {
            DB::purge('drill');
        }
    }

    private function scalaire(string $base, string $sql): string
    {
        $process = new Process(['psql', '--dbname='.$this->url($base), '-t', '-A', '-c', $sql]);
        $process->run();

        return trim($process->getOutput());
    }

    /** @param list<string> $commande */
    private function psqlProcess(array $commande): bool
    {
        $process = new Process($commande, timeout: 600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->components->error(trim($process->getErrorOutput()) ?: 'commande en échec');
        }

        return $process->isSuccessful();
    }

    private function url(string $base): string
    {
        $c = config('database.connections.pgsql');

        return sprintf(
            'postgresql://%s:%s@%s:%s/%s',
            rawurlencode((string) $c['username']),
            rawurlencode((string) $c['password']),
            $c['host'],
            $c['port'],
            $base,
        );
    }

    /**
     * @param  array<string, int>  $avant
     * @param  array<string, int>  $apres
     * @return list<string>
     */
    private function ecarts(array $avant, array $apres): array
    {
        $ecarts = [];

        foreach ($avant as $table => $compte) {
            if (($apres[$table] ?? -1) !== $compte) {
                $ecarts[] = sprintf('%s : %d attendus, %d restaurés', $table, $compte, $apres[$table] ?? -1);
            }
        }

        return $ecarts;
    }

    /**
     * @param  array<string, int>  $avant
     * @param  array<string, int>  $apres
     */
    private function rapport(string $base, array $avant, array $apres, bool $chaine, float $duree, string $dump): string
    {
        $ecarts = $this->ecarts($avant, $apres);
        /*
         * Les rapports sont **versionnés** : un exercice trimestriel dont la
         * trace vit dans un dossier temporaire ne prouve rien six mois plus
         * tard, et c'est la preuve que le dossier réclame (doc 04 §11). Le
         * chemin reste réglable pour que la suite de tests n'y dépose pas les
         * siens.
         */
        $dossier = (string) ($this->option('rapports') ?: base_path('docs/runbooks/drills'));
        $chemin = $dossier.'/'.now()->format('Y-m-d_His').'.md';
        File::ensureDirectoryExists(dirname($chemin));

        $lignes = [
            '# Exercice de restauration — '.now()->translatedFormat('j F Y à H:i'),
            '',
            '| | |',
            '|---|---|',
            '| Base d’exercice | `'.$base.'` |',
            '| Source | `'.basename($dump).'` |',
            '| **Durée mesurée** | **'.$this->duree($duree).'** |',
            '| RTO visé (doc 04 §11) | 72 h |',
            '| Chaîne d’audit | '.($chaine ? 'intacte' : '**ROMPUE**').' |',
            '| Verdict | '.($ecarts === [] && $chaine ? '✅ réussi' : '❌ échec').' |',
            '',
            '## Comptes comparés',
            '',
            '| Table | Avant | Après | |',
            '|---|---:|---:|---|',
        ];

        foreach ($avant as $table => $compte) {
            $restaure = $apres[$table] ?? -1;
            $lignes[] = sprintf('| `%s` | %d | %d | %s |', $table, $compte, $restaure, $restaure === $compte ? '✅' : '❌');
        }

        $lignes[] = '';
        $lignes[] = $ecarts === []
            ? 'Aucun écart. La restauration rend exactement ce qui a été sauvegardé.'
            : 'Écarts : '.implode(' ; ', $ecarts).'.';
        $lignes[] = '';
        $lignes[] = '> La durée mesurée est celle du dump, de la création de la base et de la '
            .'restauration, sur une base de la taille du moment. Elle ne couvre pas le '
            .'téléchargement de l’archive depuis le stockage objet ni la décision humaine : '
            .'la procédure complète est dans `docs/runbooks/restauration.md`.';

        File::put($chemin, implode("\n", $lignes)."\n");

        return $chemin;
    }

    private function duree(float $secondes): string
    {
        return $secondes < 60
            ? number_format($secondes, 1, ',', ' ').' s'
            : floor($secondes / 60).' min '.number_format(fmod($secondes, 60), 0).' s';
    }
}
