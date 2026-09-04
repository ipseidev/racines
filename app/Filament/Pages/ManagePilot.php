<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Audit\AuditLog;
use App\Models\User;
use App\Settings\PilotSettings;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Les réglages du pilote : mode, prix, plafond, validation juridique.
 *
 * Deux champs méritent d'être regardés deux fois avant d'être touchés.
 *
 * `mode` gouverne ce que la page d'accueil annonce et ce que le tunnel vend.
 * Le passer à `prevente` change le prix affiché à tous les visiteurs.
 *
 * `legal_validated_at` **atteste** la relecture des textes publics par un
 * conseil. C'est un acte, pas une case de confort : le poser sans que le
 * conseil ait relu inscrirait une validation fausse, et `golive:check` s'y
 * fiera au bloc 17. Les pages n'affichent plus de bandeau d'attente (T-145).
 */
final class ManagePilot extends SettingsPage
{
    protected static string $settings = PilotSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    public static function getNavigationLabel(): string
    {
        return __('admin.pilot.title');
    }

    public function getTitle(): string
    {
        return __('admin.pilot.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Les prix et le mode de vente ne sont pas des réglages de support.
        return $user instanceof User && $user->can('brand.manage');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.pilot.offer'))
                ->description(__('admin.pilot.offer_help'))
                ->schema([
                    Select::make('mode')
                        ->label(__('admin.pilot.mode'))
                        ->options([
                            'pilot' => __('admin.pilot.mode_pilot'),
                            'prevente' => __('admin.pilot.mode_prevente'),
                            'core' => __('admin.pilot.mode_core'),
                        ])
                        ->required(),
                    TextInput::make('cohort_id')
                        ->label(__('admin.pilot.cohort'))
                        ->maxLength(36),
                ])
                ->columns(2),

            Section::make(__('admin.pilot.prices'))
                ->description(__('admin.pilot.prices_help'))
                ->schema([
                    TextInput::make('pilot_price_cents')
                        ->label(__('admin.pilot.pilot_price'))
                        ->numeric()->required()->minValue(0),
                    TextInput::make('extra_copy_price_cents')
                        ->label(__('admin.pilot.extra_copy_price'))
                        ->helperText(__('admin.pilot.extra_copy_price_help'))
                        ->numeric()->required()->minValue(0),
                    TextInput::make('phone_option_price_cents')
                        ->label(__('admin.pilot.phone_option_price'))
                        ->numeric()->required()->minValue(0),
                    TextInput::make('ebook_price_cents')
                        ->label(__('admin.pilot.ebook_price'))
                        ->numeric()
                        ->required(),
                    TextInput::make('ebook_regular_price_cents')
                        ->label(__('admin.pilot.ebook_regular_price'))
                        ->helperText(__('admin.pilot.ebook_regular_price_help'))
                        ->numeric()
                        ->required(),
                    TextInput::make('phone_option_cap')
                        ->label(__('admin.pilot.phone_option_cap'))
                        ->helperText(__('admin.pilot.phone_option_cap_help'))
                        ->numeric()->required()->minValue(0)->maxValue(200),
                    TextInput::make('gift_send_hour')
                        ->label(__('admin.pilot.gift_send_hour'))
                        ->numeric()->required()->minValue(0)->maxValue(23),
                ])
                ->columns(2),

            Section::make(__('admin.pilot.welcome_offer'))
                ->description(__('admin.pilot.welcome_offer_help'))
                ->schema([
                    Toggle::make('welcome_offer_enabled')
                        ->label(__('admin.pilot.welcome_offer_enabled')),
                    TextInput::make('welcome_offer_discount_percent')
                        ->label(__('admin.pilot.welcome_offer_discount'))
                        ->helperText(__('admin.pilot.welcome_offer_discount_help'))
                        ->numeric()->required()->minValue(0)->maxValue(100)->suffix('%'),
                ])
                ->columns(2),

            Section::make(__('admin.pilot.legal'))
                ->description(__('admin.pilot.legal_help'))
                ->schema([
                    DateTimePicker::make('legal_validated_at')
                        ->label(__('admin.pilot.legal_validated_at'))
                        ->helperText(__('admin.pilot.legal_validated_at_help'))
                        ->seconds(false),
                ]),
        ]);
    }

    /**
     * L'enregistrement laisse une trace.
     *
     * Changer un prix ou dater la validation juridique sont des actes qu'on doit
     * pouvoir dater et attribuer. Les valeurs partent au journal ; ce sont des
     * réglages, pas des données personnelles.
     */
    protected function afterSave(): void
    {
        AuditLog::record('edited PilotSettings', null, [
            'changed' => array_keys($this->form->getState()),
            'mode' => app(PilotSettings::class)->mode,
        ]);
    }
}
