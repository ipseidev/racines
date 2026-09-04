import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

type Props = {
    action: string;
    title: string;
    body: string;
    button: string;
    done: boolean;
    message?: string | null;
    link?: string | null;
    whatsapp?: string | null;
    suggestion?: string | null;
};

/**
 * Une action, une phrase, un bouton.
 *
 * La page **montre** avant d'agir : un lien reçu par SMS qui agirait à
 * l'ouverture serait déclenché par le simple aperçu d'un client de
 * messagerie. Et un seul bouton, parce que la personne qui ouvre ce lien est
 * en train de faire autre chose — elle a trente secondes, pas un choix à
 * peser.
 */
export default function OneTapConfirm({
    title,
    body,
    button,
    done,
    message,
    link,
    whatsapp,
    suggestion,
}: Props) {
    const [sending, setSending] = useState(false);

    return (
        <>
            <Head title={title} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {title}
            </h1>

            <p className="mt-6">{body}</p>

            {done ? (
                <>
                    <p
                        role="status"
                        className="bg-brand-linen text-brand-text mt-8 rounded-md px-4 py-4 text-[1.125rem]"
                    >
                        {message}
                    </p>

                    {link == null ? null : (
                        <p className="border-brand-sand mt-6 rounded-md border px-4 py-3 break-all">
                            {link}
                        </p>
                    )}

                    {whatsapp == null ? null : (
                        <a
                            href={whatsapp}
                            className="bg-brand text-brand-foreground mt-6 inline-block min-h-[2.75rem] rounded-md px-6 py-3 text-lg font-medium"
                        >
                            WhatsApp
                        </a>
                    )}

                    {suggestion == null ? null : (
                        <p className="text-brand-muted mt-6 text-base">
                            {suggestion}
                        </p>
                    )}
                </>
            ) : (
                <button
                    type="button"
                    disabled={sending}
                    onClick={() => {
                        setSending(true);
                        router.post(
                            window.location.pathname,
                            {},
                            { onFinish: () => setSending(false) },
                        );
                    }}
                    className="bg-brand text-brand-foreground mt-8 min-h-[2.75rem] w-full rounded-md px-6 py-3 text-lg font-medium disabled:opacity-60"
                >
                    {button}
                </button>
            )}
        </>
    );
}
