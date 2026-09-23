<?php

declare(strict_types=1);

use App\Enums\GrammaticalGender;
use App\Support\QuestionCorpus;
use App\Support\QuestionWording;

/*
 * Le fichier du corpus, lu tel qu'il partira en production.
 *
 * Ces tests sont la relecture qu'aucun humain ne fera sur cent quatre-vingts
 * questions : chaque texte existe au vouvoiement et au tutoiement, chaque
 * marqueur s'accorde dans les deux genres, et aucune étiquette n'est inventée.
 */

/** Les slugs du corpus v1, tels qu'ils sont en production depuis le bloc 05. */
final class QuestionCorpusV1
{
    public const SLUGS = [
        'adulte-qui-a-compte',
        'ami-perdu-de-vue',
        'apprendre-velo-nager-lire',
        'avec-les-enfants',
        'betise-enfant',
        'cachette',
        'cadeau-touchant',
        'ce-quon-retienne',
        'choix-professionnel',
        'conseil-couple',
        'conseil-dix-huit-ans',
        'conteur-famille',
        'croyance-changee',
        'decision-difficile',
        'dimanche-dix-ans',
        'dispute-fou-rire',
        'epreuve-la-plus-dure',
        'evenement-du-monde',
        'expression-famille',
        'fou-rire',
        'geste-recette-secret',
        'grande-peur',
        'grands-parents',
        'histoire-jamais-racontee',
        'journee-de-travail',
        'lecon-echec',
        'lieu-qui-manque',
        'maison-enfance',
        'maison-quittee',
        'mariage-ou-vie-commune',
        'message-dans-cinquante-ans',
        'metier-fierte',
        'mode-jeunesse',
        'monde-des-petits-enfants',
        'musique-quinze-ans',
        'naissance-recit',
        'nom-de-famille',
        'objet-transmis',
        'odeur-enfance',
        'petite-habitude-heureuse',
        'plat-enfance',
        'plus-beau-jour',
        'plus-grande-fierte',
        'premier-enfant',
        'premier-jour-travail',
        'premier-souvenir',
        'premiere-liberte',
        'priere-poeme-chanson',
        'qualite-pere-mere',
        'qui-a-donne-sa-chance',
        'rencontre-conjoint',
        'reve-metier',
        'revoir-une-derniere-fois',
        'surnoms-famille',
        'tradition-gardee',
        'travail-et-les-gens',
        'valeur-transmise',
        'vie-reussie',
        'ville-village-avant',
        'voyage-qui-change',
    ];
}

it('lit un corpus valide', function (): void {
    expect(QuestionCorpus::default()->problems())->toBe([]);
});

it('garde les soixante slugs du corpus v1, sans doublon', function (): void {
    // Un slug ne disparaît jamais : des histoires de production le citent.
    // Pour retirer une question, on la désactive (`'active' => false`).
    $slugs = array_column(QuestionCorpus::default()->entries(), 'slug');

    expect(array_unique($slugs))->toHaveCount(count($slugs))
        ->and(array_diff(QuestionCorpusV1::SLUGS, $slugs))->toBe([]);
});

it('réserve le prénom de l’acheteur aux questions qui le demandent', function (): void {
    foreach (QuestionCorpus::default()->entries() as $entry) {
        $named = QuestionWording::needsBuyerName($entry['vous']) || QuestionWording::needsBuyerName($entry['tu']);

        expect($named)->toBe(in_array('about_buyer', $entry['conditions'], true), "{$entry['slug']} : prénom et condition `about_buyer` vont ensemble");
    }
});

it('tient trente mots au plus par question', function (): void {
    foreach (QuestionCorpus::default()->entries() as $entry) {
        foreach (['vous', 'tu'] as $form) {
            $words = count(preg_split('/\s+/u', trim(QuestionWording::resolve($entry[$form], null))) ?: []);

            expect($words)->toBeLessThanOrEqual(30, "{$entry['slug']} ({$form}) : {$words} mots");
        }
    }
});

