<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AddNarrator;
use App\Actions\CreateProject;
use App\Enums\Offer;
use App\Jobs\SendGiftInvitation;
use App\Models\User;
use App\Services\Tokens\TokenService;
use App\Support\Links;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Une invitation neuve, à la demande.
 *
 * Le décor ne sème qu'un scénario d'acceptation et un de refus, et l'opt-in
 * est **définitif** par construction — c'est le produit qui a raison, et il ne
 * changera pas pour arranger un test. Mais la suite bout en bout les consomme
 * avant qu'un humain n'arrive : le point 4 du checkpoint du bloc 10 n'était
 * jouable qu'une fois par semis, et il a fallu fabriquer un projet à la main
 * en pleine vérification pour le dérouler (T-169). Un checkpoint qu'on ne peut
 * pas rejouer n'est pas un checkpoint — la leçon est la même qu'au bloc 09
 * avec `demo:moteur` (T-155).
 *
 * Elle passe par le vrai chemin : `SendGiftInvitation`, le même jeton, le même
 * message. Elle n'enveloppe rien — elle prépare, puis appelle. Et elle imprime
 * le lien, que `RedactTokens` masque partout ailleurs à dessein : sans ça il
 * faudrait aller le pêcher dans Mailpit, et un narrateur joignable par SMS
 * n'en laisserait aucune trace lisible.
 */
#[AsCommand(name: 'demo:invitation', description: 'Fabrique un projet neuf et imprime son lien d’invitation')]
final class DemoInvitation extends Command
{
    /** @var string */
    protected $signature = 'demo:invitation
        {--prenom= : Le prénom de la personne invitée ; au hasard sans cette option}';

    /** @var string */
    protected $description = 'Fabrique un projet neuf et imprime son lien d’invitation';

    /** @var list<string> */
    private const PRENOMS = ['Suzanne', 'Odette', 'Germaine', 'Lucienne', 'Yvette', 'Paulette'];

    public function handle(TokenService $tokens): int
    {
        if (app()->isProduction()) {
            $this->components->error('Cette commande ne touche qu’un décor de démonstration. Jamais en production.');

            return self::FAILURE;
        }

        $prenom = (string) ($this->option('prenom') ?: self::PRENOMS[array_rand(self::PRENOMS)]);
        $suffixe = mb_strtolower(Str::random(6));

        $owner = User::query()->firstOrCreate(
            ['email' => 'invitation@example.test'],
            [
                'name' => 'Décor des invitations',
                'password' => Hash::make((string) config('product.seeding.admin_password')),
                'email_verified_at' => now(),
            ],
        );

        $project = app(CreateProject::class)->handle($owner, Offer::Pilot, ['prompt_day' => 1]);

        // Le courriel, et non le SMS : le lien doit rester lisible. En local
        // les SMS partent dans le journal, et `RedactTokens` y masque le jeton
        // — le chemin téléphone rendrait la vérification injouable.
        app(AddNarrator::class)->handle($project, [
            'first_name' => $prenom,
            'display_name' => $prenom,
            'email' => mb_strtolower($prenom).'+'.$suffixe.'@example.test',
            'preferred_channel' => 'email',
            'birth_year' => 1938,
        ]);

        $plain = (new SendGiftInvitation($project->refresh()->id))->handle($tokens);

        if ($plain === null) {
            $this->components->error('L’invitation n’est pas partie : voir `gift.*` dans les journaux.');

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Invitée', $prenom);
        $this->components->twoColumnDetail('Projet', $project->id);
        $this->newLine();
        $this->components->info('Le lien, à usage unique — il accepte ou il refuse, pas les deux :');
        $this->line('  '.Links::invitation($plain));
        $this->newLine();
        $this->line('  <fg=gray>Relancez la commande pour en obtenir un autre : chaque appel fabrique</>');
        $this->line('  <fg=gray>un projet neuf, parce que l’opt-in ne se rejoue pas.</>');

        return self::SUCCESS;
    }
}
