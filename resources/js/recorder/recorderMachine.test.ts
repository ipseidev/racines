import { describe, expect, it } from 'vitest';

import {
    initialSnapshot,
    MAX_PERMISSION_RETRIES,
    reduce,
    type RecorderEvent,
    type RecorderLimits,
    type RecorderSnapshot,
} from './recorderMachine';

const limits: RecorderLimits = {
    softWarningSeconds: 600,
    hardStopSeconds: 1200,
};

const run = (
    events: RecorderEvent[],
    from = initialSnapshot,
): RecorderSnapshot =>
    events.reduce((snapshot, event) => reduce(snapshot, event, limits), from);

describe('machine à états de l’enregistrement', () => {
    it('déroule le parcours nominal jusqu’à la confirmation', () => {
        const states = [
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'audio' },
            { type: 'READY' },
            { type: 'PERMISSION_GRANTED' },
            { type: 'RECORD' },
            { type: 'PAUSE' },
            { type: 'RESUME' },
            { type: 'STOP' },
            { type: 'STOPPED' },
            { type: 'SEND' },
            { type: 'CONFIRMED' },
        ] satisfies RecorderEvent[];

        const seen = states.reduce<string[]>((acc, event) => {
            const snapshot = reduce(
                {
                    state:
                        acc.length === 0
                            ? 'idle'
                            : (acc[acc.length - 1] as never),
                    context: initialSnapshot.context,
                },
                event,
                limits,
            );

            return [...acc, snapshot.state];
        }, []);

        expect(seen).toEqual([
            'choosing_mode',
            'explaining',
            'requesting_permission',
            'ready',
            'recording',
            'paused',
            'recording',
            'stopping',
            'reviewing',
            'uploading',
            'confirmed',
        ]);
    });

    it('ne demande jamais le micro avant l’écran d’explication', () => {
        expect(reduce(initialSnapshot, { type: 'READY' }, limits).state).toBe(
            'idle',
        );
        expect(reduce(initialSnapshot, { type: 'RECORD' }, limits).state).toBe(
            'idle',
        );
    });

    it('mène au refus de micro et n’autorise qu’un seul nouvel essai', () => {
        let snapshot = run([
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'audio' },
            { type: 'READY' },
            { type: 'PERMISSION_DENIED' },
        ]);

        expect(snapshot.state).toBe('permission_denied');

        snapshot = reduce(snapshot, { type: 'RETRY_PERMISSION' }, limits);
        expect(snapshot.state).toBe('requesting_permission');
        expect(snapshot.context.permissionRetries).toBe(MAX_PERMISSION_RETRIES);

        snapshot = reduce(snapshot, { type: 'PERMISSION_DENIED' }, limits);
        snapshot = reduce(snapshot, { type: 'RETRY_PERMISSION' }, limits);

        expect(snapshot.state).toBe('permission_denied');
    });

    it('compte un segment de plus après une interruption, sans rien perdre', () => {
        let snapshot = run([
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'audio' },
            { type: 'READY' },
            { type: 'PERMISSION_GRANTED' },
            { type: 'RECORD' },
            { type: 'TICK', seconds: 42 },
        ]);

        expect(snapshot.context.segments).toBe(1);
        expect(snapshot.context.elapsedSeconds).toBe(42);

        snapshot = reduce(snapshot, { type: 'INTERRUPTED' }, limits);
        expect(snapshot.state).toBe('interrupted');

        snapshot = reduce(
            snapshot,
            { type: 'RESUME_AFTER_INTERRUPTION' },
            limits,
        );
        expect(snapshot.state).toBe('recording');
        expect(snapshot.context.segments).toBe(2);
        // Le temps écoulé ne repart pas de zéro : c'est la même histoire.
        expect(snapshot.context.elapsedSeconds).toBe(42);
    });

    it('signale l’approche de la limite à dix minutes', () => {
        const before = run([
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'audio' },
            { type: 'READY' },
            { type: 'PERMISSION_GRANTED' },
            { type: 'RECORD' },
            { type: 'TICK', seconds: 599 },
        ]);

        expect(before.context.warningShown).toBe(false);

        const after = reduce(before, { type: 'TICK', seconds: 600 }, limits);

        expect(after.context.warningShown).toBe(true);
        expect(after.state).toBe('recording');
    });

    it('arrête de lui-même à vingt minutes', () => {
        const snapshot = run([
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'audio' },
            { type: 'READY' },
            { type: 'PERMISSION_GRANTED' },
            { type: 'RECORD' },
            { type: 'TICK', seconds: 1200 },
        ]);

        expect(snapshot.state).toBe('stopping');
        expect(snapshot.context.hardStopReached).toBe(true);
    });

    it('réessaie un envoi échoué sans repartir de l’enregistrement', () => {
        let snapshot = run([
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'audio' },
            { type: 'READY' },
            { type: 'PERMISSION_GRANTED' },
            { type: 'RECORD' },
            { type: 'STOP' },
            { type: 'STOPPED' },
            { type: 'SEND' },
            { type: 'UPLOAD_FAILED' },
        ]);

        expect(snapshot.state).toBe('upload_failed');
        expect(snapshot.context.segments).toBe(1);

        snapshot = reduce(snapshot, { type: 'RETRY_UPLOAD' }, limits);

        expect(snapshot.state).toBe('uploading');
        expect(snapshot.context.segments).toBe(1);
    });

    it('ne confirme que depuis l’envoi, jamais depuis la vérification', () => {
        const reviewing = run([
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'audio' },
            { type: 'READY' },
            { type: 'PERMISSION_GRANTED' },
            { type: 'RECORD' },
            { type: 'STOP' },
            { type: 'STOPPED' },
        ]);

        expect(reduce(reviewing, { type: 'CONFIRMED' }, limits).state).toBe(
            'reviewing',
        );
    });

    it('propose le brouillon retrouvé sans écraser ce qui a été dit', () => {
        const found = reduce(initialSnapshot, { type: 'DRAFT_FOUND' }, limits);

        expect(found.state).toBe('draft_found');

        const resumed = reduce(found, { type: 'RESUME_DRAFT' }, limits);

        expect(resumed.state).toBe('reviewing');
        expect(resumed.context.segments).toBe(1);

        const discarded = reduce(found, { type: 'DISCARD_DRAFT' }, limits);

        expect(discarded.state).toBe('choosing_mode');
        expect(discarded.context.segments).toBe(0);
    });

    it('demande la forme avant l’explication, donc avant toute autorisation', () => {
        const choosing = reduce(initialSnapshot, { type: 'BEGIN' }, limits);

        expect(choosing.state).toBe('choosing_mode');
        expect(choosing.context.kind).toBe('audio');

        // Sauter le choix ne mène nulle part : on ne fait pas surgir une
        // caméra sur quelqu'un qui pensait parler.
        expect(reduce(choosing, { type: 'READY' }, limits).state).toBe(
            'choosing_mode',
        );

        const filming = reduce(
            choosing,
            { type: 'CHOOSE_MODE', kind: 'video' },
            limits,
        );

        expect(filming.state).toBe('explaining');
        expect(filming.context.kind).toBe('video');
    });

    it('laisse sortir de l’écran caméra tant que rien n’a été dit (T-212)', () => {
        const ready = run([
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'video' },
            { type: 'READY' },
            { type: 'PERMISSION_GRANTED' },
        ]);

        const sorti = reduce(ready, { type: 'CANCEL_MODE' }, limits);

        expect(sorti.state).toBe('choosing_mode');
        expect(sorti.context.kind).toBe('audio');
    });

    it('refuse de sortir dès que l’enregistrement tourne', () => {
        const enCours = run([
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'video' },
            { type: 'READY' },
            { type: 'PERMISSION_GRANTED' },
            { type: 'RECORD' },
            { type: 'TICK', seconds: 30 },
        ]);

        // Sortir ici jetterait un récit : la seule porte est « Terminer ».
        expect(reduce(enCours, { type: 'CANCEL_MODE' }, limits).state).toBe(
            'recording',
        );

        const enPause = reduce(enCours, { type: 'PAUSE' }, limits);

        expect(reduce(enPause, { type: 'CANCEL_MODE' }, limits).state).toBe(
            'paused',
        );
    });

    it('garde la forme choisie quand on recommence', () => {
        const confirmed = run([
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'video' },
            { type: 'READY' },
            { type: 'PERMISSION_GRANTED' },
            { type: 'RECORD' },
            { type: 'TICK', seconds: 30 },
            { type: 'STOP' },
            { type: 'STOPPED' },
        ]);

        const restarted = reduce(confirmed, { type: 'RESTART' }, limits);

        expect(restarted.state).toBe('ready');
        expect(restarted.context.elapsedSeconds).toBe(0);
        // La caméra est déjà ouverte : reposer la question serait une
        // question de trop.
        expect(restarted.context.kind).toBe('video');
    });

    it('bascule sur l’écran d’aide quand le navigateur ne sait pas enregistrer', () => {
        expect(
            reduce(initialSnapshot, { type: 'UNSUPPORTED' }, limits).state,
        ).toBe('unsupported');
    });

    it('ignore le temps qui passe quand on n’enregistre pas', () => {
        const paused = run([
            { type: 'BEGIN' },
            { type: 'CHOOSE_MODE', kind: 'audio' },
            { type: 'READY' },
            { type: 'PERMISSION_GRANTED' },
            { type: 'RECORD' },
            { type: 'PAUSE' },
        ]);

        expect(
            reduce(paused, { type: 'TICK', seconds: 999 }, limits).context
                .elapsedSeconds,
        ).toBe(0);
    });
});
