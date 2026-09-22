<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\InviteFamilyMember;
use App\Actions\ProposeStory;
use App\Actions\RecordShareDecision;
use App\Actions\ReissueFamilyLink;
use App\Enums\AnswerType;
use App\Enums\ProjectStatus;
use App\Enums\QuestionTheme;
use App\Enums\ShareDecision;
use App\Enums\ValidatedVia;
use App\Models\FamilyMember;
use App\Models\Narrator;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use App\Models\User;
use App\Settings\PilotSettings;
use App\States\Story\Recorded;
use App\States\Story\Shared;
use App\States\Story\Transcribed;
use App\States\Story\Validated;
use App\Support\Links;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Le parcours d'un proche invité, bout à bout.
 *
 * C'est le maillon H2 du dossier — « la famille écoute et réagit » — et c'est
 * le seul des trois rôles dont le parcours n'avait pas son banc d'essai :
 * `demo:offrir` suit l'acheteuse, les liens du narrateur sortent de
 * `demo:liens`, et le proche n'avait qu'une ligne dans une feuille.
 *
 * **Deux proches, pas un.** Le droit de **poser une question** s'accorde
 * personne par personne (R-1, dossier v3.1), et une règle qui n'est éprouvée
 * que sur celui qui l'a n'est pas éprouvée : le second sert à vérifier qu'il
 * ne voit pas le bouton et que le POST lui est refusé.
 *
 * **Les liens ne sont pas relus, ils sont émis.** Les jetons sont stockés
 * hachés (bloc 03) : un lien en clair n'existe qu'entre son émission et son
 * envoi. Chaque appel de ce banc en émet donc de nouveaux et révoque les
 * précédents — c'est le comportement du produit, pas une facilité de démo.
 *
 * Trois étapes, parce qu'un humain regarde un écran entre chacune.
 */
#[AsCommand(name: 'demo:proche', description: 'Le parcours d’un proche invité : du courriel à la réaction')]
final class DemoFamilyJourney extends Command
{
    /** @var string */
    protected $signature = 'demo:proche
        {--reaction : Étape 2 — ce que le proche fait sur la page, et ce qui doit en rester}
        {--retrait : Étape 3 — l’Initiateur·rice retire un accès, et le lien doit mourir}';

    /** @var string */
    protected $description = 'Le parcours d’un proche invité : du courriel à la réaction';

    private const OWNER_EMAIL = 'proches@example.test';

    private const CONTRIBUTOR_EMAIL = 'marie@example.test';

    private const LISTENER_EMAIL = 'paul@example.test';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('Cette commande ne touche qu’un décor de démonstration. Jamais en production.');

