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

it('lit un corpus valide', function (): void {
    expect(QuestionCorpus::default()->problems())->toBe([]);
});

it('porte les soixante questions historiques, sans doublon de slug', function (): void {
    $slugs = array_column(QuestionCorpus::default()->entries(), 'slug');

    expect($slugs)->toHaveCount(60)
        ->and(array_unique($slugs))->toHaveCount(60);
});

it('écrit chaque question au vouvoiement et au tutoiement', function (): void {
    foreach (QuestionCorpus::default()->entries() as $entry) {
        expect(trim($entry['vous']))->not->toBe('', "{$entry['slug']} : vouvoiement vide")
            ->and(trim($entry['tu']))->not->toBe('', "{$entry['slug']} : tutoiement vide");
    }
});

it('ne laisse aucun « votre » ni « vos » dans un texte au tutoiement', function (): void {
    foreach (QuestionCorpus::default()->entries() as $entry) {
        expect(preg_match('/\b(votre|vos|êtes)\b/iu', $entry['tu']))
            ->toBe(0, "{$entry['slug']} tutoie mal : {$entry['tu']}");
    }
});

it('résout chaque texte dans les deux genres sans laisser de marqueur', function (): void {
    foreach (QuestionCorpus::default()->entries() as $entry) {
        foreach ([$entry['vous'], $entry['tu']] as $template) {
            foreach ([GrammaticalGender::Feminine, GrammaticalGender::Masculine] as $gender) {
                $text = QuestionWording::resolve($template, $gender);

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
