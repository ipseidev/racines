<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Ce qu'une demande d'effacement emporte.
 *
 * Les deux portées ont le même effet **technique** — les récits, les voix et
 * les champs personnels partent — et ne disent pas la même chose. `Narrator`
 * est le retrait d'une personne qui reprend sa parole ; `Project` est une
 * famille qui referme le dossier. Les distinguer sert à deux choses : la
 * priorité (le narrateur gagne, doc 04 §3) et la trace, parce qu'on ne
 * répond pas de la même façon trois mois plus tard.
 */
enum ErasureScope: string
{
    case Narrator = 'narrator';
    case Project = 'project';
}
