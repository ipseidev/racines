<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Rôles, permissions et textes de consentement sont des données de
     * référence : sans elles, aucune autorisation n'a de sens. Les seeders de
     * démonstration, eux, restent hors des tests.
     *
     * @var class-string<Seeder>
     */
    protected $seeder = ReferenceDataSeeder::class;

    protected bool $seed = true;

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
