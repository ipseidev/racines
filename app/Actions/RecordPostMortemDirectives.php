<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\PostMortemWish;
use App\Models\Narrator;
use App\Models\PostMortemDirective;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Ce que le narrateur veut qu'il advienne de ses histoires après sa mort.
 *
 * Facultatif, et toujours réversible : la dernière directive exprimée
 * remplace la précédente, l'index unique sur `narrator_id` s'en assure. On ne
 * garde pas l'historique des volontés — garder qu'une personne a d'abord voulu
 * tout supprimer puis changé d'avis n'aide personne, et pourrait servir contre
 * elle.
 *
 * Le référent est stocké **masqué et haché** : on doit pouvoir vérifier
 * qu'une personne qui se présente est bien celle désignée, sans conserver le
 * carnet d'adresses d'une famille en deuil.
 */
final readonly class RecordPostMortemDirectives
{
    public function __construct(private RecordConsent $consents) {}

    /**
     * @param  array{ip?: string|null, user_agent?: string|null}  $context
     */
    public function handle(
        Project $project,
        Narrator $narrator,
        PostMortemWish $wishes,
        ?string $referentName = null,
        ?string $referentContact = null,
        array $context = [],
    ): PostMortemDirective {
        return DB::transaction(function () use (
            $project,
            $narrator,
            $wishes,
            $referentName,
            $referentContact,
            $context,
        ): PostMortemDirective {
            // Le consentement d'abord : une directive sans consentement
            // journalisé n'a aucune valeur, et la colonne n'est pas nullable.
            $consent = $this->consents->handle(
                $narrator,
                $project,
                ConsentKind::PostMortemDirectives,
                ConsentChannel::Web,
                null,
                $context,
            );

            $directive = PostMortemDirective::query()
                ->where('narrator_id', $narrator->id)
                ->first() ?? new PostMortemDirective;

            $directive->fill([
                'wishes' => $wishes,
                'referent_name' => $referentName,
                'referent_contact_masked' => self::mask($referentContact),
                'referent_contact_hash' => self::hash($referentContact),
                'recorded_at' => now(),
            ]);

            $directive->project()->associate($project);
            $directive->narrator()->associate($narrator);
            $directive->consent()->associate($consent);
            $directive->save();

            return $directive;
        });
    }

    /**
     * De quoi reconnaître le référent sans le lire.
     *
     * Assez pour que le narrateur se relise — « ju•••@exemple.fr » — et pas
     * assez pour écrire à quelqu'un.
     */
    public static function mask(?string $contact): ?string
    {
        if ($contact === null || trim($contact) === '') {
            return null;
        }

        $contact = trim($contact);

        if (str_contains($contact, '@')) {
            [$name, $domain] = array_pad(explode('@', $contact, 2), 2, '');

            return mb_substr($name, 0, 2).'•••@'.$domain;
        }

        return mb_substr($contact, 0, 4).'•• •• •• '.mb_substr($contact, -2);
    }

    public static function hash(?string $contact): ?string
    {
        if ($contact === null || trim($contact) === '') {
            return null;
        }

        // Normalisé avant hachage : « Jean@Exemple.FR » et « jean@exemple.fr »
        // sont la même personne, et une vérification qui l'ignore échoue au
        // pire moment.
        return hash('sha256', mb_strtolower(trim($contact)));
    }
}
