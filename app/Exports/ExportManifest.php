<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Le manifeste d'un export.
 *
 * Il existe pour une raison vérifiable : **la famille doit pouvoir s'assurer
 * que rien ne manque et que rien n'a été abîmé**, sans nous. Un ZIP sans
 * manifeste oblige à nous croire sur parole ; avec lui, un `shasum -a 256`
 * répond tout seul, dix ans plus tard, sur une machine qui n'aura jamais
 * entendu parler de nous.
 *
 * Le schéma est **versionné** : le jour où un champ change, un outil écrit
 * par quelqu'un d'autre doit pouvoir dire « je ne sais pas lire cette
 * version » plutôt que de deviner de travers.
 */
final class ExportManifest
{
    public const VERSION = '1.0';

    public const ALGORITHM = 'sha256';
}
