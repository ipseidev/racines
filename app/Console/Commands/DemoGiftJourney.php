<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AddNarrator;
use App\Actions\CreateProject;
use App\Actions\IssueRecordToken;
use App\Enums\Offer;
use App\Enums\TokenIssuedReason;
use App\Health\ClamavCheck;
use App\Jobs\SendGiftInvitation;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use App\Services\Tokens\TokenService;
use App\States\Story\Proposed;
use App\Support\Links;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Health\Enums\Status;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Le parcours de celle à qui on offre le cadeau, bout à bout.
 *
 * Les deux moitiés existaient déjà — `demo:invitation` pour l'annonce, le
 * planificateur pour la question — mais rien ne les reliait, et c'est
 * justement la couture qu'un humain veut voir : ce qu'une personne reçoit, ce
 * qu'elle comprend, ce qu'elle accepte, et ce qu'elle enregistre ensuite.
 *
 * Entre les deux il y a **une nuit**, posée à dessein par `AcceptInvitation` :
 * « une question dans la minute donne l'impression d'une machine qui
 * attendait ». Un humain devant un navigateur n'a pas une nuit devant lui, et
 * l'avancer à la main demande de connaître `next_prompt_at`, puis d'aller
 * pêcher dans Mailpit un lien que `RedactTokens` masque partout ailleurs.
 *
 * La commande avance donc l'horloge du projet et **appelle la vraie
 * commande** : `prompts:dispatch-due`, celle qui tourne toutes les cinq
 * minutes en production. Elle n'enveloppe rien — envelopper la commande réelle
 * reviendrait à tester l'enveloppe (conventions §6).
 *
 * Deux étapes, parce qu'un humain agit entre les deux, et parce que
 * l'acceptation est **définitive** : chaque appel sans `--question` fabrique
 * un projet neuf, comme `demo:invitation` (T-169).
 */
#[AsCommand(name: 'demo:cadeau', description: 'Le parcours du cadeau : de l’annonce à la première histoire')]
final class DemoGiftJourney extends Command
{
    /** @var string */
    protected $signature = 'demo:cadeau
        {--prenom= : Le prénom de la personne à qui le cadeau est offert ; au hasard sans cette option}
        {--question : Étape 2 — la première question, sans attendre le lendemain}';

    /** @var string */
    protected $description = 'Le parcours du cadeau : de l’annonce à la première histoire';

    /**
     * L'acheteuse du décor. Son nom s'affiche en tête de l'annonce — « X vous
     * offre… » — donc un prénom, et non « Décor des parcours » : la page à
     * éprouver est celle qu'un vrai destinataire voit.
     */
    private const BUYER_EMAIL = 'cadeau@example.test';

    private const BUYER_NAME = 'Camille';

    /**
     * Le mot de l'acheteuse, sans lequel l'annonce est une page de service.
     * C'est la moitié de ce que la personne lit avant de dire oui.
     */
    private const GIFT_MESSAGE = 'Maman, je voudrais garder tes histoires — celles que tu racontes toujours, et celles qu’on ne t’a jamais demandées.';

    /** @var list<string> */
    private const PRENOMS = ['Suzanne', 'Odette', 'Germaine', 'Lucienne', 'Yvette', 'Paulette'];

    public function handle(TokenService $tokens, IssueRecordToken $records): int
    {
        if (app()->isProduction()) {
            $this->components->error('Cette commande ne touche qu’un décor de démonstration. Jamais en production.');

            return self::FAILURE;
        }

        return $this->option('question') === true
            ? $this->firstQuestion($records)
            : $this->announce($tokens);
    }

