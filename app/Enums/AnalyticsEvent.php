<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Les événements de la chaîne H2, mesurés maillon par maillon.
 *
 * Le dossier refuse de présumer que l'attention des proches nourrit le
 * narrateur : il faut mesurer chaque étape séparément. Un taux global ne
 * dirait pas *où* la chaîne casse — page jamais ouverte, écoute abandonnée à
 * dix secondes, réaction jamais envoyée, notification jamais reçue.
 *
 * Aucun de ces événements ne porte de donnée personnelle : ni prénom, ni
 * coordonnée, ni jeton. Des identifiants opaques et des durées.
 */
enum AnalyticsEvent: string
{
    case FamilyLinkOpened = 'family_link_opened';
    case StoryPageOpened = 'story_page_opened';
    case StoryListened30s = 'story_listened_30s';
    case ReactionSent = 'reaction_sent';
    case NarratorNotified = 'narrator_notified';
    /** Calculé au bloc 09, déclaré ici pour que la chaîne soit lisible. */
    case StoryRecordedWithin7dOfNotification = 'story_recorded_within_7d_of_notification';
}