            return self::FAILURE;
        }

        return match (true) {
            $this->option('retrait') === true => $this->withdraw(),
            $this->option('reaction') === true => $this->onThePage(),
            default => $this->invite(),
        };
    }

    /**
     * Étape 1 : l'invitation part.
     *
     * Le décor se refait à chaque appel — un projet, une narratrice, une
     * histoire partagée et une gardée pour soi. La seconde est la seule qui
     * prouve quelque chose : si elle apparaît sur la page du proche, tout le
     * bloc 08 est faux.
     */
    private function invite(): int
    {
        $this->title('l’invitation');

        $project = $this->scenery();
        $narrator = $project->primaryNarrator;

        $this->invited($project, 'Marie', self::CONTRIBUTOR_EMAIL, 'sa fille', true);
        $this->invited($project, 'Paul', self::LISTENER_EMAIL, 'son neveu', false);

        $this->components->twoColumnDetail('Narratrice', (string) $narrator?->first_name);
        $this->components->twoColumnDetail('Projet', $project->id);
        $this->components->twoColumnDetail('Partagée', '<fg=gray>« '.$this->sharedQuestion($project).' »</>');
        $this->components->twoColumnDetail('Gardée pour elle', '<fg=gray>« '.$this->privateQuestion($project).' »</>');

        /*
         * Aucun lien imprimé ici, et c'est le point de l'étape.
         *
         * La première version en imprimait deux, obtenus en réémettant le
         * jeton juste après l'invitation — ce qui **révoquait celui du
         * courriel**. Le lien du terminal marchait, celui de la boîte aux
         * lettres répondait 410 : exactement le parcours qu'on prétendait
         * éprouver, et le seul qui comptait.
         *
         * Un proche invité commence dans sa boîte. Les liens s'impriment à
         * l'étape suivante, quand le courriel a fait son office.
         */
        $this->step(1, 'Ouvrir Mailpit : deux courriels, un par personne.');
        $this->link('http://localhost:8027');
        $this->note('Chacun doit nommer Camille qui invite et Odette qui raconte, ne promettre que l’écoute, et dire que la page ne demandera jamais de mot de passe.');

        $this->step(2, 'Cliquer le lien **depuis le courriel de Marie**. C’est le geste réel ; le terminal n’a pas à le raccourcir.');
        $this->note('La page nomme la narratrice et ne montre **que** l’histoire partagée.');
        $this->note('Si « '.$this->privateQuestion($project).' » apparaît, la promesse du produit est cassée.');

        $this->step(3, 'Revenir sur l’espace : Marie porte « a ouvert son lien », Paul « n’a jamais ouvert ».');
        $this->link($this->url('/espace/'.$project->id.'/proches'));
        $this->note('Compte : '.self::OWNER_EMAIL.' / password');

        $this->step(4, 'Puis la suite : ce que le proche fait sur la page.');
        $this->command('demo:proche --reaction');

        return self::SUCCESS;
    }

    /**
     * Étape 2 : ce qui se passe sur la page, et ce qui doit en rester.
     *
     * L'écoute et la réaction ne se simulent pas : ce sont deux gestes de
     * navigateur, et les fabriquer en base éprouverait une fabrication qui
     * n'existe nulle part ailleurs. La commande dit quoi faire et où le
     * vérifier.
     */
    private function onThePage(): int
    {
        $this->title('sur la page');

        $project = $this->project();

        if (! $project instanceof Project) {
            $this->components->error('Aucun décor. Commencez par l’invitation : sail artisan demo:proche');

            return self::FAILURE;
        }

        $marie = $project->familyMembers()->where('email', self::CONTRIBUTOR_EMAIL)->first();
        $paul = $project->familyMembers()->where('email', self::LISTENER_EMAIL)->first();

        if (! $marie instanceof FamilyMember || ! $paul instanceof FamilyMember) {
            $this->components->error('Les proches ont disparu du décor. Refaire : sail artisan demo:proche');

            return self::FAILURE;
        }

        $seuil = (int) config('product.family.listen_threshold_seconds');

        $this->components->twoColumnDetail('Marie', $marie->first_seen_at === null
            ? '<fg=yellow>n’a jamais ouvert son lien</>'
            : '<fg=green>a ouvert le '.$marie->first_seen_at->translatedFormat('j F à H\hi').'</>');
        $this->components->twoColumnDetail('Paul', $paul->first_seen_at === null
            ? '<fg=yellow>n’a jamais ouvert son lien</>'
            : '<fg=green>a ouvert le '.$paul->first_seen_at->translatedFormat('j F à H\hi').'</>');
        $this->components->twoColumnDetail('Réactions reçues', (string) $this->reactionCount($project));

        $this->step(1, 'Depuis le lien de Marie, ouvrir l’histoire et écouter plus de '.$seuil.' secondes.');
        $this->link($this->reissue($marie));
        $this->note('C’est le seuil que le dossier mesure (H2) : sous '.$seuil.' s, l’écoute ne compte pas.');

        $this->step(2, 'Réagir : un cœur ou un merci. Rien d’autre — un cercle d’écoute n’a pas besoin d’un fil de commentaires.');

        $this->step(3, 'Poser une question, avec des photos — le maillon que l’écoute seule ne produit pas.');
        $this->note('Sur l’accueil de Marie, sous les histoires : « Posez-lui votre question ». Elle doit partir **derrière** celles qui attendent déjà, et apparaître signée de son nom sur la frise et la page des questions de l’espace.');
        $this->note('Paul ne doit même pas voir le bouton — et forger le POST depuis son lien doit répondre 403 : un jeton d’écoute valide ne suffit pas.');
        $this->link($this->reissue($paul));
        $this->note('Aucun des deux ne peut plus coller une photo sur une histoire déjà racontée : une photo appartient à la question qui appelle le récit.');

        $this->step(4, 'La narratrice est prévenue **dans la minute** — c’est le bras « immediate » de la micro-expérience H2.');
        $this->command('queue:work --tries=1 --max-time=90');
        $this->note('Différé de 60 s exprès : un cœur et un merci envoyés d’affilée font une seule notification, pas deux vibrations.');
        $this->note('Sans worker, rien ne part et rien ne le dit — le travail attend dans Redis. C’est le premier endroit à regarder si la narratrice ne reçoit jamais rien.');

        $this->step(5, 'Vérifier ce qui est réellement sorti :');
        $this->command('tinker --execute="foreach (App\\Models\\OutboundMessage::latest()->limit(3)->get() as $m) { echo $m->template, \' | \', $m->channel->value, \' | \', $m->status->value, PHP_EOL; }"');
        $this->note('`reactions:send-digests` est l’autre bras — le résumé du lendemain matin. Il ne partira pas : `ReactionNotificationTiming::resolve()` renvoie « immediate » pour tout le monde, la répartition n’est pas encore posée.');

        $this->step(6, 'Puis le retrait, qui est la vraie garantie du cercle.');
        $this->command('demo:proche --retrait');

        return self::SUCCESS;
    }

    /**
     * Étape 3 : retirer un accès.
     *
     * C'est ce qui distingue un cercle d'écoute d'une page publique, et c'est
     * un `removed_at` et non une suppression : savoir qu'une personne a eu
     * accès fait partie de ce qu'on doit pouvoir répondre.
     */
    private function withdraw(): int
    {
        $this->title('le retrait');

        $project = $this->project();

        if (! $project instanceof Project) {
            $this->components->error('Aucun décor. Commencez par l’invitation : sail artisan demo:proche');

            return self::FAILURE;
        }

        $paul = $project->familyMembers()->where('email', self::LISTENER_EMAIL)->first();
        $marie = $project->familyMembers()->where('email', self::CONTRIBUTOR_EMAIL)->first();

        if (! $paul instanceof FamilyMember || ! $marie instanceof FamilyMember) {
            $this->components->error('Les proches ont disparu du décor. Refaire : sail artisan demo:proche');

            return self::FAILURE;
        }

        $lien = $this->reissue($paul);

        $this->step(1, 'Ouvrir le lien de Paul et le laisser ouvert dans un onglet.');
        $this->link($lien);

        $this->step(2, 'Dans l’espace, retirer Paul du cercle. La confirmation doit dire ce que ça coupe.');
        $this->link($this->url('/espace/'.$paul->project_id.'/proches'));

        $this->step(3, 'Recharger l’onglet de Paul : la page « ce lien n’est plus disponible », tout de suite, sans que rien d’autre ait à tourner.');
        $this->note('Et aucune donnée dans la réponse : ni titre, ni texte, ni audio.');

        $this->step(4, 'Marie, elle, continue d’écouter : on retire une personne, pas le cercle.');
        $this->link($this->reissue($marie));

        return self::SUCCESS;
    }

    /**
     * Le décor : un projet actif, une narratrice, deux histoires.
     *
     * Une partagée et une gardée pour soi. La seconde n'est pas du décor :
     * c'est le témoin, et sans elle la page du proche ne prouve rien.
     */
    private function scenery(): Project
    {
        $owner = User::query()->firstOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Camille',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $project = $this->project();

        if (! $project instanceof Project) {
            $project = new Project([
                'offer' => app(PilotSettings::class)->offer(),
                'status' => ProjectStatus::Active,
            ]);
            $project->owner()->associate($owner);
            $project->save();
            $project->startCollection(now()->subWeeks(3));

            Narrator::factory()->create([
                'project_id' => $project->id,
                'is_primary' => true,
                'first_name' => 'Odette',
                'display_name' => 'Odette',
            ]);
        }

        if ($project->stories()->count() === 0) {
            $this->story($project, 'demo-proche-partagee', 'Quel était le métier de votre mère ?', true);
            $this->story($project, 'demo-proche-privee', 'Qu’avez-vous regretté de ne pas dire ?', false);
        }

        return $project->refresh();
    }

    private function story(Project $project, string $slug, string $texte, bool $shared): Story
    {
        $question = Question::query()->firstOrCreate(
            ['slug' => $slug],
            ['text' => $texte, 'theme' => QuestionTheme::FamilyOrigins, 'order_hint' => 900],
        );

        $story = app(ProposeStory::class)->handle($project, $question);

        if (! $shared) {
            return $story;
        }

        /*
         * Par la **machine à états**, jamais par une écriture directe.
         *
         * `stories.state` ne s'écrit nulle part à la main — un test
         * structurel le vérifie sur tout `app/`, et il m'a attrapé ici. Ce
         * n'est pas une formalité : la garde R-4 refuse qu'une histoire
         * devienne validée sans qu'un narrateur l'ait décidé, et un décor qui
         * la contournerait ne ressemblerait plus au produit — ce banc d'essai
         * éprouverait alors une fabrication qui n'existe nulle part.
         */
        $story->state->transitionTo(Recorded::class, AnswerType::Audio);
        $story->refresh()->state->transitionTo(Transcribed::class);

        app(RecordShareDecision::class)->handle($story->refresh(), ShareDecision::Share);

        $story->refresh()->state->transitionTo(Validated::class, ValidatedVia::RecordingEnd);
        $story->refresh()->state->transitionTo(Shared::class);

        $story->refresh()->forceFill(['title' => 'Le métier de ma mère'])->save();

        return $story->refresh();
    }

    /**
     * Le proche, **réinvité** à chaque appel de l'étape 1.
     *
     * La première version réutilisait un proche déjà là et se contentait de
     * lui refaire un lien. Le décor paraissait bon — deux adresses
     * s'affichaient — mais aucun courriel ne repartait : on suivait l'étape 1,
     * on ouvrait Mailpit, et on n'y trouvait rien. Un banc d'essai qui
     * n'envoie pas ce qu'il annonce fait chercher un défaut là où il n'y en a
     * pas.
     *
     * `ReissueFamilyLink` ne notifie personne, et c'est juste : dans le
     * produit, l'Initiateur·rice copie le lien et le transmet elle-même. Le
     * seul geste qui envoie vraiment un courriel est l'invitation — alors on
     * la rejoue, en repartant de zéro.
     */
    private function invited(Project $project, string $nom, string $email, string $lien, bool $contribue): FamilyMember
    {
        $project->familyMembers()
            ->where('email', $email)
            ->get()
            ->each(fn (FamilyMember $ancien): mixed => $ancien->forceDelete());

        $membre = app(InviteFamilyMember::class)->handle($project, $project->owner, [
            'display_name' => $nom,
            'relationship' => $lien,
            'email' => $email,
            'can_ask' => $contribue,
        ]);

        return $membre;
    }

    private function reissue(FamilyMember $membre): string
    {
        return Links::listen(app(ReissueFamilyLink::class)->handle($membre)->plain);
    }

    private function project(): ?Project
    {
        $owner = User::query()->where('email', self::OWNER_EMAIL)->first();

        return $owner === null
            ? null
            : Project::query()->where('owner_user_id', $owner->id)->latest('created_at')->first();
    }

    private function reactionCount(Project $project): int
    {
        return (int) $project->stories()->withCount('reactions')->get()->sum('reactions_count');
    }

    private function sharedQuestion(Project $project): string
    {
        return (string) $project->stories()->whereNotNull('shared_at')->first()?->questionText();
    }

    private function privateQuestion(Project $project): string
    {
        return (string) $project->stories()->whereNull('shared_at')->first()?->questionText();
    }

    private function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }

    private function title(string $titre): void
    {
        $this->newLine();
        $this->line("<fg=yellow;options=bold>── Parcours d’un proche invité — {$titre}</>");
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
