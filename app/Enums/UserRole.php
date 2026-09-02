<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Rôles applicatifs (R-1 et doc 04 §12).
 *
 * Initiateur·rice est le rôle des clients : il achète, organise, prépare le
 * BAT. Les trois autres sont le personnel, seul autorisé sur le back-office,
 * avec des permissions minimales et une double authentification obligatoire.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Support = 'support';
    case SupportReadonly = 'support_readonly';
    case Initiator = 'initiator';

    public static function default(): self
    {
        return self::Initiator;
    }

    public function isStaff(): bool
    {
        return $this !== self::Initiator;
    }

    public function label(): string
    {
        return 'admin.roles.'.$this->value;
    }
}
