import { useEffect, useRef, useState } from 'react';

import { Check } from './Icons';

/*
 * Les toasts de l'espace, sans bibliothèque : la politique de sécurité de
 * contenu n'autorise que les styles servis par l'application (`style-src`
 * à nonce), et une bibliothèque qui injecte sa feuille de style en JavaScript
 * arrive sans style. Un message, une coche, trois secondes et demie, en bas
 * et au centre parce que la page se lit sur un téléphone tenu d'une main. Le
 * même message répété remplace le précédent au lieu de s'empiler.
 */
type Toast = { id: number; message: string };

type Listener = (message: string) => void;

const listeners = new Set<Listener>();

export function toast(message: string): void {
    listeners.forEach((listener) => listener(message));
}

export const TOAST_DURATION = 3500;

export function Toasts({ duration = TOAST_DURATION }: { duration?: number }) {
    const [items, setItems] = useState<Toast[]>([]);
    const timers = useRef(new Map<number, number>());
    const sequence = useRef(0);

    useEffect(() => {
        const listener: Listener = (message) => {
            const id = ++sequence.current;

            setItems((list) => [
                ...list.filter((item) => item.message !== message),
                { id, message },
            ]);

            const timer = window.setTimeout(() => {
                timers.current.delete(id);
                setItems((list) => list.filter((item) => item.id !== id));
            }, duration);

            timers.current.set(id, timer);
        };

        listeners.add(listener);

        return () => {
            listeners.delete(listener);
            timers.current.forEach((timer) => window.clearTimeout(timer));
            timers.current.clear();
        };
    }, [duration]);

    return (
        <div
            role="status"
            aria-live="polite"
            className="pointer-events-none fixed inset-x-0 bottom-5 z-50 flex flex-col items-center gap-2 px-4"
        >
            {items.map((item) => (
                <div
                    key={item.id}
                    className="card enter pointer-events-auto flex max-w-[26rem] items-center gap-3 px-4 py-3 text-[1.0625rem] shadow-[0_10px_30px_-12px_rgb(36_57_47/0.35)]"
                >
                    <Check className="text-brand-sage size-5 flex-none" />
                    <span>{item.message}</span>
                </div>
            ))}
        </div>
    );
}
