<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ConsentKind;
use App\Models\ConsentText;
use Illuminate\Database\Seeder;

/**
 * Version 1.0 de chaque texte de consentement.
 *
 * Les textes sont provisoires et marqués comme tels : les formulations
 * définitives, opposables, arrivent au bloc 10 après relecture juridique. Ce
 * qui compte dès maintenant, c'est qu'une ligne de `consents` puisse pointer
 * une version précise de ce qui a été lu.
 */
final class ConsentTextSeeder extends Seeder
{
    /** @var array<string, string> */
    private const BODIES = [
        ConsentKind::VoiceRecording->value => 'Votre voix est enregistrée pour construire votre livre. Vous pouvez revenir sur cet accord à tout moment.',
        ConsentKind::Transcription->value => 'Votre enregistrement est transcrit en texte pour que vos proches puissent le lire.',
        ConsentKind::AiRendering->value => 'Un outil d’intelligence artificielle met votre texte en forme. La version mot à mot est conservée à côté et n’est jamais remplacée. Aucun contenu de votre famille ne sert à entraîner cet outil.',
        ConsentKind::FamilySharing->value => 'Vos histoires validées sont visibles des proches que vous avez autorisés, et d’eux seuls.',
        ConsentKind::SensitiveCategories->value => 'Vos récits peuvent aborder votre santé, vos convictions ou vos origines. Vous acceptez que ces passages soient conservés avec le reste.',
        ConsentKind::PhoneCallRecording->value => 'L’appel téléphonique est enregistré pour construire votre livre. Votre accord est demandé oralement au début de chaque appel.',
        ConsentKind::PhotoRights->value => 'Vous confirmez pouvoir déposer cette photo et en autoriser l’usage dans le livre de la famille.',
        ConsentKind::PostMortemDirectives->value => 'Vous indiquez ce qu’il faudra faire de vos histoires après votre décès. Vos directives prévalent sur la demande de vos proches.',
    ];

    public function run(): void
    {
        foreach (ConsentKind::cases() as $kind) {
            ConsentText::query()->updateOrCreate(
                ['kind' => $kind->value, 'version' => '1.0', 'locale' => 'fr'],
                [
                    'body' => self::BODIES[$kind->value].' [À VALIDER PAR CONSEIL]',
                    'effective_from' => now()->startOfDay(),
                ],
            );
        }
    }
}
