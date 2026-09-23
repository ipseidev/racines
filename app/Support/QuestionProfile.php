<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BuyerFocus;
use App\Enums\BuyerRelation;
use App\Enums\GrammaticalGender;
use App\Enums\QuestionCondition;
use App\Models\Project;
use App\Models\ProjectProfile;
use App\Models\Question;

/**
 * Ce que le profil d'un projet change aux questions qu'on lui envoie.
 *
 * La règle tient en une phrase : **seul un « non » explicite retire une
 * question**. Un profil vide, un tunnel passé, une case laissée blanche : on
 * ne sait pas, et la question part comme elle partait avant qu'il y ait des
 * profils. C'est le cas par défaut, et il ne doit rien coûter à personne.
 *
 * Les questions sur l'acheteur sont l'exception inverse : elles demandent un
 * « oui » — il a choisi « sur moi », on connaît son prénom, et c'est son
 * enfant, son petit-enfant ou un autre proche. Ni le conjoint (le bloc Couple
 * parle déjà de lui), ni qui raconte sa propre histoire.
 */
final readonly class QuestionProfile
{
    /** Les liens qui reçoivent des questions sur l'acheteur. */
    private const BUYER_RELATIONS = [BuyerRelation::Child, BuyerRelation::Grandchild, BuyerRelation::Other];

    private function __construct(private ?ProjectProfile $profile) {}

    public static function of(Project $project): self
    {
        return new self($project->profile);
    }

    public function allows(Question $question): bool
    {
        foreach ($question->conditions as $condition) {
            if (! $this->meets($condition)) {
                return false;
            }
        }

        return array_intersect($question->sensitive_topics, $this->profile->avoided_topics ?? []) === [];
    }

    public function favors(Question $question): bool
    {
        return in_array($question->theme->value, $this->profile->favored_themes ?? [], true);
    }

    public function isAboutBuyer(Question $question): bool
    {
        return in_array(QuestionCondition::AboutBuyer->value, $question->conditions, true);
    }

    /** La famille a-t-elle demandé, et permis, qu'on parle de l'acheteur ? */
    public function talksAboutBuyer(): bool
    {
        return $this->profile !== null
            && $this->profile->buyer_focus === BuyerFocus::Buyer
            && in_array($this->profile->buyer_relation, self::BUYER_RELATIONS, true)
            && trim((string) $this->profile->buyer_first_name) !== '';
    }

    public function buyerName(): ?string
    {
        return $this->talksAboutBuyer() ? trim((string) $this->profile?->buyer_first_name) : null;
    }

    public function buyerGender(): ?GrammaticalGender
    {
        return $this->profile?->buyer_gender;
    }

    private function meets(string $condition): bool
    {
        return match ($condition) {
            QuestionCondition::AboutBuyer->value => $this->talksAboutBuyer(),
            QuestionCondition::BuyerIsChild->value => $this->profile?->buyer_relation === BuyerRelation::Child,
            QuestionCondition::BuyerIsGrandchild->value => $this->profile?->buyer_relation === BuyerRelation::Grandchild,
            // Une condition de vie : seul un « non » dit la retire.
            default => ($this->profile?->facts[$condition] ?? null) !== false,
        };
    }
}
