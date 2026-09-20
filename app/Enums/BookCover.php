<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * La couverture du livre, choisie à l'achat et imprimée telle quelle.
 *
 * Six teintes, et pas une de plus : une couverture se choisit en trois
 * secondes sur un téléphone, et douze pastilles font hésiter là où six font
 * décider. Trois claires, trois sombres, pour que la vignette ne se ressemble
 * pas d'une famille à l'autre.
 *
 * `Ivory` est la première, donc la valeur par défaut, et sa teinte est
 * **exactement** celle que la couverture avait avant qu'on laisse le choix :
 * un livre déjà commandé ne change pas d'aspect parce qu'on a ajouté un
 * écran.
 *
 * Les valeurs hexadécimales vivent ici et non dans les jetons de marque : ce
 * sont des références d'impression — une matière, une encre —, pas le thème
 * de l'interface. Le thème peut changer sans que les exemplaires déjà tirés
 * cessent de correspondre à ce qui avait été montré.
 */
enum BookCover: string
{
    use HasTranslatedLabel;

    case Ivory = 'ivory';
    case Sand = 'sand';
    case Forest = 'forest';
    case Terracotta = 'terracotta';
    case Midnight = 'midnight';
    case Plum = 'plum';

    public static function default(): self
    {
        return self::Ivory;
    }

    /** La teinte de la couverture. */
    public function background(): string
    {
        return match ($this) {
            self::Ivory => '#faf7f2',
            self::Sand => '#e0d6c7',
            self::Forest => '#24392f',
            self::Terracotta => '#8f361f',
            self::Midnight => '#1f2b3d',
            self::Plum => '#4a2233',
        };
    }

    /**
     * L'encre du titre.
     *
     * Deux seulement, et elles se déduisent de la teinte : une couverture
     * sombre se marque en crème, une couverture claire en brun. Laisser
     * choisir l'encre séparément produirait des couvertures illisibles, et
     * c'est une chose qu'on ne découvre qu'une fois le carton ouvert.
     */
    public function ink(): string
    {
        return $this->isDark() ? '#f7f1e6' : '#2a231e';
    }

    /** L'encre secondaire : le sous-titre et le nom de la marque au pied. */
    public function mutedInk(): string
    {
        return $this->isDark() ? '#c9c0b2' : '#5a5049';
    }

    public function isDark(): bool
    {
        return match ($this) {
            self::Forest, self::Terracotta, self::Midnight, self::Plum => true,
            self::Ivory, self::Sand => false,
        };
    }

    /**
     * Ce que le front a besoin de savoir pour dessiner la même couverture
     * que l'imprimeur.
     *
     * @return list<array{value: string, label: string, background: string, ink: string, mutedInk: string, dark: bool}>
     */
    public static function palette(): array
    {
        return array_map(
            static fn (self $cover): array => [
                'value' => $cover->value,
                'label' => __($cover->label()),
                'background' => $cover->background(),
                'ink' => $cover->ink(),
                'mutedInk' => $cover->mutedInk(),
                // Le marquage à froid n'a pas de couleur propre : il se voit
                // par son creux et par son bord, et les deux se déduisent de
                // la clarté de la toile.
                'dark' => $cover->isDark(),
            ],
            self::cases(),
        );
    }
}
