# Annexe C — Règles du moteur de complétion v1

Transcription exacte de PRD §5.3 en règles exécutables. Chaque règle est une classe `App\Engine\Rules\<RuleId>` qui implémente `App\Engine\Rule` :

```php
interface Rule
{
    public static function id(): string;                     // ex. 'link_not_opened'
    public function detect(CarbonImmutable $now): Collection; // occurrences candidates (projet/histoire)
    public function occurrenceKey(object $occurrence): string; // pour la clé d'idempotence
    public function isCapped(object $occurrence): bool;       // limite anti-culpabilisation
    public function fire(object $occurrence): EngineEvent;    // action + enregistrement
}
```

Le tick (`php artisan engine:tick`, toutes les heures à la minute 07) parcourt les règles dans l'ordre du tableau, ignore les projets `paused`, `frozen_bereavement`, `cancelled`, `completed`, et n'exécute jamais une occurrence dont la `dedupe_key` existe déjà. Les délais sont exprimés en jours calendaires dans le fuseau du projet. Les paramètres chiffrés vivent dans `config/product.php` sous la clé `engine`.

| rule_id | État détecté | Déclencheur | Message → destinataire (clé de traduction) | Action proposée | Limite | Événement de reprise mesuré |
|---|---|---|---|---|---|---|
| `invitation_not_accepted` | `projects.status = awaiting_acceptance`, invitation envoyée, ni acceptée ni refusée | J+7 (relance douce au narrateur), J+14 (alerte à l'Initiateur·rice) | `notifications.engine.invitation_reminder` → narrateur ; `notifications.engine.invitation_alert` → Initiateur·rice | Renvoyer via le canal de l'Initiateur·rice (lien copiable WhatsApp) ; message audio personnel | 2 relances max, puis `H0 constaté` : `projects.refused_at = null`, `status = awaiting_acceptance` gelé, contact supprimé à J+14+30 | `invitation_accepted_after_reminder` |
| `link_not_opened` | histoire `proposed`, jeton `record` jamais utilisé | J+3 après envoi | `notifications.engine.link_resend` → narrateur **sur l'autre canal** (SMS↔email) | — | 1 renvoi par question | `link_opened_after_resend` |
| `mic_denied` | événement front `mic_denied` reçu pour une histoire `proposed` | immédiat | page d'aide par OS (front) ; si 2 échecs : ticket proactif dans Filament | Fallback réponse écrite ; aide support | Ne jamais redemander le micro en boucle (max 1 nouvelle demande par visite) | `mic_granted_after_help` |
| `recording_abandoned` | événement front `recording_started` sans `recording_confirmed` | J+2 | `notifications.engine.draft_waiting` → narrateur | Reprise du brouillon local (même lien) | 1 rappel | `draft_resumed` |
| `recorded_not_validated` | histoire `to_review` (variante B) ou `recorded` avec `share_decision = decide_later` | J+4 | `notifications.engine.validation_reminder` → narrateur | Les 3 choix de P0-18 | 2 rappels puis statut « en attente » silencieux (`stories.state` inchangé, flag `awaiting_quietly` dans `action_taken`) | `validated_after_reminder` |
| `validated_not_listened` | histoire `shared`, aucun `listen_events.reached_30s` | J+5 | `notifications.engine.new_story_nudge` → chaque proche (jeton `listen_story`) | Lien d'écoute direct | 1 nudge par histoire | `listened_after_nudge` |
| `three_stories_no_reaction` | 3 dernières histoires `shared` du projet sans aucune `reactions` | au fil de l'eau | `notifications.engine.react_suggestion` → Initiateur·rice | Réaction en 1 tap (jeton `action` → réaction ❤️ sur la dernière histoire) | 1 par mois par projet | `story_recorded_within_7d_after_reaction` |
| `narrator_silence_10d` | aucune histoire `recorded` depuis 10 jours alors qu'un lien est ouvert ou envoyé | J+10 | `notifications.engine.lighter_question` → narrateur, avec une question `difficulty ≤ 2` | Changer de thème (nouveau lien) | Ton jamais culpabilisant (chaîne relue) | `recorded_after_lighter_question` |
| `narrator_silence_21d` | aucune histoire `recorded` depuis 21 jours | J+21 | `notifications.engine.initiator_alert` → Initiateur·rice | 4 actions 1-tap (jetons `action`) : renvoyer via son WhatsApp / passer en quinzomadaire / « appelez votre parent » (accusé de réception) / proposer l'option « Enregistrement par téléphone » (`phone_options.entry = rescue`, si `phone-option-offer` actif) | 1 alerte par mois | `recorded_within_30d_after_alert` |
| `pause_requested` | `projects.paused_until` posé par le narrateur ou l'Initiateur·rice | sur action | `notifications.engine.pause_confirmed` → narrateur, avec date de reprise | Reprise programmée (`next_prompt_at = paused_until`) | Aucun autre envoi pendant la pause | `resumed_after_pause` |
| `declining_cadence` | nombre d'histoires `recorded` sur 4 semaines ≤ moitié des 4 semaines précédentes, minimum 2 → 1 | hebdomadaire | `notifications.engine.slower_rhythm_offer` → narrateur | Passage en `biweekly` (jeton `action`) | Réduire vaut mieux qu'arrêter ; 1 proposition par 8 semaines | `retained_at_8w` |

## Paramètres (`config/product.php`, clé `engine`)

```php
'engine' => [
    'tick_cron' => '7 * * * *',
    'invitation_reminder_days' => [7, 14],
    'link_not_opened_days' => 3,
    'recording_abandoned_days' => 2,
    'recorded_not_validated_days' => 4,
    'recorded_not_validated_max_reminders' => 2,
    'validated_not_listened_days' => 5,
    'no_reaction_story_count' => 3,
    'react_suggestion_min_interval_days' => 30,
    'silence_light_question_days' => 10,
    'silence_alert_days' => 21,
    'silence_alert_min_interval_days' => 30,
    'declining_window_weeks' => 4,
    'declining_offer_min_interval_weeks' => 8,
],
```

## Événements analytics émis (bloc 15)

Chaque `fire()` émet `engine_rule_fired` avec `rule_id`, `project_id`, `story_id`, `attempt`. Chaque reprise détectée émet `engine_rule_resumed` avec `rule_id`, `delay_hours`. Les métriques de la dernière colonne sont calculées dans PostHog à partir de ces deux événements.

## Tests obligatoires par règle (bloc 09)

Pour chaque règle, dans `tests/Feature/Engine/Rules/<RuleId>Test.php` :
1. ne se déclenche pas avant le délai ;
2. se déclenche au délai exact ;
3. ne se déclenche pas deux fois pour la même occurrence ;
4. respecte la limite (n-ième occurrence ignorée) ;
5. ne se déclenche pas pendant une pause ni pour un projet gelé ;
6. enregistre l'événement `engine_events` avec la bonne `action_taken` ;
7. le message envoyé utilise la bonne clé de traduction et le bon destinataire.
