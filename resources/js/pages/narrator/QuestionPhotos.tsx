import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';

import { useT } from '@/hooks/useT';

export type QuestionPhoto = {
    id: number;
    url: string;
    thumbUrl: string;
    alt: string;
    caption: string | null;
    /** Le prénom de qui l'a envoyée, ou `null` si on ne le connaît pas. */
    from: string | null;
};

/**
 * Les photos qui **posent** la question (T-251).
 *
 * Une famille joint une image à une question pas encore racontée — « raconte-
 * nous celle-ci ». Le dépôt existait depuis le bloc 12, l'affichage non : la
 * photo attendait en base d'être vue après l'enregistrement, c'est-à-dire
 * trop tard pour servir.
 *
 * **Des vignettes, et le plein écran au toucher.** Mesuré sur la fenêtre
 * réelle d'un iPhone (393 × 726) : une image affichée en grand dans la carte
 * ajoute 237 px et fait défiler la page de 152 px, alors que la règle de
 * cette page est de tenir dans l'écran sans défilement (T-139) — une
 * grand-mère ne doit jamais chercher le bouton sous le bord. La vignette de
 * 88 px est celle de `PhotoGallery`, employée partout ailleurs dans le
 * produit : ce qui s'apprend une fois se retrouve à sa place.
 *
 * Le plein écran passe par un **portail sur `body`** : `main` porte `.enter`,
 * qui laisse un `transform` identité après son animation et fait donc de lui
 * le bloc conteneur de tout `fixed` — un rideau posé là se centre hors écran
 * (T-232, trouvé sur la page d'invitation).
 *
 * Ce morceau n'est chargé que lorsqu'il y a des photos : une narratrice dont
 * la question n'en porte pas n'en paie pas un octet, et le budget de la page
 * est de 150 Ko (conventions §4).
 */
export default function QuestionPhotos({
    photos,
}: {
    photos: QuestionPhoto[];
}) {
    const t = useT();
    const [opened, setOpened] = useState<QuestionPhoto | null>(null);

    useEffect(() => {
        if (opened === null) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpened(null);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [opened]);

    if (photos.length === 0) {
        return null;
    }

    const seule = photos.length === 1 ? photos[0] : null;
    const plusieurs = photos.length > 1;

    /*
     * « Envoyée par Claire », et « par votre famille » à défaut.
     *
     * Le prénom situe la photo bien mieux qu'un collectif : c'est quelqu'un
     * qui demande, pas un service. On ne le donne que lorsqu'on le connaît,
     * et que **toutes** les photos viennent de la même personne — « envoyée
     * par Claire » sous trois images dont deux sont d'un autre serait faux.
     */
    const commun = photos.every((photo) => photo.from === photos[0].from)
        ? photos[0].from
        : null;

    const provenance =
        commun === null
            ? t(
                  plusieurs
                      ? 'narrator.record.photos_from_family'
                      : 'narrator.record.photo_from_family',
              )
            : t(
                  plusieurs
                      ? 'narrator.record.photos_from'
                      : 'narrator.record.photo_from',
                  { name: commun },
              );

    return (
        <>
            {/*
             * Une seule photo prend la largeur de la carte.
             *
             * Elle tenait d'abord dans la même vignette de 88 px que les
             * autres, et c'était illisible : à 88 px on ne distingue pas ce
             * que montre une photo, et les deux cent cinquante pixels de
             * blanc à sa droite ne servaient à rien. La hauteur est bornée
             * en `vh` — la page doit tenir dans l'écran (T-139) et une photo
             * de téléphone est portrait ou paysage.
             */}
            {seule !== null ? (
                <button
                    type="button"
                    onClick={() => setOpened(seule)}
                    className="question-photo-solo press"
                >
                    <img src={seule.url} alt={seule.alt} decoding="async" />
                </button>
            ) : (
                <ul className="question-photos">
                    {photos.map((photo) => (
                        <li key={photo.id}>
                            <button
                                type="button"
                                onClick={() => setOpened(photo)}
                                aria-label={t('narrator.record.photo_open', {
                                    alt: photo.alt,
                                })}
                                className="press border-brand-sand hover:border-brand block size-[88px] overflow-hidden rounded-lg border transition-colors"
                            >
                                <img
                                    src={photo.thumbUrl}
                                    alt={photo.alt}
                                    className="size-full object-cover"
                                />
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {seule !== null && seule.caption !== null ? (
                <p className="question-photo-caption text-brand-muted mt-1 text-base">
                    {seule.caption}
                </p>
            ) : null}

            {/*
             * Que l'image s'agrandisse doit être **écrit**.
             *
             * Une image bordée ne ressemble pas à un bouton, et personne à
             * quatre-vingts ans ne tente un appui pour voir. Le libellé dit
             * aussi d'où vient la photo : c'est la seule phrase de l'écran
             * qui explique pourquoi une image accompagne la question.
             */}
            <p className="question-photo-note text-brand-muted mt-2 flex flex-wrap items-baseline gap-x-2 text-base">
                <span>{provenance}</span>
                <button
                    type="button"
                    onClick={() => setOpened(photos[0])}
                    className="text-brand font-medium underline underline-offset-4"
                >
                    {t('narrator.record.photo_enlarge')}
                </button>
            </p>

            {opened !== null && typeof document !== 'undefined'
                ? createPortal(
                      <div
                          role="dialog"
                          aria-modal="true"
                          aria-label={opened.alt}
                          className="enter bg-brand-text/95 fixed inset-0 z-50 flex flex-col p-4"
                      >
                          <div className="flex flex-1 items-center justify-center">
                              <img
                                  src={opened.url}
                                  alt={opened.alt}
                                  className="max-h-full max-w-full rounded-lg object-contain"
                              />
                          </div>

                          {opened.caption !== null ? (
                              <p className="mt-3 text-center text-base text-white/90">
                                  {opened.caption}
                              </p>
                          ) : null}

                          <button
                              type="button"
                              onClick={() => setOpened(null)}
                              className="press border-brand-sand mx-auto mt-4 min-h-[2.75rem] rounded-md border-2 px-6 py-3 text-[1.0625rem] font-semibold text-[#F7F1E6]"
                          >
                              {t('common.actions.close')}
                          </button>
                      </div>,
                      document.body,
                  )
                : null}
        </>
    );
}
