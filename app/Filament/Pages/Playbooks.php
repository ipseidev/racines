<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Les playbooks du support, lisibles dans l'outil de travail.
 *
 * Ils vivent dans `resources/playbooks/*.md` et non dans une base : ils se
 * relisent en revue de code, se versionnent, et se diffusent à quelqu'un qui
 * n'a pas encore de compte. Un playbook qu'on modifie sans trace est un
 * playbook auquel on ne peut pas se référer.
 *
 * Ils sont **ici** plutôt que dans un outil de documentation séparé pour une
 * raison plus simple : un support au téléphone avec une famille en deuil
 * n'ouvrira pas un second onglet.
 */
final class Playbooks extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lifebuoy';

    protected string $view = 'filament.pages.playbooks';

    public static function getNavigationLabel(): string
    {
        return __('admin.playbooks.title');
    }

    public function getTitle(): string
    {
        return __('admin.playbooks.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('support.read');
    }

    /**
     * L'ordre d'affichage : le plus grave d'abord.
     *
     * Pas l'ordre alphabétique. Quelqu'un qui ouvre cette page en urgence
     * cherche « décès » ou « regret », pas « conflit ».
     */
    private const ORDER = [
        'deces',
        'regret-confidence',
        'conflit-familial',
        'refus-cadeau',
        'micro-et-technique',
        'option-telephone',
    ];

    /**
     * @return list<array{slug: string, title: string, html: string}>
     */
    public function playbooks(): array
    {
        $directory = resource_path('playbooks');

        if (! File::isDirectory($directory)) {
            return [];
        }

        $converter = new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        $files = collect(File::files($directory))
            ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'md')
            ->sortBy(function (SplFileInfo $file): int {
                $position = array_search($file->getFilenameWithoutExtension(), self::ORDER, true);

                // Un playbook ajouté sans être classé passe à la fin plutôt
                // que de casser la page.
                return $position === false ? count(self::ORDER) : $position;
            });

        return array_values($files
            ->map(fn (SplFileInfo $file): array => [
                'slug' => $file->getFilenameWithoutExtension(),
                'title' => self::titleOf($file->getContents(), $file->getFilenameWithoutExtension()),
                'html' => (string) $converter->convert($file->getContents()),
            ])
            ->all());
    }

    /**
     * Le titre est le premier `#` du fichier : il vit avec le texte, et non
     * dans une table de correspondance qu'on oublierait de mettre à jour.
     */
    private static function titleOf(string $markdown, string $fallback): string
    {
        preg_match('/^#\s+(.+)$/m', $markdown, $matches);

        return trim($matches[1] ?? Str::headline($fallback));
    }
}
