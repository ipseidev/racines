<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Le pictogramme de la marque, posé à côté du nom dans les en-têtes.
     *
     * Distinct de `logo_path`, qui remplace le nom : ici les deux cohabitent,
     * le dessin puis le mot. Nul par défaut, et c'est le fichier livré dans
     * public/ (config `brand.mark`) qui s'affiche tant que l'administration
     * n'en téléverse pas un autre.
     */
    public function up(): void
    {
        $this->migrator->add('brand.mark_path', null);
    }

    public function down(): void
    {
        $this->migrator->delete('brand.mark_path');
    }
};
