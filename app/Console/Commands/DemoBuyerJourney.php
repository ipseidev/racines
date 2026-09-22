<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\FulfillOrder;
use App\Enums\Channel;
use App\Enums\TokenType;
use App\Models\CheckoutDraft;
use App\Models\Order;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use App\Services\Tokens\TokenService;
use App\States\Story\Proposed;
use App\Support\Links;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Le parcours de celle qui **offre** le cadeau, bout à bout.
 *
 * `demo:cadeau` montre l'autre moitié — ce que reçoit la personne à qui on
 * offre. Celle-ci part du moment où l'on vient de payer, et suit ce que
 * l'acheteuse voit ensuite : la page de merci, son espace vide, l'annonce qui
 * part, l'attente, l'acceptation, la première question, puis la première
 * histoire qu'elle ne peut pas encore écouter.
 *
 * **Le paiement passe par le vrai chemin, sans Stripe.** `FulfillOrder` est
 * l'action que le webhook appelle ; on lui donne la même forme de session,
 * avec un identifiant de démonstration. Fabriquer la commande à la main
 * reviendrait à éprouver une fabrication qui n'existe nulle part ailleurs —
 * et c'est précisément le chemin qui a caché deux défauts au bloc 10 (T-167,
 * T-169).
 *
 * **Ce que la commande n'avance jamais toute seule** : l'acceptation. Elle
 * appartient à la narratrice, elle est définitive, et la sauter ferait de ce
 * banc d'essai une mise en scène. Le lien s'imprime, l'humain clique.
 *
 * Quatre étapes, parce qu'un humain regarde son espace entre chacune.
 */
#[AsCommand(name: 'demo:offrir', description: 'Le parcours de celle qui offre : du paiement à la première histoire')]
final class DemoBuyerJourney extends Command
{
    /** @var string */
    protected $signature = 'demo:offrir
        {--prenom= : Le prénom de la personne à qui le cadeau est offert}
        {--annonce : Étape 2 — l’annonce part maintenant, sans attendre la date choisie}
        {--question : Étape 3 — la première question part, sans attendre le lendemain}
        {--histoire : Étape 4 — ce qu’il reste à faire au navigateur, et ce que l’acheteuse doit voir}';

    /** @var string */
    protected $description = 'Le parcours de celle qui offre : du paiement à la première histoire';

    /**
     * Le compte de l'acheteuse, **stable** d'un appel à l'autre.
     *
     * Un compte neuf à chaque fois obligerait à se reconnecter à chaque
     * étape, et l'espace se lit justement dans la continuité. Chaque appel
     * sans option fabrique en revanche un projet neuf : `InitiatorProject`
     * prend le plus récent, et l'ancien reste consultable en base.
     */
    private const BUYER_EMAIL = 'offrir@example.test';

    private const BUYER_NAME = 'Camille Laurent';

    private const GIFT_MESSAGE = 'Maman, je voudrais garder tes histoires — celles que tu racontes toujours, et celles qu’on ne t’a jamais demandées.';

    /** @var list<string> */
    private const PRENOMS = ['Suzanne', 'Odette', 'Germaine', 'Lucienne', 'Yvette', 'Paulette'];

    public function handle(TokenService $tokens): int
    {
        if (app()->isProduction()) {
            $this->components->error('Cette commande ne touche qu’un décor de démonstration. Jamais en production.');

            return self::FAILURE;
        }

        return match (true) {
            $this->option('histoire') === true => $this->firstStory(),
            $this->option('question') === true => $this->firstQuestion(),
            $this->option('annonce') === true => $this->announce($tokens),
            default => $this->paid(),
        };
    }

