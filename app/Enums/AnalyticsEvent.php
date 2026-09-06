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

    /*
     * Moteur de complétion (bloc 09). Les deux vont ensemble : le premier dit
     * qu'on a relancé, le second qu'on a servi à quelque chose. Sans le
     * second, le moteur ne serait qu'un émetteur de messages.
     */
    case EngineRuleFired = 'engine_rule_fired';
    case EngineRuleResumed = 'engine_rule_resumed';

    /*
     * L'entonnoir H0 : de l'achat à l'acceptation (bloc 15, PRD §7).
     *
     * Le dénominateur de H0 est **les invitations délivrées**, pas les
     * achats : une invitation qui n'arrive jamais — mauvais numéro, SMS
     * bloqué — n'a pas été refusée, elle n'a pas été posée. Les confondre
     * ferait passer un problème d'acheminement pour un refus du parent, et
     * l'on corrigerait la mauvaise chose.
     */
    case PurchaseCompleted = 'purchase_completed';
    case InvitationDelivered = 'invitation_delivered';
    case InvitationAccepted = 'invitation_accepted';
    case InvitationRefused = 'invitation_refused';
    case ConsentRecorded = 'consent_recorded';

    /*
     * L'entonnoir H1 : du premier lien à la dixième histoire.
     *
     * `MicDenied` compte autant que `MicGranted` : le seuil du dossier est
     * « réussite du premier enregistrement non assisté ≥ 85 % », et le micro
     * refusé est la première cause d'échec connue de la catégorie.
     */
    case FirstLinkClicked = 'first_link_clicked';
    case MicGranted = 'mic_granted';
    case MicDenied = 'mic_denied';
    case FirstStoryRecorded = 'first_story_recorded';
    case StoryRecorded = 'story_recorded';
    case FirstStoryValidated = 'first_story_validated';
    case StoryValidated = 'story_validated';
    case ThirdStoryValidated = 'third_story_validated';
    case TenthStoryValidated = 'tenth_story_validated';

    /*
     * La chaîne H2, côté famille. `FirstListen30s` et `FirstReaction` sont
     * distincts de leurs équivalents répétés : ce qui décide, c'est le
     * **premier** franchissement, pas le volume.
     */
    case FirstListen30s = 'first_listen_30s';
    case FirstReaction = 'first_reaction';

    /*
     * Le livre, et ce qui se passe après.
     */
    case BookReady = 'book_ready';
    case ProofApproved = 'proof_approved';
    case BookDelivered = 'book_delivered';
    case PrintDefect = 'print_defect';

    /*
     * Les **contre-métriques** (PRD §7).
     *
     * Elles existent pour empêcher un chiffre flatteur : un taux de
     * validation excellent qui s'accompagnerait de retraits massifs dirait
     * qu'on a poussé des gens à valider ce qu'ils ne voulaient pas partager.
     * Elles sont donc comptées avec la même rigueur que les succès.
     */
    case RefundIssued = 'refund_issued';
    case StoryHidden = 'story_hidden';
    case StoryDeleted = 'story_deleted';

    /*
     * La charge de l'Initiateur·rice : le dossier lui promet ≤ 4
     * sollicitations et ≤ 15 minutes par mois. Sans mesure, cette promesse
     * est une intention.
     */
    case InitiatorActionRequested = 'initiator_action_requested';
    case InitiatorActionDone = 'initiator_action_done';

    /** Le test de demande D-9 : l'option téléphone, par point d'entrée. */
    case PhoneOptionSelected = 'phone_option_selected';

    /** L'enquête de satisfaction à la semaine 8. */
    case NpsAnswered = 'nps_answered';
}
