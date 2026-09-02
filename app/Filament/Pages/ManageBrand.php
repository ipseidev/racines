<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\UpdateBrandSettings;
use App\Models\User;
use App\Settings\BrandSettings;
use App\Support\Contrast;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Édition de la marque : nom, domaine des liens, couleurs, typographie.
 *
 * L'enregistrement passe par UpdateBrandSettings, qui porte les deux règles
 * non négociables : expéditeur SMS acceptable par les opérateurs, et contraste
 * suffisant pour rester lisible par un narrateur de 85 ans.
 */
final class ManageBrand extends SettingsPage
{
    protected static string $settings = BrandSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    public static function getNavigationLabel(): string
    {
        return __('admin.brand.title');
    }

    public function getTitle(): string
    {
        return __('admin.brand.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('brand.manage');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.brand.identity'))
                ->description(__('admin.brand.identity_help'))
                ->schema([
                    TextInput::make('product_name')
                        ->label(__('admin.brand.product_name'))
                        ->required()->minLength(2)->maxLength(60),
                    TextInput::make('short_name')
                        ->label(__('admin.brand.short_name'))
                        ->required()->minLength(2)->maxLength(30),
                    TextInput::make('tagline')
                        ->label(__('admin.brand.tagline'))
                        ->maxLength(200)->columnSpanFull(),
                    TextInput::make('legal_entity')->label(__('admin.brand.legal_entity'))->maxLength(120),
                    TextInput::make('legal_address')->label(__('admin.brand.legal_address'))->maxLength(255),
                ])->columns(2),

            Section::make(__('admin.brand.contacts'))
                ->description(__('admin.brand.contacts_help'))
                ->schema([
                    TextInput::make('links_domain')
                        ->label(__('admin.brand.links_domain'))
                        ->helperText(__('admin.brand.links_domain_help'))
                        ->required()->maxLength(120),
                    TextInput::make('support_email')
                        ->label(__('admin.brand.support_email'))
                        ->email()->required()->maxLength(120),
                    TextInput::make('support_phone')
                        ->label(__('admin.brand.support_phone'))
                        ->tel()->maxLength(30),
                    TextInput::make('sms_sender_id')
                        ->label(__('admin.brand.sms_sender_id'))
                        ->helperText(__('admin.brand.sms_sender_id_help'))
                        ->required()->maxLength(11),
                ])->columns(2),

            Section::make(__('admin.brand.colors'))
                ->description(__('admin.brand.colors_help'))
                ->schema([
                    ColorPicker::make('color_primary')->label(__('admin.brand.color_primary'))->required(),
                    ColorPicker::make('color_primary_foreground')->label(__('admin.brand.color_primary_foreground'))->required(),
                    ColorPicker::make('color_accent')->label(__('admin.brand.color_accent'))->required(),
                    ColorPicker::make('color_accent_foreground')->label(__('admin.brand.color_accent_foreground'))->required(),
                    ColorPicker::make('color_background')->label(__('admin.brand.color_background'))->required(),
                    ColorPicker::make('color_surface')->label(__('admin.brand.color_surface'))->required(),
                    ColorPicker::make('color_text')->label(__('admin.brand.color_text'))->required(),
                    ColorPicker::make('color_muted')->label(__('admin.brand.color_muted'))->required(),
                ])->columns(2),

            Section::make(__('admin.brand.typography'))
                ->schema([
                    Select::make('font_display')
                        ->label(__('admin.brand.font_display'))
                        ->options(self::DISPLAY_FONTS)->required(),
                    Select::make('font_body')
                        ->label(__('admin.brand.font_body'))
                        ->options(self::BODY_FONTS)->required(),
                ])->columns(2),
        ]);
    }

    /**
     * Un seul verbe dans toute l'application : « Enregistrer ».
     */
    public function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label(__('common.actions.save'));
    }

    /**
     * Toute la validation vit dans l'action, pour que l'administration et le
     * reste du code appliquent exactement les mêmes règles.
     */
    public function save(): void
    {
        $data = $this->form->getState();

        try {
            app(UpdateBrandSettings::class)->handle($data);
        } catch (ValidationException $exception) {
            $errors = [];

            foreach ($exception->errors() as $field => $messages) {
                $errors["data.{$field}"] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }

        Notification::make()
            ->success()
            ->title(__('admin.brand.saved'))
            ->body(__('admin.brand.saved_help'))
            ->send();
    }

    /**
     * Rapports de contraste courants, affichés pour que l'écart soit visible
     * avant l'enregistrement.
     *
     * @return array<int, array{label: string, ratio: float, readable: bool}>
     */
    public function contrastReport(): array
    {
        $data = $this->data ?? [];
        $brand = app(BrandSettings::class);
        $report = [];

        foreach (BrandSettings::contrastPairs() as [$foreground, $background, $label]) {
            $report[] = [
                'label' => $label,
                'ratio' => $ratio = Contrast::ratio(
                    (string) ($data[$foreground] ?? $brand->{$foreground}),
                    (string) ($data[$background] ?? $brand->{$background}),
                ),
                'readable' => $ratio >= Contrast::AA_NORMAL_TEXT,
            ];
        }

        return $report;
    }

    /** @var array<string, string> */
    private const DISPLAY_FONTS = [
        'Fraunces' => 'Fraunces',
        'Newsreader' => 'Newsreader',
    ];

    /** @var array<string, string> */
    private const BODY_FONTS = [
        'Inter' => 'Inter',
        'Instrument Sans' => 'Instrument Sans',
    ];
}
