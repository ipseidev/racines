<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;
use App\Support\Names;

/**
 * Le titre imprimé sur la couverture.
 *
 * Jusqu'ici c'était le prénom, seul. C'est sobre et ce n'est pas faux, mais
 * une famille qui offre un livre veut souvent le nommer, et « Les souvenirs
 * d'Odette » dit en trois mots ce que « Odette » laisse deviner.
 *
 * Cinq formules et un titre libre. Les formules plutôt qu'un champ vide
 * seul : devant un champ vide, on écrit « Livre » ou rien, et on se retrouve
 * avec un cartonnage sans titre. `FirstName` reste la première, donc la
 * valeur par défaut, et c'est exactement ce qu'imprimaient les couvertures
 * avant ce choix.
 *
 * La formule ne colle pas le prénom : elle reçoit `:of`, déjà élidé par
 * `Names::of()`. Deux raisons. La première est que « L'histoire de Odette »
 * se lit mal ; la seconde est que l'écran du tunnel de découverte compose le
 * même titre avec le jumeau front de cette fonction, et qu'une règle
 * d'élision écrite deux fois finit par diverger.
 */
enum BookTitle: string
{
    use HasTranslatedLabel;

    case FirstName = 'first_name';
    case Story = 'story';
    case Stories = 'stories';
    case Life = 'life';
    case Memories = 'memories';
    case Custom = 'custom';

    public static function default(): self
    {
        return self::FirstName;
    }

    /** La clé de la formule imprimée, ou `null` pour un titre libre. */
    public function pattern(): ?string
    {
        return $this === self::Custom ? null : 'book.title.'.$this->value;
    }

    /**
     * Le titre, composé.
     *
     * Un titre libre vide retombe sur le prénom : mieux vaut une couverture
     * sobre qu'une couverture muette, et le cas arrive — un champ ouvert puis
     * abandonné en cours de route.
     */
    public function compose(string $firstName, ?string $custom = null, ?string $language = null): string
    {
        $name = trim($firstName);
        $free = trim((string) $custom);

        if ($this === self::Custom) {
            return $free !== '' ? $free : $name;
        }

        if ($name === '') {
            return '';
        }

        return (string) __($this->pattern() ?? '', [
            'name' => $name,
            'of' => Names::of($name, $language),
        ], $language);
    }

    /**
     * Les formules telles que le front les recevra, pour composer à l'écran
     * exactement ce que l'imprimeur composera.
     *
     * @return list<array{value: string, label: string, pattern: string|null}>
     */
    public static function options(?string $language = null): array
    {
        return array_map(
            static fn (self $title): array => [
                'value' => $title->value,
                'label' => (string) __($title->label(), [], $language),
                'pattern' => $title->pattern() === null
                    ? null
                    : (string) __($title->pattern(), [], $language),
            ],
            self::cases(),
        );
    }
}
