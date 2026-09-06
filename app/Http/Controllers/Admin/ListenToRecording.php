<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Audit\AuditLog;
use App\Models\Recording;
use App\Models\Story;
use App\Models\User;
use App\Services\Storage\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Écouter un enregistrement depuis le back-office, et le dire.
 *
 * Le dossier exige la journalisation des **lectures** et pas seulement des
 * écritures : un support qui écoute l'histoire de quelqu'un laisse une trace,
 * faute de quoi le back-office n'est qu'un accès libre aux souvenirs d'une
 * famille.
 *
 * Une route plutôt qu'une action Filament : une action qui **retourne** une
 * URL ne navigue pas, et la première version journalisait l'écoute sans rien
 * ouvrir (T-182). Ici la trace et l'ouverture sont le même geste — on ne peut
 * pas obtenir l'une sans l'autre.
 *
 * L'URL rendue vit **soixante secondes**. C'est court à dessein : elle donne
 * accès à la voix de quelqu'un sans passer par nos contrôles, et une adresse
 * qui traîne dans un historique de navigation en donnerait encore demain.
 */
final readonly class ListenToRecording
{
    public function __invoke(Request $request, Story $story): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->can('support.read'), 403);

        $recording = $story->currentRecording()->first();

        abort_unless($recording instanceof Recording, 404);

        $key = $recording->derived_mp3_path ?? $recording->original_path;

        abort_unless(is_string($key) && $key !== '', 404);

        AuditLog::record('played Recording', $recording, [
            'story_id' => $story->id,
        ], $story->project);

        return redirect()->away(app(MediaStorage::class)->temporaryUrl($key, 60));
    }
}
