<?php

declare(strict_types=1);

use App\Enums\AddressForm;
use App\Enums\AnswerType;
use App\Enums\Cadence;
use App\Enums\Channel;
use App\Enums\CohortPhase;
use App\Enums\ConsentChannel;
use App\Enums\ConsentKind;
use App\Enums\ConsentStatus;
use App\Enums\DeletionRequestedBy;
use App\Enums\Offer;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\PromptSlot;
use App\Enums\QuestionTheme;
use App\Enums\ShareDecision;
use App\Enums\StoryVisibility;
use App\Enums\UserRole;
use App\Enums\ValidatedVia;
use App\Enums\ValidationVariant;
use App\Support\Database\EnumCheck;
use Illuminate\Support\Facades\Lang;

/** @return list<class-string<BackedEnum>> */
function domainEnums(): array
{
    return [
        AddressForm::class,
        AnswerType::class,
        Cadence::class,
        Channel::class,
        CohortPhase::class,
        ConsentChannel::class,
        ConsentKind::class,
        ConsentStatus::class,
        DeletionRequestedBy::class,
        Offer::class,
        ProjectMemberRole::class,
        ProjectStatus::class,
        PromptSlot::class,
        QuestionTheme::class,
        ShareDecision::class,
        StoryVisibility::class,
        UserRole::class,
        ValidatedVia::class,
        ValidationVariant::class,
    ];
}

it('n’expose que des énumérations adossées à une chaîne', function (): void {
    foreach (domainEnums() as $enum) {
        expect((new ReflectionEnum($enum))->getBackingType()?->getName())
            ->toBe('string', "{$enum} doit être adossée à une chaîne.");
    }
});

it('donne à chaque valeur un libellé traduit en français', function (): void {
    foreach (domainEnums() as $enum) {
        foreach ($enum::cases() as $case) {
            /** @var object{label: callable} $case */
            $key = $case->label();

            expect(Lang::has($key))->toBeTrue("La clé de traduction {$key} est absente de lang/fr.");
        }
    }
});

it('reprend les dix thèmes du corpus de questions', function (): void {
    expect(EnumCheck::of(QuestionTheme::class))->toBe([
        'childhood', 'family_origins', 'youth', 'work', 'love',
        'places', 'joys', 'hardships', 'beliefs_values', 'legacy',
    ]);
});

it('reprend les huit consentements du dossier 04 §2', function (): void {
    expect(EnumCheck::of(ConsentKind::class))->toBe([
        'voice_recording', 'transcription', 'ai_rendering', 'family_sharing',
        'sensitive_categories', 'phone_call_recording', 'photo_rights',
        'post_mortem_directives',
    ]);
});
