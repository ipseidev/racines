<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TokenType;
use App\Models\Narrator;
use App\Services\Tokens\TokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Efface les coordonnées d'un narrateur qui n'a jamais dit oui.
 *
 * Trente jours après la dernière relance, ou après un refus. On ne garde pas
 * indéfiniment le téléphone et le courriel de quelqu'un qui n'a rien demandé :
 * il les a reçus d'un proche, il n'a pas choisi de nous les confier.
 *
 * La ligne `narrators` reste, avec son prénom : elle porte l'histoire du
 * projet, et l'Initiateur·rice doit pouvoir comprendre ce qui s'est passé.
 * Ce qui part, c'est ce qui permettrait de recontacter la personne.
 */
final class DeleteUnacceptedContacts extends Command
{
    protected $signature = 'narrators:delete-unaccepted-contacts';

    protected $description = 'Efface les coordonnées des narrateurs qui n’ont pas accepté';

    public function handle(TokenService $tokens): int
    {
        $due = Narrator::query()
            ->whereNotNull('contact_deletion_due_at')
            ->where('contact_deletion_due_at', '<=', now())
            ->whereNull('opted_in_at')
            ->get();

        foreach ($due as $narrator) {
            $narrator->forceFill([
                'email' => null,
                'phone_e164' => null,
                'contact_deletion_due_at' => null,
                // La date accompagne l'effacement : « quand ces coordonnées
                // ont-elles été effacées ? » est exactement ce qu'une demande
                // RGPD demande, et c'est aussi ce qui lève la contrainte
                // `narrators_reachable_check` (T-109).
                'contact_deleted_at' => now(),
            ])->save();

            // Les liens aussi : un jeton qui survit à la suppression du
            // contact serait une porte laissée ouverte. Ceux d'espace
            // narrateur portent la personne ; l'invitation, elle, porte le
            // projet — d'où les deux appels.
            $tokens->revokeAllFor($narrator, TokenType::NarratorSpace, 'contact_deleted');
            $tokens->revokeAllFor($narrator->project, TokenType::Invitation, 'contact_deleted');

            Log::warning('narrator.contact_deleted', [
                'narrator_id' => $narrator->id,
                'project_id' => $narrator->project_id,
            ]);
        }

        $this->components->info(sprintf('%d coordonnée(s) effacée(s).', $due->count()));

        return self::SUCCESS;
    }
}
