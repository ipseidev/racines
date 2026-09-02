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

        expect(discarded.state).toBe('explaining');
        expect(discarded.context.segments).toBe(0);
    });

    it('bascule sur l’écran d’aide quand le navigateur ne sait pas enregistrer', () => {
        expect(
            reduce(initialSnapshot, { type: 'UNSUPPORTED' }, limits).state,
        ).toBe('unsupported');
    });

    it('ignore le temps qui passe quand on n’enregistre pas', () => {
        const paused = run([
            { type: 'BEGIN' },
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