    /**
     * Étape 1 : le paiement vient de passer.
     *
     * Le brouillon est celui du tunnel, et la session a la forme que Stripe
     * envoie : `FulfillOrder` ne fait donc aucune différence entre ce banc
     * d'essai et un vrai achat. Le projet naît en `draft`, passe en
     * `awaiting_acceptance` quand l'annonce part, et ne devient `active`
     * qu'une fois acceptée : rien ne s'envoie avant, c'est l'invariant du
     * bloc 10.
     */
    private function paid(): int
    {
        $prenom = (string) ($this->option('prenom') ?: self::PRENOMS[array_rand(self::PRENOMS)]);
        $email = mb_strtolower($prenom).'+'.mb_strtolower(Str::random(6)).'@example.test';

        $buyer = User::query()->firstOrCreate(
            ['email' => self::BUYER_EMAIL],
            [
                'name' => self::BUYER_NAME,
                'password' => Hash::make((string) config('product.seeding.admin_password')),
            ],
        );

        /*
         * La vérification se pose **après** la création, et pas dans le
         * tableau ci-dessus : `email_verified_at` n'est pas dans le
         * `Fillable` de `User`, donc l'assignation de masse l'écarte en
         * silence. Le compte naissait non vérifié, et tout l'espace est
         * derrière `verified` : la connexion réussissait et la personne
         * tombait sur la page de vérification, sans comprendre pourquoi.
         */
        if ($buyer->email_verified_at === null) {
            $buyer->forceFill(['email_verified_at' => now()])->save();
        }

        $draft = new CheckoutDraft([
            'step' => 6,
            'payload' => [
                'for' => 'relative',
                'narrator_first_name' => $prenom,
                'narrator_email' => $email,
                'preferred_channel' => Channel::Email->value,
                'address_form' => 'vous',
                'narrator_tech_comfort' => 'rarely',
                /*
                 * Demain, et non aujourd'hui.
                 *
                 * `FulfillOrder` envoie l'annonce sur-le-champ quand la date
                 * choisie est déjà là — c'est juste, et c'est ce que fait un
                 * achat « à offrir maintenant ». Mais l'étape 1 de ce banc
                 * d'essai existe pour montrer l'espace **avant** que rien
                 * ne parte : le projet en attente, la date annoncée, aucune
                 * question. Une journée d'écart suffit, et c'est le cas le
                 * plus courant — on achète un jour, le cadeau arrive un
                 * autre.
                 */
                'gift_send_at' => now()->addDay()->toDateString(),
                'gift_send_time' => '09:00',
                'gift_message' => self::GIFT_MESSAGE,
                'extra_copies' => 0,
                'accepts_terms' => true,
            ],
            'expires_at' => now()->addDays(7),
        ]);
        $draft->user()->associate($buyer);
        $draft->save();

        $order = app(FulfillOrder::class)->handle([
            'id' => 'cs_demo_'.Str::random(24),
            'amount_subtotal' => 8900,
            'amount_total' => 8900,
            'currency' => 'eur',
            'metadata' => ['draft_id' => $draft->id, 'user_id' => (string) $buyer->id],
        ]);

        if (! $order instanceof Order) {
            $this->components->error('La commande n’a pas été créée : voir `checkout.*` dans les journaux.');

            return self::FAILURE;
        }

        $project = $order->project;

        // Une commande honorée sans projet n'existe pas, et si cela arrivait
        // le banc d'essai doit le dire plutôt qu'imprimer un tiret : c'est le
        // genre de silence qui fait chercher le défaut ailleurs.
        if (! $project instanceof Project) {
            $this->components->error('La commande existe mais aucun projet n’y est rattaché : voir `checkout.*` dans les journaux.');

            return self::FAILURE;
        }

        $this->title('étape 1 sur 4 : le paiement vient de passer');
        $this->components->twoColumnDetail('Acheteuse', self::BUYER_NAME.' <fg=gray>'.self::BUYER_EMAIL.'</>');
        $this->components->twoColumnDetail('Offert à', $prenom.' <fg=gray>'.$email.'</>');
        $this->components->twoColumnDetail('Commande', $order->id.' <fg=gray>'.number_format($order->total_cents / 100, 2, ',', ' ').' €</>');
        $this->components->twoColumnDetail('Projet', $project->id.' <fg=gray>'.$project->status->value.'</>');
        $this->newLine();

        $this->step(1, 'Le reçu, dans Mailpit. C’est le premier courriel qu’elle reçoit, et le seul avant que la personne réponde.');
        $this->link('http://localhost:8027');

        $this->step(2, 'Se connecter à son espace. Le mot de passe est celui du décor.');
        $this->link($this->url('/espace'));
        $this->note(self::BUYER_EMAIL.' / '.(string) config('product.seeding.admin_password'));

        $this->step(3, 'Ce qu’elle y voit **maintenant** : « En préparation », aucune question, aucune histoire. Rien ne s’envoie avant que la personne ait accepté.');
        $this->note('à regarder de près : la date d’envoi de l’annonce n’est **pas** affichée, et « Envoyer le lien » est proposé alors qu’aucune question n’existe');

        $this->step(4, 'Ses commandes, où vit la rétractation de quatorze jours.');
        $this->link($this->url('/espace/commandes'));

        $this->step(5, 'Puis l’annonce part, sans attendre l’heure choisie :');
        $this->command('demo:offrir --annonce');

        return self::SUCCESS;
    }

