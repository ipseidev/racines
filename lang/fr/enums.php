<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Libellés des énumérations de domaine
|--------------------------------------------------------------------------
|
| Une clé par valeur d'énumération, appelée par la méthode `label()`. Le
| vocabulaire suit le référentiel doc 05 et la liste d'expressions proscrites
| de R-11, que `tests/Unit/ForbiddenVocabularyTest.php` vérifie ici même.
|
*/

return [

    'project_status' => [
        'draft' => 'Brouillon',
        'awaiting_acceptance' => 'En attente d’acceptation',
        'active' => 'En cours',
        'paused' => 'En pause',
        'dormant' => 'En sommeil',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
        'frozen_bereavement' => 'Gelé (deuil)',
    ],

    'offer' => [
        'pilot' => 'Pilote',
        'core' => 'Offre cœur',
        'prevente' => 'Prévente',
    ],

    'address_form' => [
        'vous' => 'Vouvoiement',
        'tu' => 'Tutoiement',
    ],

    'cadence' => [
        'weekly' => 'Une question par semaine',
        'biweekly' => 'Une question tous les quinze jours',
    ],

    'prompt_slot' => [
        'morning' => 'Matin',
        'afternoon' => 'Après-midi',
        'evening' => 'Soir',
    ],

    'channel' => [
        'sms' => 'SMS',
        'email' => 'Courriel',
        'phone_operator' => 'Téléphone (opérateur)',
    ],

    'share_decision' => [
        'share' => 'Partager avec les proches',
        'keep_private' => 'Garder pour moi',
        'decide_later' => 'Décider plus tard',
    ],

    'validated_via' => [
        'recording_end' => 'À la fin de l’enregistrement',
        'post_transcription' => 'Après relecture du texte',
        'mandate' => 'Par la personne mandatée',
        'phone_operator' => 'Accord oral recueilli par téléphone',
    ],

    'story_visibility' => [
        'all_family' => 'Tous les proches',
        'restricted' => 'Certains proches seulement',
        'book_only' => 'Livre uniquement',
    ],

    'answer_type' => [
        'audio' => 'Voix',
        'text' => 'Texte écrit',
        'phone' => 'Téléphone',
    ],

    'consent_kind' => [
        'voice_recording' => 'Enregistrement de la voix',
        'transcription' => 'Transcription de l’audio',
        'ai_rendering' => 'Mise en forme du texte par une intelligence artificielle',
        'family_sharing' => 'Partage avec les proches',
        'sensitive_categories' => 'Sujets sensibles (santé, convictions, origines)',
        'phone_call_recording' => 'Enregistrement de l’appel téléphonique',
        'photo_rights' => 'Droits sur les photos déposées',
        'post_mortem_directives' => 'Directives à appliquer après le décès',
    ],

    'consent_status' => [
        'granted' => 'Accordé',
        'revoked' => 'Retiré',
    ],

    'consent_channel' => [
        'web' => 'Sur le site',
        'phone' => 'Par téléphone',
        'admin' => 'Saisi par le support',
    ],

    'question_theme' => [
        'childhood' => 'Enfance',
        'family_origins' => 'Origines familiales',
        'youth' => 'Jeunesse',
        'work' => 'Métier',
        'love' => 'Amour',
        'places' => 'Lieux',
        'joys' => 'Joies',
        'hardships' => 'Épreuves',
        'beliefs_values' => 'Convictions et valeurs',
        'legacy' => 'Ce qui reste',
    ],

    'project_member_role' => [
        'initiator' => 'Initiateur·rice',
        'editor' => 'Éditeur·rice désigné·e',
    ],

    'validation_variant' => [
        'immediate' => 'Validation en fin d’enregistrement',
        'deferred' => 'Validation après relecture',
    ],

    'deletion_requested_by' => [
        'narrator' => 'Le narrateur·rice',
        'mandate' => 'La personne mandatée',
        'admin' => 'Le support, sur demande écrite',
    ],

    'cohort_phase' => [
        '0A' => 'Phase 0A',
        '0B' => 'Phase 0B',
        'launch' => 'Lancement',
    ],

    'story_state' => [
        'proposed' => 'Proposée',
        'recorded' => 'Enregistrée',
        'transcribed' => 'Transcrite',
        'to_review' => 'À relire',
        'validated' => 'Validée',
        'shared' => 'Partagée',
        'in_book' => 'Incluse au livre',
        'hidden' => 'Masquée',
        'archived' => 'Archivée',
        'trashed' => 'Dans la corbeille',
        'deleted' => 'Supprimée',
    ],

];
