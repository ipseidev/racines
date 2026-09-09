<?php

declare(strict_types=1);

use App\Actions\UpdateBrandSettings;
use App\Enums\TokenType;
use App\Models\Story;
use App\Models\User;
use App\Notifications\PromptNotification;
use App\Services\Tokens\TokenService;
use App\Support\Brand;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Le gabarit commun des courriels (T-237).
 *
 * Tout ce qu'un courriel dit autour du message vient des réglages de
 * marque, lus au rendu : le nom, le pictogramme, les couleurs, l'adresse du
 * support, le domaine des liens, la mention légale. Ces tests changent un
 * réglage et regardent le HTML produit — c'est la seule preuve qu'aucun
 * d'eux n'est écrit en dur, ni dans un fichier CSS ni dans une vue.
 */
function renderedMail(?MailMessage $message = null): string
{
    $message ??= (new MailMessage)
        ->greeting('Bonjour Odette,')
        ->line('Une ligne.')
        ->action('Répondre en parlant', 'https://liens.example/r/abc')
        ->line('Une autre.');

    return (string) $message->render();
}

it('porte le nom de marque des réglages en en-tête, et jamais le nom de l’application', function (): void {
    app(UpdateBrandSettings::class)->handle(['product_name' => 'Essai Souvenirs']);
    config()->set('app.name', 'Racines-Technique');

    $html = renderedMail();

    expect($html)->toContain('Essai Souvenirs')
        ->and($html)->not->toContain('Racines-Technique')
        ->and($html)->not->toContain('All rights reserved');
});

it('pose les couleurs de la marque en styles en ligne, lues au rendu', function (): void {
    app(UpdateBrandSettings::class)->handle([
        'color_primary' => '#123456',
        'color_primary_foreground' => '#FFFFFF',
        'color_accent' => '#654321',
        'color_accent_foreground' => '#FFFFFF',
    ]);

    $html = renderedMail();

    // Le nom de marque en couleur principale, le bouton en couleur d'action.
    expect($html)->toMatch('/class="brand-name[^"]*" style="[^"]*color: #123456/')
        ->and($html)->toMatch('/class="button[^"]*"[^>]*style="[^"]*background-color: #654321/');
});

it('n’emploie la couleur d’action que sur le bouton', function (): void {
    app(UpdateBrandSettings::class)->handle([
        'color_accent' => '#654321',
        'color_accent_foreground' => '#FFFFFF',
    ]);

    $html = renderedMail();

    // Deux occurrences : la cellule qui porte le bouton, et le bouton.
    // Aucune autre — un bouton se voit par son isolement.
    expect(substr_count($html, '#654321'))->toBe(2);
});

it('montre le pictogramme en PNG à côté du nom, jamais le SVG', function (): void {
    $html = renderedMail();

    expect($html)->toContain('/img/brand/mark.png')
        ->and($html)->not->toContain('mark.svg')
        // Décoratif : le nom est là, en texte, même si l'image est bloquée.
        ->and($html)->toMatch('/<img src="[^"]*mark\.png" class="mark" alt="" /');
});

it('préfère un pictogramme matriciel téléversé, et se passe d’un SVG téléversé', function (): void {
    app(UpdateBrandSettings::class)->handle(['mark_path' => 'brand/mark-maison.png']);
    expect(renderedMail())->toContain('storage/brand/mark-maison.png');

    app(UpdateBrandSettings::class)->handle(['mark_path' => 'brand/mark-maison.svg']);
    $html = renderedMail();

    expect($html)->not->toContain('mark-maison.svg')
        ->and($html)->not->toContain('mark.png')
        ->and($html)->toContain(Brand::name());
});

it('met dans le pied l’adresse du support, le domaine des liens et la mention légale', function (): void {
    app(UpdateBrandSettings::class)->handle([
        'support_email' => 'aide@souvenirs.example',
        'links_domain' => 'liens.souvenirs.example',
        'legal_entity' => 'Souvenirs SAS',
        'legal_address' => '12 rue des Lilas, 75011 Paris',
    ]);

    $html = renderedMail();

    expect($html)->toContain('mailto:aide@souvenirs.example')
        ->and($html)->toContain('https://liens.souvenirs.example')
        ->and($html)->toContain('Souvenirs SAS · 12 rue des Lilas, 75011 Paris')
        ->and($html)->toContain('Données hébergées dans l’Union européenne');
});

it('tait la mention légale tant qu’elle n’est pas renseignée', function (): void {
    app(UpdateBrandSettings::class)->handle(['legal_entity' => '', 'legal_address' => '']);

    expect(renderedMail())->not->toContain('footer-legal">'.' · ');
});

it('écrit en français ce que le framework écrivait en anglais', function (): void {
    $html = renderedMail();

    expect($html)->toContain('Si le bouton « Répondre en parlant » ne réagit pas')
        ->and($html)->not->toContain('If you\'re having trouble');
});

it('donne un salut et une signature de repli en français, au nom de la marque', function (): void {
    app(UpdateBrandSettings::class)->handle(['product_name' => 'Essai Souvenirs']);

    $html = renderedMail((new MailMessage)->line('Une ligne.'));

    expect($html)->toContain('Bonjour,')
        ->and($html)->toContain('À bientôt, l’équipe Essai Souvenirs.')
        ->and($html)->not->toContain('Regards');
});

it('ignore le niveau de la notification : le bouton est toujours de la couleur d’action', function (): void {
    app(UpdateBrandSettings::class)->handle(['color_accent' => '#654321', 'color_accent_foreground' => '#FFFFFF']);

    $html = renderedMail((new MailMessage)->error()->line('Un souci.')->action('Voir', 'https://liens.example/x'));

    expect($html)->toMatch('/class="button[^"]*"[^>]*style="[^"]*background-color: #654321/')
        ->and($html)->toContain('Un instant,');
});

it('traduit le courriel de réinitialisation du mot de passe', function (): void {
    $user = User::factory()->create();

    $message = (new ResetPassword('jeton-de-test'))->toMail($user);
    $html = (string) $message->render();

    expect($message->subject)->toBe('Réinitialiser votre mot de passe')
        ->and($html)->toContain('Choisir un nouveau mot de passe')
        ->and($html)->not->toContain('Reset Password')
        ->and($html)->not->toContain('Hello!');
});

it('pose la question de la semaine dans sa carte, sous un filet d’or, en police de titre', function (): void {
    app(UpdateBrandSettings::class)->handle(['font_display' => 'Fraunces']);

    $story = Story::factory()->proposed()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    $html = (string) (new PromptNotification($story, $issued->plain))->toMail($story->narrator)->render();

    expect($html)->toMatch('/class="question-text[^"]*" style="[^"]*font-family: \'Fraunces\'/')
        ->and($html)->toContain((string) $story->questionText())
        ->and($html)->toMatch('/class="question-rule"[^>]*style="[^"]*background-color: #C9A24B/i');
});

it('garde une version texte lisible, sans balise', function (): void {
    $story = Story::factory()->proposed()->create();
    $issued = app(TokenService::class)->issue(TokenType::Record, $story);

    $message = (new PromptNotification($story, $issued->plain))->toMail($story->narrator);
    $text = (string) app(Markdown::class)->renderText($message->markdown, $message->data());

    expect($text)->toContain((string) $story->questionText())
        ->and($text)->not->toContain('<table');
});

it('déclare un thème clair et les polices de la charte', function (): void {
    $html = renderedMail();

    expect($html)->toContain('<meta name="color-scheme" content="light">')
        ->and($html)->toContain('fraunces-var-roman.woff2')
        ->and($html)->toContain('inter-400.woff2');
});