    /**
     * Étape 2 : l'annonce part.
     *
     * On avance l'heure d'envoi, et c'est tout : `gifts:dispatch-due` fait le
     * reste — c'est la commande qui tourne en production, et l'envelopper
     * reviendrait à éprouver l'enveloppe (conventions §6).
     */
    private function announce(TokenService $tokens): int
    {
        $project = $this->currentProject();

        if (! $project instanceof Project) {
            return self::FAILURE;
        }

        if ($project->gift_sent_at === null) {
            $project->gift_send_at = now()->subMinute();
            $project->save();

            $this->call('gifts:dispatch-due');
            $project->refresh();
        }

        /*
         * Le lien en clair n'existe qu'entre son émission et son envoi : il
         * ne se relit pas en base (invariant du bloc 03), et le journal le
         * masque à dessein (`RedactTokens`). On en émet donc un second, du
         * même type et de la même portée que celui qui vient de partir par
         * courriel — les deux ouvrent la même annonce.
         *
         * Rejouer le job ne convenait pas : il refuse un second envoi quand
         * `gift_sent_at` est posé, et c'est exactement ce qu'on attend de lui
         * — une annonce envoyée deux fois est une annonce de trop dans la
         * vie d'une famille.
         */
        $narrator = $project->primaryNarrator;

        $plain = $narrator === null ? null : $tokens->issue(
            TokenType::Invitation,
            $project,
            ['opt_in'],
            now()->addDays(30),
            $project->owner,
            issuedTo: $narrator,
        )->plain;

        $this->title('étape 2 sur 4 : l’annonce est partie');
        $this->components->twoColumnDetail('Offert à', (string) $project->primaryNarrator?->first_name);
        /*
         * « Mise en file », et non une date : `gifts:dispatch-due` place
         * `SendGiftInvitation` sur la file, et c'est Horizon qui envoie.
         * Relire `gift_sent_at` dans la foulée le trouve souvent encore nul —
         * imprimer un champ vide ferait chercher une panne là où il n'y a
         * qu'une seconde d'écart.
         */
        $this->components->twoColumnDetail(
            'Annonce',
            $project->gift_sent_at === null
                ? 'mise en file <fg=gray>— Horizon l’envoie dans la seconde</>'
                : 'envoyée '.$project->gift_sent_at->translatedFormat('j F à H\hi'),
        );
        $this->newLine();

        $this->step(6, 'Côté acheteuse : son espace dit maintenant que l’annonce est partie, et qu’on attend la réponse. Toujours aucune question.');
        $this->link($this->url('/espace'));

        $this->step(7, 'Côté personne à qui on offre — à ouvrir dans une fenêtre privée, pour ne pas mélanger les deux points de vue :');
        $this->link($plain === null ? '(lien non réémis : voir `gift.*` dans les journaux)' : Links::invitation($plain));
        $this->note('accepter est **définitif** : la commande ne le fait jamais à sa place');

        $this->step(8, 'Un refus se teste aussi, et c’est le cas qu’on oublie : l’acheteuse reçoit un message avec tact, et les coordonnées sont effacées. Pour cela, reprendre un cadeau neuf.');
        $this->command('demo:offrir');

        $this->step(9, 'Une fois acceptée, la première question part sans attendre la nuit :');
        $this->command('demo:offrir --question');

        return self::SUCCESS;
    }

