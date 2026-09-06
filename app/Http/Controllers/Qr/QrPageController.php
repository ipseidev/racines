<?php

declare(strict_types=1);

namespace App\Http\Controllers\Qr;

use App\Enums\AnalyticsEvent;
use App\Models\Story;
use App\Services\Analytics\Analytics;
use App\Services\Storage\MediaStorage;
use App\Support\Brand;
use App\Support\FamilyPresenter;
use App\Support\QrFamilyCode;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * La page qu'ouvre un QR imprimé dans le livre.
 *
 * **Lisible sans compte, par défaut** (doc 04 §7). Un livre se prête, se
 * transmet, se lit chez quelqu'un d'autre : demander une identification pour
 * entendre la voix qu'on tient entre les mains serait absurde, et c'est
 * précisément ce que la page imprimée promet.
 *
 * Le code famille est **facultatif** et posé par la famille elle-même. Quand
 * il existe, il est demandé une fois puis retenu trente jours dans un cookie
 * signé — un code redemandé à chaque scan finirait sur un post-it collé dans
 * le livre.
 *
 * Trois choses n'existent pas ici, et leur absence est le sujet : pas de
 * liste des autres histoires — un QR ouvre un chapitre, pas une bibliothèque ;
 * pas de réactions ni de dépôt de photo — ils ont un auteur, et on ne sait pas
 * qui lit.
 */
final class QrPageController
{
    public function __invoke(
        Request $request,
        string $token,
        MediaStorage $storage,
        Analytics $analytics,
    ): Response {
        $story = $request->attributes->get('token_subject');

        if (! $story instanceof Story) {
            abort(404);
        }

        /*
         * Le retrait vaut aussi pour l'imprimé.
         *
         * Une histoire masquée après l'impression ne peut plus être retirée du
         * papier — mais elle peut cesser d'être servie. La page le dit sans
         * accuser personne et sans laisser croire à une panne : le livre reste,
         * la voix ne s'écoute plus en ligne.
         */
        if (! $story->isVisibleToFamily()) {
            return inertia('qr/Unavailable', [
                'brandName' => Brand::nameSafe(),
            ]);
        }

        if (! QrFamilyCode::isUnlocked($request, $story->project)) {
            return inertia('qr/FamilyCode', [
                'token' => $token,
                'narratorFirstName' => $story->narrator->first_name,
            ]);
        }

        $analytics->capture(AnalyticsEvent::StoryPageOpened, [
            'story_id' => $story->id,
            'project_id' => $story->project_id,
            'source' => 'qr',
        ]);

        return inertia('family/Story', [
            'narratorFirstName' => $story->narrator->first_name,
            ...FamilyPresenter::qrStoryProps($story, $storage),
            'siblings' => [],
        ]);
    }
}
