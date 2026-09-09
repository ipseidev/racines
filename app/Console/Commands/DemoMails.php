<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Channel;
use App\Enums\TokenType;
use App\Models\Book;
use App\Models\FamilyMember;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Story;
use App\Notifications\BookNotification;
use App\Notifications\FamilyInvitationNotification;
use App\Notifications\GiftInvitationNotification;
use App\Notifications\InvitationRefusedNotification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\OtpCodeNotification;
use App\Notifications\PromptNotification;
use App\Notifications\ReviewReadyNotification;
use App\Notifications\WelcomeOfferNotification;
use App\Services\Tokens\TokenService;
use Closure;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Tous les courriels du produit, rendus d'un coup, pour les relire.
 *
 * Le gabarit des courriels est un dessin (T-237), et un dessin se relit à
 * l'œil, dans un vrai client de messagerie — ce qu'aucun test ne remplace.
 * Cette commande fabrique un décor jetable (une narratrice, un projet, une
 * commande, un code), rend chaque courriel comme il partirait, écrit le HTML
 * dans un dossier, et avec `--envoyer` le poste par le mailer configuré : en
 * local, Mailpit. Le décor vit dans une transaction annulée — la base locale
 * n'en garde rien, et les envois ne laissent aucune trace de livraison, parce
 * qu'ils ne passent pas par le canal suivi : ce sont des aperçus, pas des
 * messages.
 *
 * Des fabriques plutôt que le décor semé, parce qu'il faut un cas de chaque
 * et que le semis n'en a pas : une commande confirmée, un refus, un code de
 * bienvenue avec la case des nouvelles cochée.
 */
#[AsCommand(name: 'demo:courriels', description: 'Rend chaque courriel du produit sur un décor jetable, et le poste dans Mailpit avec --envoyer')]
final class DemoMails extends Command
{
    /** @var string */
    protected $signature = 'demo:courriels
        {--envoyer : Poste aussi chaque courriel par le mailer configuré (Mailpit en local)}
        {--a=apercu@example.test : L’adresse qui reçoit les envois}
        {--dossier= : Où écrire les fichiers HTML ; storage/app/private/courriels par défaut}';

    /** @var string */
    protected $description = 'Rend chaque courriel du produit sur un décor jetable, et le poste dans Mailpit avec --envoyer';

    public function handle(TokenService $tokens): int
    {
        if (app()->isProduction()) {
            $this->components->error('Cette commande fabrique un décor et envoie des aperçus. Jamais en production.');

            return self::FAILURE;
        }

        $directory = (string) ($this->option('dossier') ?: storage_path('app/private/courriels'));
        $recipient = (string) $this->option('a');
        $send = (bool) $this->option('envoyer');

        File::ensureDirectoryExists($directory);

        // Rien ne doit s'échapper du décor : ni une ligne en base, ni un
        // travail en file qui chercherait demain une histoire disparue.
        Bus::fake();
        DB::beginTransaction();

        try {
            foreach ($this->samples($tokens) as $name => $render) {
                $message = $render();
                $subject = (string) $message->subject;
                $html = (string) $message->render();

                File::put("{$directory}/{$name}.html", $html);

                if ($send) {
                    Mail::html($html, function (Message $mail) use ($recipient, $subject): void {
                        $mail->to($recipient, 'Aperçu')->subject($subject);
                    });
                }

                $this->components->twoColumnDetail($name, $subject);
            }
        } finally {
            DB::rollBack();
        }

        $this->newLine();
        $this->components->info("Les fichiers HTML sont dans {$directory}.");

        if ($send) {
            $this->line("  <fg=gray>Les courriels sont partis à {$recipient} par le mailer « ".config('mail.default').' » :</>');
            $this->line('  <fg=gray>en local, Mailpit les montre sur http://localhost:8027.</>');
        } else {
            $this->line('  <fg=gray>Ajoutez --envoyer pour les lire dans Mailpit, tels qu’un client les recevra.</>');
        }

        return self::SUCCESS;
    }

    /**
     * Un cas de chaque courriel, sur un décor cohérent : Odette raconte,
     * Claire Martin lui a offert le livre, Louise écoute.
     *
     * Chaque entrée rend le courriel tel que la notification l'écrirait pour
     * son destinataire : c'est `toMail()` qu'on appelle, rien d'autre.
     *
     * @return array<string, Closure(): MailMessage>
     */
    private function samples(TokenService $tokens): array
    {
        $story = Story::factory()->proposed()->create();

        $narrator = $story->narrator;
        $narrator->forceFill([
            'first_name' => 'Odette',
            'email' => 'odette@example.test',
            'preferred_channel' => Channel::Email,
        ])->save();

        $project = $story->project;
        $project->forceFill([
            'gift_message' => 'Maman, j’aimerais garder ta voix et tes histoires pour Louise et Gabriel. Prends ton temps, raconte comme tu veux.',
            'gift_send_at' => now()->addDays(3)->setTime(9, 0),
        ])->save();

        $owner = $project->owner;
        $owner->forceFill(['name' => 'Claire Martin'])->save();

        $story->refresh();
        $project->refresh();

        $record = $tokens->issue(TokenType::Record, $story)->plain;
        $invitation = $tokens->issue(TokenType::Invitation, $project)->plain;

        $relative = FamilyMember::factory()->create([
            'project_id' => $project->id,
            'invited_by_user_id' => $owner->id,
            'display_name' => 'Louise',
            'email' => 'louise@example.test',
        ]);
        $listen = $tokens->issue(TokenType::ListenProject, $relative)->plain;

        $order = Order::factory()->paid()->create(['project_id' => $project->id, 'user_id' => $owner->id]);
        $book = Book::factory()->proofing()->create(['project_id' => $project->id]);
        $lead = Lead::factory()->wantsNews()->create();

        return [
            'question' => fn (): MailMessage => (new PromptNotification($story, $record))->toMail($narrator),
            'invitation-cadeau' => fn (): MailMessage => (new GiftInvitationNotification($project, $invitation))->toMail($narrator),
            'relecture' => fn (): MailMessage => (new ReviewReadyNotification($story, $record))->toMail($narrator),
            'invitation-proche' => fn (): MailMessage => (new FamilyInvitationNotification($project, $listen, $owner))->toMail($relative),
            'bon-a-tirer' => fn (): MailMessage => (new BookNotification($book, 'proof_ready'))->toMail($owner),
            'commande-confirmee' => fn (): MailMessage => (new OrderConfirmationNotification($order))->toMail($owner),
            'invitation-refusee' => fn (): MailMessage => (new InvitationRefusedNotification($project))->toMail($owner),
            'code-bienvenue' => fn (): MailMessage => (new WelcomeOfferNotification($lead))->toMail($lead),
            'code-usage-unique' => fn (): MailMessage => (new OtpCodeNotification('482 913', Channel::Email, 10))->toMail($narrator),
            'mot-de-passe' => fn (): MailMessage => (new ResetPassword('jeton-de-demonstration'))->toMail($owner),
        ];
    }
}
