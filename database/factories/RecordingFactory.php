<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RecordingSource;
use App\Enums\UploadStatus;
use App\Models\Recording;
use App\Models\Story;
use App\Support\ObjectKeys;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Recording>
 */
final class RecordingFactory extends Factory
{
    /** @var class-string<Recording> */
    protected $model = Recording::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            // L'identifiant est tiré ici : le chemin de l'objet en dépend, et
            // le déclencheur d'immuabilité refuse qu'on le pose après coup.
            'id' => (string) Str::uuid7(),
            'story_id' => Story::factory(),
            'source' => RecordingSource::Browser,
            'original_disk' => 'r2',
            'original_mime' => 'audio/webm',
            'upload_status' => UploadStatus::Initiated,
            'upload_id' => 'upload-'.fake()->unique()->numerify('######'),
            'is_current' => true,
            'device_info' => ['platform' => 'ios', 'browser' => 'safari'],
        ];
    }

    public function initiated(): static
    {
        return $this->state(fn (): array => ['upload_status' => UploadStatus::Initiated]);
    }

    /**
     * Enregistrement confirmé : le stockage a dit détenir l'objet.
     */
    public function confirmed(): static
    {
        return $this->afterMaking(function (Recording $recording): void {
            $recording->original_path = ObjectKeys::recordingSegment($recording, 1, 'webm');
            $recording->upload_status = UploadStatus::Completed;
            $recording->original_bytes = 1_024_000;
            $recording->confirmed_at = now();
            $recording->segments = [['number' => 1, 'bytes' => 1_024_000]];
        });
    }

    public function superseded(): static
    {
        return $this->state(fn (): array => ['is_current' => false]);
    }
}
