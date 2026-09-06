<?php

declare(strict_types=1);

use App\Actions\IssueRecordToken;
use App\Enums\TokenIssuedReason;
use App\Enums\TokenType;
use App\Models\FamilyMember;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickPixel;

/**
 * Le dépôt d'une photo, **par la route** et non par l'action.
 *
 * L'action était couverte, la route ne l'était pas, et c'est entre les deux
 * que se logeait le défaut : le contrôleur refusait tout ce qui n'avait pas
 * un type d'image **avant** que l'antivirus ne voie le fichier. Un fichier
 * hostile déguisé repartait donc avec « le fichier doit être une image », sans
 * scan, sans trace, et le contrôle antivirus n'était pas démontrable —
 * découvert en préparant le point 3 du checkpoint du bloc 12 (T-187).
 *
 * `AttachPhoto` annonce pourtant l'ordre en tête de sa classe : « Scanner
 * d'abord. » C'est le contrôleur qui le contredisait. Même motif que T-181 et
 * T-186 : l'unité passe, la couture ne tient pas.
 */
beforeEach(function (): void {
    Storage::fake('r2');
});

/** Une vraie photo, pour la contre-épreuve. */
function photoDeDepot(): UploadedFile
{
    $image = new Imagick;
    $image->newImage(1600, 1200, new ImagickPixel('#8B7355'));
    $image->setImageFormat('jpeg');

    $path = tempnam(sys_get_temp_dir(), 'depot').'.jpg';
    $image->writeImage($path);
    $image->clear();

    return new UploadedFile($path, 'souvenir.jpg', 'image/jpeg', null, true);
}

/** @return array{string, Story} */
function lienDepot(): array
{
    $story = Story::factory()->toReview()->create();
    $issued = app(IssueRecordToken::class)->handle($story, TokenIssuedReason::Rotation);

    return [$issued->plain, $story->refresh()];
}

it('fait passer le fichier par l’antivirus avant le contrôle de type', function (): void {
    [$token, $story] = lienDepot();

    $piege = UploadedFile::fake()->createWithContent(
        'piege.txt',
        'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*',
    );

    $this->from("/r/{$token}/review")
        ->post("/r/{$token}/photos", ['photo' => $piege])
        ->assertRedirect("/r/{$token}/review")
        // Le message de l'antivirus, et non celui du contrôle de type : c'est
        // la différence entre « nous avons regardé » et « nous n'avons pas
        // regardé ».
        ->assertSessionHasErrors(['photo' => 'Nous n’avons pas pu accepter ce fichier : notre contrôle de sécurité l’a signalé. Essayez une autre photo, ou envoyez-la depuis un autre appareil.']);

    expect($story->refresh()->getMedia(Story::PHOTOS))->toHaveCount(0);
});

it('inscrit le refus de l’antivirus au journal d’audit', function (): void {
    [$token, $story] = lienDepot();

    $piege = UploadedFile::fake()->createWithContent(
        'piege.jpg',
        'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*',
    );

    $this->post("/r/{$token}/photos", ['photo' => $piege]);

    $trace = DB::table('audit_logs')->where('action', 'rejected Photo')->first();

    expect($trace)->not->toBeNull()
        ->and($trace->subject_id)->toBe((string) $story->getKey());

    $payload = json_decode((string) $trace->payload, true);

    expect($payload['reason'])->toBe('antivirus')
        ->and($payload['file_name'])->toBe('piege.jpg')
        // L'empreinte, jamais le contenu : un journal inaltérable n'est pas
        // un endroit où déposer un fichier suspect.
        ->and($payload['sha256'])->toHaveLength(64)
        ->and((string) $trace->payload)->not->toContain('EICAR');
});

it('accepte une vraie photo par la même route', function (): void {
    [$token, $story] = lienDepot();

    $this->post("/r/{$token}/photos", ['photo' => photoDeDepot()])
        ->assertSessionHasNoErrors();

    expect($story->refresh()->getMedia(Story::PHOTOS))->toHaveCount(1);
});

