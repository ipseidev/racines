<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ConsentKind;
use App\Models\ConsentText;
use Illuminate\Database\Seeder;

/**
 * Le texte en vigueur de chaque consentement.
 *
 * La version 1.0 portait la mention `[À VALIDER PAR CONSEIL]`, qui s'affichait
 * à la fin de chaque paragraphe sur la page des accords et dans le tunnel. Une
 * note de fabrication n'a rien à faire sous les yeux de quelqu'un à qui l'on
 * demande son accord : elle dit « ce texte n'est pas arrêté » au moment précis
 * où l'on attend un engagement. Elle disparaît en 1.1 (T-242).
 *
 * **Une nouvelle version, et non une réécriture de la 1.0.** `consents`
 * pointe une version précise et la promesse du dossier est de pouvoir
 * réafficher ce qui a été lu : corriger le texte de la 1.0 changerait
 * rétroactivement ce que quelqu'un a accepté. Les lignes déjà signées gardent
 * donc leur 1.0, mot pour mot, et les pages publiques affichent la 1.1 —
 * `ConsentText::current()` prend la plus récente entrée en vigueur.
 *
 * La relecture par un conseil, elle, reste due : elle est un acte posé dans
 * l'administration (`PilotSettings::legal_validated_at`), que `golive:check`
 * vérifie, et non une note en bas d'un paragraphe.
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
        ConsentKind::DeclaredSharing->value => 'Vous demandez que vos histoires soient partagées avec vos proches dès qu’elles sont prêtes, sans qu’on vous le redemande à chaque fois. Vous restez la seule à décider : vous pouvez revenir sur ce choix à tout moment, et retirer une histoire déjà partagée d’un seul geste.',
        ConsentKind::MandateDelegation->value => 'Vous autorisez un proche que vous désignez à valider vos histoires à votre place, quand vous ne le faites pas vous-même. Vous pouvez retirer cette autorisation à tout moment, et elle cesse aussitôt.',
        ConsentKind::EarlyServiceStart->value => 'Vous demandez que le service numérique démarre immédiatement, sans attendre la fin du délai de rétractation de quatorze jours. Vous conservez ce droit, mais nous pourrons retenir une part correspondant à ce qui aura déjà été fourni.',
        ConsentKind::MarketingEmail->value => 'Vous acceptez de recevoir de nos nouvelles par courriel. Ce n’est jamais nécessaire pour acheter ni pour utiliser le service, et un lien de désinscription figure dans chaque message.',
    ];

    /** La version que ce semis met en vigueur. */
    public const VERSION = '1.1';

    public function run(): void
    {
        foreach (ConsentKind::cases() as $kind) {
            ConsentText::query()->updateOrCreate(
                ['kind' => $kind->value, 'version' => self::VERSION, 'locale' => 'fr'],
                [
                    'body' => self::BODIES[$kind->value],
                    'effective_from' => now()->startOfDay(),
                ],
            );
        }
    }
}
