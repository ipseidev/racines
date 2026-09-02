import { Head } from '@inertiajs/react';

/**
 * Jalon d'accueil. Le bloc 01 y branche la marque, le bloc 10 la remplace par
 * la véritable page publique. Aucun contenu de démonstration Laravel ici.
 */
export default function Welcome() {
    return (
        <>
            <Head title="Accueil" />

            <main className="flex min-h-screen items-center justify-center bg-white px-6 py-16 text-neutral-900">
                <div className="w-full max-w-xl">
                    <h1 className="text-3xl leading-tight font-semibold sm:text-4xl">
                        Le livre de souvenirs de vos parents qui va réellement
                        au bout.
                    </h1>

                    <p className="mt-6 text-lg leading-relaxed text-neutral-700">
                        Sans application, et sans leur demander d’écrire.
                    </p>

                    <p className="mt-10 text-base text-neutral-600">
                        Ce site est en cours de construction.
                    </p>
                </div>
            </main>
        </>
    );
}
