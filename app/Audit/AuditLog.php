<?php

declare(strict_types=1);

namespace App\Audit;

use App\Enums\ActorContext;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Le journal d'audit : append-only, et chaîné.
 *
 * Le dossier 04 §12 exige une « journalisation inviolable de toutes les
 * actions support, **lecture comprise** ». Trois mécanismes s'y emploient, et
 * aucun ne suffit seul :
 *
 *  1. Un **trigger** Postgres refuse `UPDATE` et `DELETE`. Il protège de
 *     l'erreur et du script maladroit, mais pas de qui détient les droits de
 *     désactiver un trigger.
 *  2. Une **chaîne d'empreintes** : chaque ligne porte celle de la
 *     précédente. Effacer une ligne demande de recalculer tout ce qui suit —
 *     et `audit:verify` le dirait.
 *  3. La chaîne commence à une **racine connue** et non à `null`. Sans elle,
 *     supprimer les cent premières lignes serait indistinguable d'un journal
 *     qui commence à la cent-unième.
 *
 * Écrit en `DB::table` et non par un modèle Eloquent, délibérément : un modèle
 * a des événements, des observateurs, un `save()` qu'on peut appeler deux
 * fois. Ici on veut une insertion, une seule, et rien autour.
 */
final class AuditLog
{
    /**
     * La racine de la chaîne.
     *
     * Une valeur littérale et non un hachage de quelque chose : elle n'a pas
     * à être secrète, elle a à être **stable**. `audit:verify` la connaît, et
     * une chaîne qui ne commence pas par elle est une chaîne tronquée. Elle
     * fait exactement soixante-quatre caractères, comme une empreinte, pour
     * tenir dans la même colonne.
     */
    public const GENESIS = 'genesis:00000000000000000000000000000000000000000000000000000000';

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array $payload = [],
        ?Project $project = null,
        ?string $ip = null,
    ): void {
        $actor = Auth::user();
        $occurredAt = CarbonImmutable::now();

        $row = [
            'occurred_at' => $occurredAt->format('Y-m-d H:i:sP'),
            'actor_user_id' => $actor?->id,
            'actor_role' => self::roleOf($actor),
            'actor_context' => self::context()->value,
            'action' => $action,
            'subject_type' => $subject === null ? null : $subject::class,
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
            'project_id' => ($project ?? self::projectOf($subject))?->id,
            'ip_hash' => self::hashIp($ip),
            'payload' => json_encode(Redactor::scrub($payload)),
        ];

        /*
         * Le verrou, et la raison pour laquelle il n'est pas négociable :
         * `previous_hash` se lit sur la dernière ligne écrite. Deux
         * transactions concurrentes liraient la même, et produiraient deux
         * lignes partageant le même parent — une fourche silencieuse que
         * `audit:verify` ne signalerait qu'au prochain passage.
         *
         * Le verrou porte sur une ligne dédiée de `audit_chain_head` plutôt
         * que sur `audit_logs` : verrouiller la dernière ligne d'une table en
         * append-only ne dit rien à celui qui insère juste après.
         */
        DB::transaction(function () use ($row): void {
            $previous = (string) DB::table('audit_chain_head')
                ->lockForUpdate()
                ->where('id', 1)
                ->value('hash');

            $row['previous_hash'] = $previous === '' ? self::GENESIS : $previous;
            $row['hash'] = self::hash($row);

            DB::table('audit_logs')->insert($row);

            DB::table('audit_chain_head')->where('id', 1)->update(['hash' => $row['hash']]);
        });
    }

    /**
     * L'empreinte d'une ligne.
     *
     * La formule est publique et vit ici seule : `audit:verify` l'appelle,
     * et deux implémentations divergentes rendraient la vérification inutile.
     *
     * @param  array<string, mixed>  $row
     */
    public static function hash(array $row): string
    {
        return hash('sha256', implode('|', [
            trim((string) $row['previous_hash']),
            // Normalisé, jamais pris tel quel : Postgres rend l'horodatage
            // dans son propre format textuel, qui n'est pas celui qu'on a
            // inséré. Hacher la chaîne brute rendait toute ligne relue
            // incohérente avec elle-même.
            self::normalizeDate((string) $row['occurred_at']),
            (string) $row['action'],
            (string) ($row['subject_type'] ?? ''),
            (string) ($row['subject_id'] ?? ''),
            // Canonique, jamais le texte brut : Postgres stocke le `jsonb`
            // sous sa propre forme — clés réordonnées, espaces normalisés —
            // et hacher ce qu'on a inséré rendait toute ligne relue
            // incohérente avec elle-même.
            self::canonicalPayload($row['payload'] ?? null),
        ]));
    }

    /**
     * Le contenu sous une forme unique : clés triées, sans espace.
     *
     * @param  array<array-key, mixed>|string|null  $payload
     */
    public static function canonicalPayload(array|string|null $payload): string
    {
        $decoded = is_string($payload) ? json_decode($payload, true) : $payload;

        if (! is_array($decoded)) {
            return '{}';
        }

        self::sortDeep($decoded);

        return (string) json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    private static function sortDeep(array &$values): void
    {
        foreach ($values as &$value) {
            if (is_array($value)) {
                self::sortDeep($value);
            }
        }

        unset($value);

        // Les listes gardent leur ordre : `['a', 'b']` et `['b', 'a']` ne
        // disent pas la même chose. Seules les clés d'objet sont triées.
        if (! array_is_list($values)) {
            ksort($values);
        }
    }

    /**
     * L'horodatage sous une forme unique, à la seconde.
     *
     * À la seconde et non à la microseconde : la précision rendue par le
     * pilote varie, et une empreinte ne doit pas dépendre de la version du
     * pilote qui a relu la ligne.
     */
    public static function normalizeDate(string $value): string
    {
        return CarbonImmutable::parse($value)->utc()->format('Y-m-d\TH:i:s\Z');
    }

    public static function hashIp(?string $ip): ?string
    {
        // Même règle que pour le contexte : une route résolue signifie qu'on
        // répond à une requête, et c'est la seule situation où une adresse IP
        // veut dire quelque chose.
        $ip ??= RouteFacade::current() === null ? null : Request::ip();

        return $ip === null || $ip === '' ? null : hash('sha256', $ip);
    }

    /**
     * Le contexte de l'action.
     *
     * On regarde si une **route** est résolue, et non `runningInConsole()` :
     * sous PHPUnit, ce dernier rend vrai même pendant qu'une requête de test
     * est traitée, et toute lecture depuis le panneau se serait retrouvée
     * inscrite comme venant de la console. Une route résolue signifie qu'on
     * répond à une requête ; son absence signifie une commande, une file ou
     * le planificateur.
     *
     * `system` quand personne n'est connecté hors requête : une purge
     * programmée n'a pas d'auteur humain, et prétendre le contraire serait
     * pire que de l'admettre.
     */
    public static function context(): ActorContext
    {
        if (RouteFacade::current() === null) {
            return Auth::check() ? ActorContext::Cli : ActorContext::System;
        }

        return str_starts_with(Request::path(), 'admin')
            ? ActorContext::Filament
            : ActorContext::Web;
    }

    private static function roleOf(?object $actor): string
    {
        return $actor instanceof User ? $actor->role->value : 'system';
    }

    /**
     * Le projet du sujet, deviné plutôt que demandé.
     *
     * L'audit se lit projet par projet — « qui a touché à cette famille ? ».
     * Le déduire ici évite l'oubli au trentième appel.
     */
    private static function projectOf(?Model $subject): ?Project
    {
        if ($subject === null) {
            return null;
        }

        if ($subject instanceof Project) {
            return $subject;
        }

        $related = $subject->getAttribute('project');

        if ($related instanceof Project) {
            return $related;
        }

        $id = $subject->getAttribute('project_id');

        return is_string($id) ? Project::query()->find($id) : null;
    }
}