    /**
     * Étape 1 : l'annonce.
     *
     * Le vrai chemin d'envoi — même job, même jeton, même message — et le lien
     * imprimé, que le journal masque à dessein. Sans ça il faudrait aller le
     * pêcher dans Mailpit, et un narrateur joignable par SMS n'en laisserait
     * aucune trace lisible.
     */
    private function announce(TokenService $tokens): int
    {
        $prenom = (string) ($this->option('prenom') ?: self::PRENOMS[array_rand(self::PRENOMS)]);
        $email = mb_strtolower($prenom).'+'.mb_strtolower(Str::random(6)).'@example.test';

        $buyer = User::query()->firstOrCreate(
            ['email' => self::BUYER_EMAIL],
            [
                'name' => self::BUYER_NAME,
                'password' => Hash::make((string) config('product.seeding.admin_password')),
                'email_verified_at' => now(),
            ],
        );

        $project = app(CreateProject::class)->handle($buyer, Offer::Pilot, [
            'prompt_day' => 1,
            'gift_message' => self::GIFT_MESSAGE,
        ]);

        // Le courriel, et non le SMS : le lien doit rester lisible. En local
        // les SMS partent dans le journal, où `RedactTokens` masque le jeton.
        app(AddNarrator::class)->handle($project, [
            'first_name' => $prenom,
            'display_name' => $prenom,
            'email' => $email,
            'preferred_channel' => 'email',
            'birth_year' => 1938,
        ]);

        $plain = (new SendGiftInvitation($project->refresh()->id))->handle($tokens);

        if ($plain === null) {
            $this->components->error('L’annonce n’est pas partie : voir `gift.*` dans les journaux.');

            return self::FAILURE;
        }

        $this->title('étape 1 sur 2 : l’annonce');
        $this->components->twoColumnDetail('Offert à', $prenom.' <fg=gray>'.$email.'</>');
        $this->components->twoColumnDetail('De la part de', self::BUYER_NAME);
        $this->components->twoColumnDetail('Projet', $project->id);
        $this->newLine();

        $this->step(1, 'Ouvrir l’annonce. Rien n’y propose d’enregistrer, et les deux boutons ont la même taille : le cadeau se propose, il ne s’impose pas.');
        $this->link(Links::invitation($plain));

        $this->step(2, 'Accepter : cinq cases distinctes, le canal, le jour et le créneau. L’écran de bienvenue suit, avec la fiche contact.');
        $this->note('le même lien est parti par courriel — Mailpit, http://localhost:8027');

        $this->step(3, 'Puis, sans attendre le lendemain que l’acceptation vient de poser :');
        $this->command('demo:cadeau --question');

        return self::SUCCESS;
    }

