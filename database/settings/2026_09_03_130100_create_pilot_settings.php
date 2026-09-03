<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach ($this->values() as $property => $value) {
            $this->migrator->add("pilot.{$property}", $value);
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->values()) as $property) {
            $this->migrator->delete("pilot.{$property}");
        }
    }

    /**
     * Valeurs initiales, reprises de `config/product.php`. Le mode par défaut
     * est `pilot` : les préventes s'activent à la main (règle §9 du bloc 10).
     *
     * @return array<string, mixed>
     */
    private function values(): array
    {
        return [
            'mode' => 'pilot',
            'phone_option_cap' => (int) config('product.pilot.phone_option_cap'),
            'pilot_price_cents' => (int) config('product.pilot.pilot_price_cents'),
            'prevente_prices_cents' => (array) config('product.pilot.prevente_prices_cents'),
            'extra_copy_price_cents' => (int) config('product.pilot.extra_copy_price_cents'),
            'phone_option_price_cents' => (int) config('product.pilot.phone_option_price_cents'),
            'gift_send_hour' => (int) config('product.pilot.gift_send_hour'),
            'cohort_id' => null,
            'legal_validated_at' => null,
        ];
    }
};
