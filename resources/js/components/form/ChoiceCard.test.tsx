import { render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import { ChoiceCard } from './ChoiceCard';

describe('ChoiceCard', () => {
    it('reste un vrai bouton radio, et toute la carte le coche', async () => {
        const onChange = vi.fn();

        render(
            <>
                <ChoiceCard
                    name="for"
                    value="relative"
                    checked={true}
                    onChange={onChange}
                    title="Un proche"
                    hint="Vous offrez, la personne raconte."
                />
                <ChoiceCard
                    name="for"
                    value="self"
                    checked={false}
                    onChange={onChange}
                    title="Vous-même"
                />
            </>,
        );

        expect(screen.getByRole('radio', { name: /Un proche/ })).toBeChecked();
        expect(
            screen.getByRole('radio', { name: /Vous-même/ }),
        ).not.toBeChecked();

        // Le clic sur le texte, pas sur le rond : c'est là que tombe le pouce.
        await userEvent.click(screen.getByText('Vous-même'));

        expect(onChange).toHaveBeenCalledWith('self');
    });
});
