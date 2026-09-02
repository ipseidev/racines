<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ValidatedVia;
use App\Models\Story;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Le narrateur a validé une histoire.
 *
 * L'événement porte **par quel chemin** et **par qui**, parce que les deux
 * comptent : un tap en fin d'enregistrement et une relecture n'ont pas la
 * même valeur de preuve, et une validation par mandat doit rester
 * identifiable dans l'audit (bloc 11) comme dans les KPI (bloc 15).
 */
final class StoryValidated
{
    use Dispatchable;

    public function __construct(
        public readonly Story $story,
        public readonly ValidatedVia $via,
        public readonly ?Model $actor = null,
    ) {}
}
