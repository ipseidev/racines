/**
 * Ce que la page rapporte au serveur, au mieux.
 *
 * `keepalive` pour que le signal parte même si la page se ferme juste après :
 * c'est précisément quand le narrateur abandonne qu'on a besoin de savoir
 * pourquoi. Un échec d'envoi est ignoré — mesurer ne doit jamais gêner
 * l'enregistrement.
 */
export type ClientEventName =
    | 'mic_denied'
    | 'mic_granted'
    | 'recorder_unsupported'
    | 'recording_started'
    | 'recording_paused'
    | 'recording_resumed'
    | 'recording_stopped'
    | 'page_hidden'
    | 'interrupted'
    | 'resumed_from_draft'
    | 'draft_discarded'
    | 'soft_warning_reached'
    | 'hard_stop_reached'
    | 'upload_started'
    | 'upload_retried'
    | 'upload_failed'
    | 'storage_quota_low'
    | 'written_answer_chosen';

export function reportClientEvent(
    event: ClientEventName,
    payload: Record<string, unknown> = {},
    path = `${window.location.pathname}/events`,
): void {
    const token = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content;

    void fetch(path, {
        method: 'POST',
        keepalive: true,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(token === undefined ? {} : { 'X-CSRF-TOKEN': token }),
        },
        body: JSON.stringify({ event, payload }),
    }).catch(() => {
        // Mesurer ne doit jamais gêner : on avale l'échec.
    });
}
