<?php

declare(strict_types=1);

namespace App\Http\Controllers\Narrator;

use App\Actions\SubmitWrittenAnswer;
use App\Http\Requests\Narrator\WrittenAnswerRequest;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;

/**
 * Réponse écrite : le repli quand le micro est refusé, ou le choix de
 * quelqu'un qui préfère écrire (P0-5).
 */
final readonly class WrittenAnswerController
{
    public function __construct(private SubmitWrittenAnswer $action) {}

    public function store(WrittenAnswerRequest $request, string $token): RedirectResponse
    {
        $story = $request->attributes->get('token_subject');

        abort_unless($story instanceof Story, 404);

        $this->action->handle($story, (string) $request->validated('written_answer'));

        return redirect()
            ->route('narrator.record.show', ['token' => $token])
            ->with('status', __('narrator.written_answer.sent'));
    }
}
