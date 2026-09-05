import { render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import { ConfirmDialog } from './ConfirmDialog';

const labels = {
    title: 'Retirer l’accès de Claire ?',
    body: 'Son lien cessera de fonctionner.',
    confirmLabel: 'Retirer l’accès',
    cancelLabel: 'Annuler',
};

describe('ConfirmDialog', () => {
    it('ne rend rien tant qu’elle est fermée', () => {
        render(
            <ConfirmDialog
                open={false}
                onConfirm={vi.fn()}
                onCancel={vi.fn()}
                {...labels}
            />,
        );

        expect(screen.queryByRole('alertdialog')).toBeNull();
    });

    it('pose le focus sur « Annuler » et confirme sur demande', async () => {
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        render(
            <ConfirmDialog
                open
                onConfirm={onConfirm}
                onCancel={onCancel}
                {...labels}
            />,
        );

        expect(screen.getByRole('alertdialog')).toHaveAccessibleName(
            labels.title,
        );
        expect(screen.getByRole('button', { name: 'Annuler' })).toHaveFocus();

        await userEvent.click(
            screen.getByRole('button', { name: 'Retirer l’accès' }),
        );

        expect(onConfirm).toHaveBeenCalledOnce();
        expect(onCancel).not.toHaveBeenCalled();
    });

    it('se ferme avec Échap', async () => {
        const onCancel = vi.fn();

        render(
            <ConfirmDialog
                open
                onConfirm={vi.fn()}
                onCancel={onCancel}
                {...labels}
            />,
        );

        await userEvent.keyboard('{Escape}');

        expect(onCancel).toHaveBeenCalledOnce();
    });
});
