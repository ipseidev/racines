<?php

declare(strict_types=1);

namespace App\Engine\Actions;

use App\Actions\OpenSupportTicket;
use App\Enums\SupportTicketKind;
use App\Models\AccessToken;
use App\Models\Project;
use Laravel\Pennant\Feature;

/**
 * Proposer l'enregistrement par téléphone.
 *
 * Le dernier recours du moteur, et le plus coûteux : un opérateur appelle le
 * narrateur et l'enregistre. Deux verrous avant d'y arriver — le drapeau
 * `phone-option-offer`, et le plafond du pilote. Sans eux, une promesse
 * humaine se retrouverait faite à plus de familles qu'on ne peut en rappeler,
 * et une promesse tenue à moitié vaut moins qu'une promesse jamais faite.
 *
 * La ligne `phone_options` arrive au bloc 10 avec sa table ; ici on ouvre le
 * ticket que le support traitera (écart T-98).
 */
final readonly class OfferPhoneOption implements OneTapAction
{
    public const FLAG = 'phone-option-offer';

    public function __construct(private OpenSupportTicket $tickets) {}

    public static function name(): string
    {
        return 'offer_phone_option';
    }

    /** @return array<string, mixed> */
    public function preview(AccessToken $token): array
    {
        return [
            'title' => __('initiator.one_tap.offer_phone_option.title'),
            'body' => __('initiator.one_tap.offer_phone_option.body'),
            'button' => __('initiator.one_tap.offer_phone_option.button'),
        ];
    }

    /** @return array<string, mixed> */
    public function execute(AccessToken $token): array
    {
        $project = $token->subject;

        if (! $project instanceof Project) {
            return ['done' => false];
        }

        if (! self::isAvailableFor($project)) {
            // On le dit franchement plutôt que de laisser espérer : « nous
            // vous rappelons » qu'on ne tiendrait pas serait pire que rien.
            return [
                'done' => false,
                'message' => __('initiator.one_tap.offer_phone_option.unavailable'),
            ];
        }

        $ticket = $this->tickets->handle(
            $project,
            SupportTicketKind::PhoneOptionRequested,
            null,
            ['entry' => 'rescue'],
        );

        return [
            'done' => true,
            'message' => __('initiator.one_tap.offer_phone_option.done'),
            'support_ticket_id' => $ticket->id,
        ];
    }

    public static function isAvailableFor(Project $project): bool
    {
        return Feature::for($project)->active(self::FLAG);
    }
}
