<?php

declare(strict_types=1);

namespace App\Engine\Actions;

use App\Models\AccessToken;

/**
 * Le nom d'action qui voyage dans le périmètre du jeton, et la classe qui
 * l'exécute.
 *
 * Une liste fermée : un jeton dont le périmètre nomme une action inconnue est
 * refusé, jamais deviné. C'est ce qui empêche un périmètre bricolé d'ouvrir
 * autre chose que ce pour quoi le lien a été émis.
 */
final class OneTapRegistry
{
    /** @var list<class-string<OneTapAction>> */
    private const ACTIONS = [
        ResendWhatsapp::class,
        SwitchBiweekly::class,
        AckCallParent::class,
        OfferPhoneOption::class,
        ReactHeart::class,
    ];

    /**
     * `null` pour un périmètre qu'on ne reconnaît pas.
     *
     * Le contrôleur en fait un 404 : du point de vue du visiteur, un lien
     * dont le périmètre est bricolé est un lien qui n'existe pas. Une erreur
     * technique lui apprendrait qu'il a touché quelque chose.
     */
    public function resolve(AccessToken $token): ?OneTapAction
    {
        $name = self::nameIn($token);

        foreach (self::ACTIONS as $action) {
            if ($action::name() === $name) {
                return app($action);
            }
        }

        return null;
    }

    /**
     * Le nom d'action lu dans le périmètre.
     */
    public static function nameIn(AccessToken $token): string
    {
        foreach ($token->scope ?? [] as $entry) {
            if ($entry !== 'action') {
                return $entry;
            }
        }

        return '';
    }

    /**
     * Le périmètre d'un jeton d'action : le mot `action`, puis le nom.
     *
     * @return list<string>
     */
    public static function scopeFor(string $name): array
    {
        return ['action', $name];
    }
}
