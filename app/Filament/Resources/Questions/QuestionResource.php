<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questions;

use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Models\Question;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Le corpus de questions, bien commun de toutes les familles.
 *
 * En lecture ici : le corpus est relu et séquencé, et une question ajoutée à
 * la volée depuis un back-office échapperait à cette relecture. Une famille
 * qui veut sa propre question la pose depuis son espace, où elle devient une
 * histoire à part et ne rejoint pas le corpus.
 */
final class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 12;

    public static function getNavigationGroup(): string
    {
        // Une méthode et non une propriété : un défaut de propriété
        // ne peut pas appeler `__()`.
        return __('admin.groups.reference');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.questions.title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.questions.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.questions.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.read');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order_hint', 'asc')
            ->columns([
                TextColumn::make('order_hint')
                    ->label(__('admin.questions.order'))
                    ->sortable(),
                TextColumn::make('text')
                    ->label(__('admin.questions.text'))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('theme')
                    ->label(__('admin.questions.theme'))
                    ->badge(),
                TextColumn::make('difficulty')
                    ->label(__('admin.questions.difficulty')),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListQuestions::route('/'),
        ];
    }
}
