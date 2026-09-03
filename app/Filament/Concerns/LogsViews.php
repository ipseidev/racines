<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Audit\AuditLog;
use Filament\Resources\Pages\Page;

/**
 * Inscrit au journal d'audit l'ouverture d'une page de consultation.
 *
 * C'est l'exigence du dossier 04 §12 qu'on oublie toujours : « journalisation
 * inviolable de toutes les actions support, **lecture comprise** ». Un support
 * qui ouvre la fiche d'une histoire intime n'a rien modifié — et doit quand
 * même laisser une trace.
 *
 * Le trait s'attache aux pages `View*` et `Edit*` de Filament, et un test
 * parcourt `app/Filament` pour qu'aucune n'y échappe. C'est un critère de
 * sortie du bloc 11 : une page ajoutée plus tard sans ce trait serait un trou
 * dans la seule chose qui rend ce back-office acceptable.
 *
 * @phpstan-require-extends Page
 */
trait LogsViews
{
    /**
     * Filament appelle `mount()` à l'ouverture, une fois par visite.
     *
     * Les navigations Livewire qui suivent — trier une table, ouvrir un
     * onglet — n'écrivent rien : on journalise l'accès à la donnée, pas
     * chaque battement de l'interface.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $model = $this->getRecord();

        AuditLog::record('viewed '.class_basename($model), $model);
    }
}
