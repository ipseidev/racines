import { useEffect, useRef, useState } from 'react';

import { Check, Copy, Message, Send } from './Icons';

/*
 * La fiche de partage : le lien, « Copier », WhatsApp, SMS. Elle apparaît là
 * où l'on a cliqué, et le bouton dit « Copié » deux secondes : c'est le retour
 * qui manquait. Le presse-papiers demande un geste de l'utilisateur ; on ne
 * copie donc pas tout seul à l'arrivée du lien.
 */
type Props = {
    link: string;
    whatsapp: string | null;
    sms: string | null;
    title: string;
    hint?: string;
    copyLabel: string;
    copiedLabel: string;
    whatsappLabel: string;
    smsLabel: string;
};

export function ShareSheet({
    link,
    whatsapp,
    sms,
    title,
    hint,
    copyLabel,
    copiedLabel,
    whatsappLabel,
    smsLabel,
}: Props) {
    const [copied, setCopied] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (!copied) {
            return;
        }

        const timer = window.setTimeout(() => setCopied(false), 2400);

        return () => window.clearTimeout(timer);
    }, [copied]);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(link);
        } catch {
            // Sans presse-papiers (page non sécurisée, ancien navigateur), le
            // champ se sélectionne : un appui long ou Ctrl+C fait le reste.
            inputRef.current?.focus();
            inputRef.current?.select();
        }

        setCopied(true);
    };

    return (
        <div className="panel enter mt-5" role="region" aria-label={title}>
            <p className="font-medium">{title}</p>

            <div className="mt-3 flex flex-col gap-3 sm:flex-row">
                <input
                    ref={inputRef}
                    type="text"
                    readOnly
                    value={link}
                    onFocus={(event) => event.target.select()}
                    aria-label={title}
                    className="input flex-1 text-[0.95rem]"
                />

                <button
                    type="button"
                    onClick={() => void copy()}
                    className={`btn-secondary press min-h-[2.75rem] flex-none ${
                        copied
                            ? 'bg-brand text-brand-foreground hover:bg-brand'
                            : ''
                    }`}
                >
                    {copied ? <Check /> : <Copy />}
                    {copied ? copiedLabel : copyLabel}
                </button>
            </div>

            <span role="status" className="sr-only">
                {copied ? copiedLabel : ''}
            </span>

            {(whatsapp !== null || sms !== null) && (
                <div className="mt-3 flex flex-wrap gap-3">
                    {whatsapp !== null && (
                        <a
                            href={whatsapp}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="btn-secondary press min-h-[2.75rem]"
                        >
                            <Send />
                            {whatsappLabel}
                        </a>
                    )}

                    {sms !== null && (
                        <a
                            href={sms}
                            className="btn-secondary press min-h-[2.75rem]"
                        >
                            <Message />
                            {smsLabel}
                        </a>
                    )}
                </div>
            )}

            {hint !== undefined && (
                <p className="text-brand-muted mt-3 text-base">{hint}</p>
            )}
        </div>
    );
}