it('écrit chaque question au vouvoiement et au tutoiement', function (): void {
    foreach (QuestionCorpus::default()->entries() as $entry) {
        expect(trim($entry['vous']))->not->toBe('', "{$entry['slug']} : vouvoiement vide")
            ->and(trim($entry['tu']))->not->toBe('', "{$entry['slug']} : tutoiement vide");
    }
});

it('ne laisse aucune forme du vouvoiement dans un texte au tutoiement', function (): void {
    // Ce qui trahit un vouvoiement oublié : « votre », « êtes », un verbe en
    // « -ez ». Le « vous » pluriel, lui, est juste — elle et son frère, elle
    // et son conjoint — et ces questions-là sont nommées une par une, relues
    // à la main, plutôt que devinées par une expression.
    $plural = [
        'ami-perdu-de-vue',            // « ce que vous faisiez ensemble »
        'fratrie-portrait',            // « quand vous étiez petits »
        'fratrie-disputes',            // « pour quoi vous disputiez-vous »
        'mariage-ou-vie-commune',      // « le jour où vous avez décidé »
        'premier-rendez-vous',         // « votre premier rendez-vous »
        'premier-chez-soi-a-deux',     // « votre premier logement à deux »
        'epreuve-a-deux',              // « avez-vous traversée à deux »
        'vacances-avec-les-enfants',   // « où partiez-vous avec les enfants »
        'prenom-moment-ensemble',      // « où étiez-vous », elle et l'acheteur
    ];

    foreach (QuestionCorpus::default()->entries() as $entry) {
        if (in_array($entry['slug'], $plural, true)) {
            continue;
        }

        expect(preg_match('/\b(votre|êtes|(?!chez\b|assez\b|nez\b)\w+ez)\b/iu', $entry['tu']))
            ->toBe(0, "{$entry['slug']} tutoie mal : {$entry['tu']}");
    }
});

it('résout chaque texte dans les deux genres sans laisser de marqueur', function (): void {
    foreach (QuestionCorpus::default()->entries() as $entry) {
        foreach ([$entry['vous'], $entry['tu']] as $template) {
            foreach ([GrammaticalGender::Feminine, GrammaticalGender::Masculine] as $gender) {
                // L'acheteur dans l'autre genre : un marqueur accordé du
                // mauvais côté se verrait.
                $buyer = $gender === GrammaticalGender::Feminine ? GrammaticalGender::Masculine : GrammaticalGender::Feminine;
                $text = QuestionWording::resolve($template, $gender, 'Claire', $buyer);

                expect($text)->not->toContain('{')
                    ->and($text)->not->toContain('}')
                    ->and($text)->not->toContain('·', "{$entry['slug']} garde un point médian au genre connu");
            }
        }
    }
});

it('refuse un fichier dont les étiquettes ne sont pas connues', function (): void {
    $corpus = QuestionCorpus::fromArray([[
        'slug' => 'essai', 'theme' => 'childhood', 'difficulty' => 1, 'order' => 10,
        'tone' => 'joyeux', 'conditions' => ['chien'], 'sensitive' => ['impots'],
        'vous' => 'Quel est votre souvenir ?', 'tu' => 'Quel est ton souvenir ?',
    ]]);

    $problems = implode("\n", $corpus->problems());

    expect($problems)->toContain('joyeux')
        ->and($problems)->toContain('chien')
        ->and($problems)->toContain('impots');
});

it('refuse deux entrées au même slug', function (): void {
    $entry = [
        'slug' => 'essai', 'theme' => 'childhood', 'difficulty' => 1, 'order' => 10,
        'tone' => 'light', 'conditions' => [], 'sensitive' => [],
        'vous' => 'Quel est votre souvenir ?', 'tu' => 'Quel est ton souvenir ?',
    ];

    expect(implode("\n", QuestionCorpus::fromArray([$entry, $entry])->problems()))->toContain('essai');
});