    /**
     * Étape 2 : la première question, et l'enregistrement.
     *
     * Rejouable : tant que l'histoire proposée n'a pas été enregistrée, la
     * commande rouvre **son** lien plutôt que d'en empiler une deuxième. Un
     * checkpoint qu'on ne peut pas rejouer n'est pas un checkpoint (T-155).
     */
    private function firstQuestion(IssueRecordToken $records): int
    {
        $project = Project::query()
            ->whereHas('owner', fn ($query) => $query->where('email', self::BUYER_EMAIL))
            ->with('primaryNarrator')
            ->latest('created_at')
            ->first();

        if (! $project instanceof Project) {
            $this->components->error('Aucun cadeau en cours. Commencez par l’annonce : sail artisan demo:cadeau');

            return self::FAILURE;
        }

        if ($project->refused_at !== null) {
            $this->components->error('Cette annonce a été refusée, et un refus se respecte. Un cadeau neuf : sail artisan demo:cadeau');

            return self::FAILURE;
        }

        if ($project->accepted_at === null) {
            // Rien ne part avant l'acceptation : c'est l'invariant du bloc 10,
            // et la commande le dit plutôt que de le contourner.
            $this->components->error('L’annonce n’a pas encore été acceptée : aucune question ne peut partir.');
            $this->line('  <fg=gray>Ouvrez le lien de l’étape 1 et acceptez, ou reprenez un cadeau neuf</>');
            $this->line('  <fg=gray>avec `sail artisan demo:cadeau` — un lien en clair ne se relit pas.</>');

            return self::FAILURE;
        }

        $story = $this->pendingStory($project) ?? $this->askQuestion($project);

        if (! $story instanceof Story) {
            $this->components->error('Aucune question n’est partie : corpus épuisé, ou projet en pause. Voir `prompt.*` dans les journaux.');

            return self::FAILURE;
        }

        $issued = $records->handle($story, TokenIssuedReason::ReissueSupport);

        $this->title('étape 2 sur 2 : la première question');
        $this->components->twoColumnDetail('Offert à', (string) $project->primaryNarrator?->first_name);
        $this->components->twoColumnDetail('Question', '<fg=gray>« '.(string) $story->questionText().' »</>');
        $this->components->twoColumnDetail('Histoire', $story->id);
        $this->newLine();

        $this->step(4, 'Ouvrir le lien, enregistrer une réponse, la réécouter, l’envoyer. Rien ne dit « enregistrée » avant que le stockage l’ait confirmée.');
        $this->link(Links::record($issued->plain));
        $this->note('le même lien est parti par courriel ; celui-ci en est une réémission, et les deux ouvrent la même histoire');

        $this->step(5, 'Après la confirmation seulement, « Ajouter une photo » : proposer une image au milieu ferait abandonner l’enregistrement.');
        $this->antivirus();

        $this->step(6, 'Puis choisir « Partager », « Garder pour moi » ou « Décider plus tard ». Sans partage, la famille ne voit rien — jamais.');

        return self::SUCCESS;
    }

    /** L'histoire déjà proposée et pas encore enregistrée, s'il y en a une. */
    private function pendingStory(Project $project): ?Story
    {
        $story = $project->stories()
            ->where('state', Proposed::$name)
            ->orderByDesc('sequence')
            ->first();

        return $story instanceof Story ? $story : null;
    }

    /**
     * La question part **maintenant**.
     *
     * On avance `next_prompt_at`, et c'est tout : le reste est le travail de
     * `prompts:dispatch-due`, qui choisit la question, propose l'histoire,
     * émet le lien et envoie sur le canal choisi.
     */
    private function askQuestion(Project $project): ?Story
    {
        $project->next_prompt_at = now()->subMinute();
        $project->save();

        $this->call('prompts:dispatch-due');

        return $this->pendingStory($project->refresh());
    }

    /**
     * L'état du démon antivirus, imprimé là où il compte.
     *
     * `ClamavScanner` refuse tout fichier quand le démon est muet — le bon
     * choix — mais la conséquence visible est qu'**aucune photo ne passe**,
     * avec un message qui parle de sécurité. Le lire ici évite de chercher un
     * défaut du produit là où il n'y a qu'un conteneur éteint (T-185).
     */
    private function antivirus(): void
    {
        $result = ClamavCheck::new()->run();

        if ($result->status->equals(Status::ok())) {
            $this->line('       <fg=green>antivirus : '.$result->getShortSummary().'</>');

            return;
        }

        $this->line('       <fg=red>antivirus injoignable — aucune photo ne passera.</>');
        $this->line('       <fg=magenta>docker compose up -d clamav</> <fg=gray>puis une minute jusqu’à `healthy`</>');
    }

    private function title(string $titre): void
    {
        $this->newLine();
        $this->line("<fg=yellow;options=bold>── Parcours du cadeau — {$titre}</>");
        $this->newLine();
    }

    private function step(int $numero, string $quoi): void
    {
        $this->newLine();
        $this->line("  <fg=green>•</> {$numero}. {$quoi}");
    }

    private function link(string $url): void
    {
        $this->line("       <fg=blue>{$url}</>");
    }

    private function command(string $ligne): void
    {
        $this->line("       <fg=magenta>sail artisan {$ligne}</>");
    }

    private function note(string $texte): void
    {
        $this->line("       <fg=gray>{$texte}</>");
    }
}
