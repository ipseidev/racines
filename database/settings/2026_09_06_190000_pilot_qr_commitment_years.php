<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * La durée d'engagement des QR imprimés (D-8), en années.
 *
 * Dix ans par défaut : la décision D-8 n'est pas prise, et il fallait une
 * valeur imprimable dès le premier BAT. Ce qui compte n'est pas le nombre
 * mais le fait qu'il y en ait un — R-11 interdit « pour toujours » et
 * « QR autonomes », et une durée chiffrée est ce qui les remplace.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pilot.qr_commitment_years', 10);
    }

    public function down(): void
    {
        $this->migrator->delete('pilot.qr_commitment_years');
    }
};
