import { act, render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { ShareSheet } from './ShareSheet';

const labels = {
    title: 'Le lien est prêt',
    hint: 'Le lien précédent ne fonctionne plus.',
    copyLabel: 'Copier le lien',
    copiedLabel: 'Copié',
    whatsappLabel: 'WhatsApp',
    smsLabel: 'SMS',
};

describe('ShareSheet', () => {
    beforeEach(() => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('copie le lien et le dit deux secondes', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.assign(navigator, { clipboard: { writeText } });

        render(
            <ShareSheet
                link="https://example.test/r/abc"
                whatsapp="https://wa.me/?text=abc"
                sms="sms:+33600000099?&body=abc"
                {...labels}
            />,
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Copier le lien' }),
        );

        expect(writeText).toHaveBeenCalledWith('https://example.test/r/abc');
        expect(screen.getByRole('button', { name: 'Copié' })).toBeVisible();

        await act(async () => {
            await vi.advanceTimersByTimeAsync(2500);
        });

        expect(
            screen.getByRole('button', { name: 'Copier le lien' }),
        ).toBeVisible();
    });

    it('propose WhatsApp et SMS quand ils sont donnés', () => {
        render(
            <ShareSheet
                link="https://example.test/r/abc"
                whatsapp="https://wa.me/?text=abc"
                sms={null}
                {...labels}
            />,
        );

        expect(screen.getByRole('link', { name: 'WhatsApp' })).toHaveAttribute(
            'href',
            'https://wa.me/?text=abc',
        );
        expect(screen.queryByRole('link', { name: 'SMS' })).toBeNull();
    });
});
