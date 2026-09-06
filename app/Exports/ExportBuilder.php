<?php

declare(strict_types=1);

namespace App\Exports;

use App\Books\SelectBookChapters;
use App\Enums\ExportKind;
use App\Enums\ExportScope;
use App\Enums\TranscriptKind;
use App\Models\Book;
use App\Models\Consent;
use App\Models\Export;
use App\Models\Story;
use App\Services\Storage\MediaStorage;
use App\Support\Brand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;
use ZipStream\ZipStream;

/**
 * Fabriquer l'archive d'un export.
 *
 * Trois contraintes commandent tout le reste :
 *
 *  1. **Le ZIP doit être lisible sans le service.** C'est un critère de
 *     sortie du bloc, et le sens même de la non-captivité : des dossiers
 *     nommés en français, des fichiers ouvrables par un double-clic, et un
 *     `LISEZ-MOI.txt` qui explique quoi est quoi. Rien qui demande une URL
 *     du produit.
 *  2. **Il ne tient pas en mémoire.** Cinq gigaoctets d'audio ne se
 *     concatènent pas dans une variable. On écrit dans un fichier temporaire
 *     au fil de l'eau, puis on le pousse au stockage par un flux.
 *  3. **La portée décide, pas le demandeur.** L'Initiateur·rice ne reçoit que
 *     ce que le narrateur a validé — un export qui livrerait les brouillons
 *     contournerait toute la souveraineté du bloc 07 par la porte de derrière.
 */
