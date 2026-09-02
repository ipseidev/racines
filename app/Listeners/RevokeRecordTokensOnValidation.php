<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\TokenType;
use App\Models\Story;
use App\Services\Tokens\TokenService;
use App\States\Story\Validated;
use Spatie\ModelStates\Events\StateChanged;

/**
 * Une histoire validée ferme son lien d'enregistrement.
 *
 * Le glossaire §4 le dit : un jeton `record` est invalidé par le passage à
 * `validated`. Ce n'est pas un détail d'hygiène — c'est ce qui empêche
 * quiconque détient encore le lien de réenregistrer par-dessus une histoire
 * que le narrateur a déjà validée.
 */
final readonly class RevokeRecordTokensOnValidation
{
    public function __construct(private TokenService $tokens) {}

    public function handle(StateChanged $event): void
    {
        if (! $event->model instanceof Story) {
            return;
        }

        if (! $event->finalState instanceof Validated) {
            return;
        }

        $this->tokens->revokeAllFor($event->model, TokenType::Record, 'story_validated');
    }
}