it('refuse encore ce qui n’est pas une image, mais après l’avoir scanné', function (): void {
    [$token, $story] = lienDepot();

    $pdf = UploadedFile::fake()->createWithContent('facture.pdf', '%PDF-1.4 rien de dangereux');

    $this->post("/r/{$token}/photos", ['photo' => $pdf])
        ->assertSessionHasErrors('photo');

    expect($story->refresh()->getMedia(Story::PHOTOS))->toHaveCount(0)
        ->and(DB::table('audit_logs')->where('action', 'rejected Photo')->count())->toBe(0);
});

/*
 * Les autres entrées, et la vraie raison de ce fichier.
 *
 * `store`, `updateCaption` et `destroy` lisaient leurs paramètres **par
 * position** : Laravel remplit les paramètres scalaires dans l'ordre de
 * l'URL, sans regarder leur nom. `{token}` arrivait donc dans `$story`, et
 * `$story` dans `$photo`. Seule `POST /r/{token}/photos` fonctionnait, parce
 * qu'elle n'a pas d'autre paramètre et que l'histoire y vient du sujet du
 * jeton — et c'est exactement la seule que les tests exerçaient (T-191).
 *
 * Résultat : depuis l'espace de la narratrice comme depuis un lien famille,
 * **aucune photo n'a jamais pu être déposée**, et l'erreur remontait en 500
 * parce que Postgres refuse un jeton là où il attend un UUID. Trouvé par le
 * fondateur au point 1 du checkpoint du bloc 12, sur un iPhone.
 */
function proche(Story $story, bool $contribue): array
{
    $member = FamilyMember::factory()->create([
        'project_id' => $story->project_id,
        'can_contribute' => $contribue,
    ]);

    $issued = app(TokenService::class)->issue(TokenType::ListenProject, $member, ['listen', 'react']);

    return [$issued->plain, $member];
}

it('laisse un proche autorisé déposer depuis son lien d’écoute', function (): void {
    $story = Story::factory()->shared()->create();
    [$token] = proche($story, contribue: true);

    $this->post("/l/{$token}/stories/{$story->getKey()}/photos", ['photo' => photoDeDepot()])
        ->assertSessionHasNoErrors();

    expect($story->refresh()->getMedia(Story::PHOTOS))->toHaveCount(1);
});

it('refuse le dépôt à un proche sans droit de contribuer', function (): void {
    $story = Story::factory()->shared()->create();
    [$token] = proche($story, contribue: false);

    $this->post("/l/{$token}/stories/{$story->getKey()}/photos", ['photo' => photoDeDepot()])
        ->assertForbidden();

    expect($story->refresh()->getMedia(Story::PHOTOS))->toHaveCount(0);
});

it('laisse la narratrice déposer depuis son espace', function (): void {
    $story = Story::factory()->shared()->create();
    $narrator = $story->project->primaryNarrator;
    $issued = app(TokenService::class)->issue(TokenType::NarratorSpace, $narrator, ['manage']);

    $this->post("/n/{$issued->plain}/stories/{$story->getKey()}/photos", ['photo' => photoDeDepot()])
        ->assertSessionHasNoErrors();

    expect($story->refresh()->getMedia(Story::PHOTOS))->toHaveCount(1);
});

it('refuse l’histoire d’une autre famille', function (): void {
    $sienne = Story::factory()->shared()->create();
    $autre = Story::factory()->shared()->create();
    [$token] = proche($sienne, contribue: true);

    $this->post("/l/{$token}/stories/{$autre->getKey()}/photos", ['photo' => photoDeDepot()])
        ->assertNotFound();

    expect($autre->refresh()->getMedia(Story::PHOTOS))->toHaveCount(0);
});

it('légende et retire une photo par son identifiant, pas par le jeton', function (): void {
    [$token, $story] = lienDepot();

    $this->post("/r/{$token}/photos", ['photo' => photoDeDepot()]);
    $photo = $story->refresh()->getFirstMedia(Story::PHOTOS);

    $this->patch("/r/{$token}/photos/{$photo->getKey()}", ['caption' => 'Le fournil de Kerhostin'])
        ->assertSessionHasNoErrors();

    expect($story->refresh()->getFirstMedia(Story::PHOTOS)->getCustomProperty('caption'))
        ->toBe('Le fournil de Kerhostin');

    $this->delete("/r/{$token}/photos/{$photo->getKey()}")
        ->assertSessionHasNoErrors();

    expect($story->refresh()->getMedia(Story::PHOTOS))->toHaveCount(0);
});
