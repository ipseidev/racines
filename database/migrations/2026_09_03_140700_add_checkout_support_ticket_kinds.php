<?php

declare(strict_types=1);

use App\Enums\SupportTicketKind;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;

/**
 * Deux motifs de ticket ajoutés au bloc 10 : rétractation demandée, et
 * remboursement à proposer quand le narrateur décline.
 *
 * Traités à la main l'un comme l'autre. Il y a une personne au bout, et un
 * virement automatique ne dit pas qu'on a compris.
 */
return new class extends Migration
{
    public function up(): void
    {
        EnumCheck::drop('support_tickets', 'kind');
        EnumCheck::add('support_tickets', 'kind', EnumCheck::of(SupportTicketKind::class));
    }

    public function down(): void
    {
        EnumCheck::drop('support_tickets', 'kind');
        EnumCheck::add('support_tickets', 'kind', [
            'mic_denied_twice', 'phone_option_requested', 'transcription_failed',
        ]);
    }
};