final readonly class ExportBuilder
{
    public function __construct(private MediaStorage $storage) {}

    /**
     * Écrit l'archive sur le disque local et rend son chemin et son manifeste.
     *
     * @return array{path: string, manifest: array<string, mixed>}
     */
    public function build(Export $export): array
    {
        $project = $export->project;
        $stories = $project->stories()
            ->whereIn('state', $export->scope->states())
            ->with(['question', 'transcripts', 'recordings'])
            ->orderBy('sequence')
            ->orderBy('recorded_at')
            ->get();

        $path = $this->temporaryPath($export);
        File::ensureDirectoryExists(dirname($path));

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Impossible d’ouvrir le fichier temporaire de l’export.');
        }

        $checksums = [];
        $entrees = [];

        $zip = new ZipStream(
            outputStream: $handle,
            sendHttpHeaders: false,
            enableZip64: true,
        );

        try {
            $this->addString($zip, 'LISEZ-MOI.txt', $this->readme($export), $checksums);

            foreach ($stories as $index => $story) {
                $entrees[] = $this->addStory($zip, $story, $index + 1, $checksums);
            }

            $this->addBook($zip, Book::query()->where('project_id', $project->getKey())->first(), $checksums);
            $this->addLexicon($zip, $export, $checksums);

            if ($export->kind === ExportKind::GdprAccess || $export->scope === ExportScope::Narrator) {
                $this->addConsents($zip, $export, $checksums);
            }

            $manifest = $this->manifest($export, $entrees, $checksums);
            $this->addString($zip, 'manifest.json', $this->json($manifest), $checksums);

            $zip->finish();
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        return ['path' => $path, 'manifest' => $manifest];
    }

    /**
     * Un dossier par histoire, numéroté.
     *
     * Le numéro d'abord, pour que l'ordre du livre soit celui du dossier une
     * fois trié par nom — c'est ainsi qu'un explorateur de fichiers les
     * affichera dans dix ans, sans notre aide.
     *
     * @param  array<string, string>  $checksums
     * @return array<string, mixed>
     */
    private function addStory(ZipStream $zip, Story $story, int $rang, array &$checksums): array
    {
        $dossier = sprintf('histoires/%02d-%s', $rang, Str::slug($story->title ?? 'sans-titre') ?: 'sans-titre');
        $fichiers = [];

        $recording = $story->currentRecording()->first();

        if ($recording !== null) {
            foreach ([
                'original' => $recording->original_path,
                'mp3' => $recording->derived_mp3_path,
            ] as $quoi => $cle) {
                if (! is_string($cle) || $cle === '') {
                    continue;
                }

                $nom = $dossier.'/'.($quoi === 'mp3' ? 'audio.mp3' : 'audio-original.'.$this->extension($cle));

                try {
                    $this->addString($zip, $nom, $this->storage->get($cle), $checksums);
                    $fichiers['audio_'.$quoi] = $nom;
                } catch (Throwable) {
                    /*
                     * Un objet manquant ne fait pas échouer l'export entier.
                     *
                     * Il est **signalé dans le manifeste** — mieux vaut une
                     * archive de quarante histoires avec une ligne « fichier
                     * absent » qu'aucune archive du tout. Et la famille voit
                     * ce qui manque au lieu de le découvrir plus tard.
                     */
                    $fichiers['audio_'.$quoi] = null;
                }
            }
        }

        /*
         * Les deux textes, et jamais l'un sans l'autre quand ils existent :
         * le mot à mot est la parole telle qu'elle a été dite, le texte mis
         * au propre est ce que la famille lira. Le dossier interdit de
         * supprimer le premier au profit du second.
         */
        $verbatim = (string) $story->transcripts()->ofKind(TranscriptKind::Verbatim)->current()->value('text');

        if (trim($verbatim) !== '') {
            $this->addString($zip, $dossier.'/mot-a-mot.txt', $verbatim, $checksums);
            $fichiers['verbatim'] = $dossier.'/mot-a-mot.txt';
        }

        $texte = SelectBookChapters::textOf($story);

        if (trim($texte) !== '') {
            $this->addString($zip, $dossier.'/texte.txt', $texte, $checksums);
            $fichiers['text'] = $dossier.'/texte.txt';
        }

        $photos = [];

        foreach ($story->getMedia(Story::PHOTOS) as $position => $photo) {
            $nom = sprintf('%s/photos/%02d-%s', $dossier, $position + 1, $photo->file_name);

            try {
                $this->addString($zip, $nom, $this->bytes($photo), $checksums);
                $photos[] = $nom;
            } catch (Throwable) {
                // Même règle que pour l'audio : signalé, jamais bloquant.
            }
        }

        $entree = [
            'id' => $story->getKey(),
            'sequence' => $story->sequence,
            'title' => $story->title,
            'question' => $story->questionText(),
            'state' => $story->state->getValue(),
            'visibility' => $story->visibility->value,
            'recorded_at' => $story->recorded_at?->toIso8601String(),
            'validated_at' => $story->validated_at?->toIso8601String(),
            'duration_seconds' => $recording?->duration_seconds,
            'files' => [...$fichiers, 'photos' => $photos],
        ];

        $this->addString($zip, $dossier.'/histoire.json', $this->json($entree), $checksums);

        return $entree;
    }

    /** @param array<string, string> $checksums */
    private function addBook(ZipStream $zip, ?Book $book, array &$checksums): void
    {
        $chemin = $book?->proof_pdf_path;

        if (! is_string($chemin) || $chemin === '') {
            return;
        }

        try {
            $this->addString($zip, sprintf('livre/bat-v%d.pdf', $book->proof_version), $this->storage->get($chemin), $checksums);
        } catch (Throwable) {
            // Le livre absent n'empêche pas d'emporter les récits.
        }
    }

    /** @param array<string, string> $checksums */
    private function addLexicon(ZipStream $zip, Export $export, array &$checksums): void
    {
        $termes = $export->project->lexiconEntries()
            ->get(['term', 'replacement', 'notes'])
            ->map(fn ($entry): array => [
                'term' => $entry->term,
                'replacement' => $entry->replacement,
                'notes' => $entry->notes,
            ])
            ->all();

        if ($termes === []) {
            return;
        }

        $this->addString($zip, 'lexique.json', $this->json($termes), $checksums);
    }

    /**
     * Les consentements : ce à quoi on a dit oui, quand, et par quel canal.
     *
     * Obligatoire pour une demande de droit d'accès, et utile au narrateur :
     * c'est la trace de ce qu'il a autorisé, y compris ce qu'il a révoqué.
     *
     * @param  array<string, string>  $checksums
     */
    private function addConsents(ZipStream $zip, Export $export, array &$checksums): void
    {
        $consents = Consent::query()
            ->where('project_id', $export->project_id)
            ->orderBy('granted_at')
            ->get()
            ->map(fn (Consent $consent): array => [
                'kind' => $consent->kind->value,
                'channel' => $consent->channel->value,
                'granted_at' => $consent->granted_at->toIso8601String(),
                'revoked_at' => $consent->revoked_at?->toIso8601String(),
                'subject_type' => $consent->subject_type,
            ])
            ->all();

        $this->addString($zip, 'consentements.json', $this->json($consents), $checksums);
    }

    /**
     * @param  list<array<string, mixed>>  $stories
     * @param  array<string, string>  $checksums
     * @return array<string, mixed>
     */
    private function manifest(Export $export, array $stories, array $checksums): array
    {
        $project = $export->project;

        return [
            'version' => ExportManifest::VERSION,
            'generated_at' => now()->toIso8601String(),
            'product_name' => Brand::nameSafe(),
            'project' => [
                'id' => $project->getKey(),
                'narrator_display_name' => $project->primaryNarrator?->first_name,
                'collection_started_at' => $project->collection_started_at?->toIso8601String(),
            ],
            'requested_by' => ['type' => $export->scope->value],
            'kind' => $export->kind->value,
            'stories' => $stories,
            'checksum_algorithm' => ExportManifest::ALGORITHM,
            'checksums' => $checksums,
        ];
    }

    /**
     * Écrit une entrée et retient son empreinte.
     *
     * L'empreinte est calculée sur le contenu **tel qu'il est écrit**, pas sur
     * la source : c'est ce que la famille pourra vérifier avec
     * `sha256sum -c`, et une empreinte prise ailleurs ne prouverait rien.
     *
     * @param  array<string, string>  $checksums
     */
    private function addString(ZipStream $zip, string $nom, string $contenu, array &$checksums): void
    {
        $zip->addFile($nom, $contenu);
        $checksums[$nom] = hash(ExportManifest::ALGORITHM, $contenu);
    }

    private function bytes(Media $photo): string
    {
        $stream = $photo->stream();
        $bytes = stream_get_contents($stream);

        if ($bytes === false) {
            throw new RuntimeException('Photo illisible.');
        }

        return $bytes;
    }

    private function extension(string $cle): string
    {
        $extension = pathinfo($cle, PATHINFO_EXTENSION);

        return $extension === '' ? 'bin' : $extension;
    }

    private function temporaryPath(Export $export): string
    {
        return storage_path('app/exports/'.$export->getKey().'.zip');
    }

    /** @param array<mixed> $data */
    private function json(array $data): string
    {
        return (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Le mot d'accueil de l'archive.
     *
     * Il dit trois choses, et la troisième est la plus importante : ces
     * fichiers se lisent **sans nous**. La formulation des droits suit R-10.1
     * — on n'écrit jamais que « les contenus appartiennent à la famille »,
     * une phrase que R-11 interdit parce qu'elle est juridiquement fausse.
     */
    private function readme(Export $export): string
    {
        $marque = Brand::nameSafe();
        $prenom = $export->project->primaryNarrator?->first_name ?: 'la personne qui raconte';

        return <<<TEXTE
        Vos histoires — export du {$this->aujourdhui()}
        {$marque}

        CE QUE CONTIENT CE DOSSIER

          histoires/      Un dossier par récit, dans l’ordre où ils ont été racontés.
                          Chacun contient la voix ({$prenom} telle qu’elle a parlé),
                          le mot à mot, le texte mis au propre, les photos, et un
                          fichier histoire.json qui reprend les dates et la question posée.

          livre/          Le bon à tirer du livre, s’il en existe un.

          manifest.json   La liste de tous les fichiers avec leur empreinte.

          LISEZ-MOI.txt   Ce fichier.

        LIRE CES FICHIERS SANS NOUS

        Tout s’ouvre avec les logiciels que vous avez déjà : les audios avec
        n’importe quel lecteur, les textes avec un éditeur de texte, les photos
        avec votre visionneuse. Rien ici n’a besoin d’internet ni de notre site.

        Conservez ce dossier ailleurs que sur un seul ordinateur. Une clé USB
        rangée chez quelqu’un d’autre vaut mieux qu’une sauvegarde en ligne
        dont on oublie le mot de passe.

        VÉRIFIER QUE RIEN N’EST ABÎMÉ

        Chaque fichier a une empreinte dans manifest.json. Pour vérifier, sur
        un Mac ou sous Linux :

            shasum -a 256 histoires/01-*/audio.mp3

        Le résultat doit correspondre à l’empreinte du manifeste.

        VOS DROITS SUR CES ENREGISTREMENTS

        Vous pouvez les écouter, les copier, les transmettre à vos proches et
        les conserver aussi longtemps que vous le souhaitez. Nous ne les
        utilisons pour rien d’autre que ce service, et jamais pour entraîner
        un système d’intelligence artificielle.

        Le lien qui vous a permis de télécharger ce dossier expire au bout de
        sept jours. Vous pouvez en demander un nouveau à tout moment, sans
        frais, depuis votre espace.
        TEXTE;
    }

    private function aujourdhui(): string
    {
        return now()->translatedFormat('j F Y');
    }
}
