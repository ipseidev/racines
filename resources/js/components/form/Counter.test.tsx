import { render, screen } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import { Counter } from './Counter';

function renderCounter(value: number, onChange = vi.fn()) {
    render(
        <Counter
            label="Exemplaires"
            value={value}
            min={0}
            max={5}
            onChange={onChange}
            decrementLabel="Un de moins"
            incrementLabel="Un de plus"
        />,
    );

    return onChange;
}

describe('Counter', () => {
    it('ajoute et retire un par les boutons', async () => {
        const onChange = renderCounter(2);

        await userEvent.click(
            screen.getByRole('button', { name: 'Un de plus' }),
        );
        expect(onChange).toHaveBeenLastCalledWith(3);

        await userEvent.click(
            screen.getByRole('button', { name: 'Un de moins' }),
        );
        expect(onChange).toHaveBeenLastCalledWith(1);
    });

    it('désactive le bouton qui ne mènerait nulle part', () => {
        renderCounter(0);
        expect(
            screen.getByRole('button', { name: 'Un de moins' }),
        ).toBeDisabled();
        expect(
            screen.getByRole('button', { name: 'Un de plus' }),
        ).toBeEnabled();
    });

    it('ramène une saisie clavier dans les bornes', async () => {
        const onChange = renderCounter(1);
        const input = screen.getByLabelText('Exemplaires');

        // Le champ reste un vrai champ : on peut y taper, et ce qui dépasse
        // est ramené au maximum plutôt qu'accepté puis refusé par le serveur.
        await userEvent.clear(input);
        await userEvent.type(input, '9');

        expect(onChange).toHaveBeenLastCalledWith(5);
    });
});