    /** Étape 3 : la première question part. */
    private function firstQuestion(): int
    {
        $project = $this->currentProject();

        if (! $project instanceof Project) {
            return self::FAILURE;
        }

        if ($project->accepted_at === null) {
            $this->components->error('L’annonce n’a pas encore été acceptée : aucune question ne peut partir.');
            $this->line('  <fg=gray>C’est l’invariant du bloc 10, et la commande le dit plutôt que de le contourner.</>');
            $this->line('  <fg=gray>Ouvrez le lien de l’étape 7, ou reprenez un cadeau neuf : sail artisan demo:offrir</>');

            return self::FAILURE;
        }

        $story = $this->pendingStory($project);

        if (! $story instanceof Story) {
            $project->next_prompt_at = now()->subMinute();
            $project->save();

            $this->call('prompts:dispatch-due');
            $story = $this->pendingStory($project->refresh());
        }

        if (! $story instanceof Story) {
            $this->components->error('Aucune question n’est partie : corpus épuisé, ou projet en pause. Voir `prompt.*` dans les journaux.');

            return self::FAILURE;
        }

        $this->title('étape 3 sur 4 : la première question est partie');
        $this->components->twoColumnDetail('Question', '<fg=gray>« '.(string) $story->questionText().' »</>');
        $this->components->twoColumnDetail('Prochaine', (string) $project->next_prompt_at?->translatedFormat('j F à H\hi'));
        $this->newLine();

        $this->step(10, 'Côté acheteuse : la question en cours apparaît, avec le bouton pour renvoyer le lien. Elle ne voit **pas** le contenu — il n’y en a pas encore.');
        $this->link($this->url('/espace'));

        $this->step(11, 'Ses questions : en réordonner deux, en exclure une, en écrire une à elle. L’ordre doit tenir après rechargement.');
        $this->link($this->url('/espace/questions'));

        $this->step(12, 'Joindre une photo à la question en cours, depuis le tableau de bord (T-251) : la narratrice la verra **avec** la question, pas après.');

        $this->step(13, 'Inviter un proche : le courriel part dans Mailpit avec son lien d’écoute.');
        $this->link($this->url('/espace/proches'));

        $this->step(14, 'Puis ce qu’il reste : l’enregistrement, et ce que l’acheteuse en voit.');
        $this->command('demo:offrir --histoire');

        return self::SUCCESS;
    }

    /**
     * Étape 4 : elle raconte, et l'acheteuse ne l'entend pas.
     *
     * Cette étape **n'avance rien** : personne ne peut raconter à la place
     * de la narratrice, et un décor qui fabriquerait un faux enregistrement
     * montrerait un produit qui n'existe pas. Elle dit donc ce qu'il reste à
     * faire à la main, et surtout ce qu'il faut regarder ensuite.
     *
     * C'est **le** point du parcours qui se vérifie mal en lisant du code, et
     * celui qui compte le plus : une histoire enregistrée mais pas validée
     * n'est visible de personne d'autre que la narratrice, pas même de celle
     * qui a payé. L'écran doit le dire sans avoir l'air d'une panne.
     */
    private function firstStory(): int
    {
        $project = $this->currentProject();

        if (! $project instanceof Project) {
            return self::FAILURE;
        }

        $story = $this->pendingStory($project);

        if (! $story instanceof Story) {
            $this->components->error('Aucune question en attente. Étape 3 d’abord : sail artisan demo:offrir --question');

            return self::FAILURE;
        }

        $this->title('étape 4 sur 4 : elle raconte, et l’acheteuse n’entend rien');
        $this->components->twoColumnDetail('Question', '<fg=gray>« '.(string) $story->questionText().' »</>');
        $this->newLine();

        $this->step(15, 'Enregistrer pour de vrai depuis ce lien — un navigateur est nécessaire, et le décor ne peut pas parler à sa place :');
        $this->link($this->url('/espace'));
        $this->note('le lien d’enregistrement se réémet depuis l’espace, bouton « Renvoyer le lien »');

        $this->step(16, 'Ce que l’acheteuse doit voir ensuite, et qui est tout l’enjeu : l’histoire **existe**, elle est datée, et elle n’est **pas écoutable**. Rien n’est visible avant que la narratrice ait validé — pas même pour celle qui a payé.');

        $this->step(17, 'Le silence, enfin : ce que le moteur envoie quand rien n’arrive pendant trois semaines.');
        $this->command('demo:moteur');

        return self::SUCCESS;
    }

    /** Le projet le plus récent de l'acheteuse — celui que son espace montre. */
    private function currentProject(): ?Project
    {
        $project = Project::query()
            ->whereHas('owner', fn ($query) => $query->where('email', self::BUYER_EMAIL))
            ->with('primaryNarrator')
            ->latest('created_at')
            ->first();

        if (! $project instanceof Project) {
            $this->components->error('Aucun achat en cours. Commencez par le paiement : sail artisan demo:offrir');

            return null;
        }

        if ($project->refused_at !== null) {
            $this->components->error('Cette annonce a été refusée, et un refus se respecte. Un achat neuf : sail artisan demo:offrir');

            return null;
        }

        return $project;
    }

    private function pendingStory(Project $project): ?Story
    {
        $story = $project->stories()
            ->where('state', Proposed::$name)
            ->orderByDesc('sequence')
            ->first();

        return $story instanceof Story ? $story : null;
    }

    /** L'adresse d'une page de l'espace, sur le domaine du décor. */
    private function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }

    private function title(string $titre): void
    {
        $this->newLine();
        $this->line("<fg=yellow;options=bold>── Parcours de celle qui offre — {$titre}</>");
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
