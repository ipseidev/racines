<?php

declare(strict_types=1);

use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le narrateur peut se filmer, pas seulement s'enregistrer (T-210).
 *
 * Trois colonnes et une contrainte élargie, et rien d'autre : la vidéo est
 * additive. Sa piste sonore produit le même `derived_mp3_path` que la voix
 * seule, donc la transcription, le rendu Fluide, le QR du livre et l'export
 * continuent de lire ce qu'ils lisaient.
 *
 * `derived_mp4_path` existe pour une raison très concrète : Chrome Android
 * rend du WebM, qu'un iPhone ne sait pas lire. Sans dérivé, une grand-mère
 * filmée sur Android serait invisible pour la moitié de sa famille — le même
 * problème que le MP3 résout depuis le bloc 06 pour le son.
 *
 * L'original, lui, ne bouge pas : le déclencheur d'immuabilité vaut pour la
 * vidéo comme pour la voix.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $kinds = ['audio', 'video'];

    /**
     * Les formes de réponse, `video` comprise (P0-5).
     *
     * @var list<string>
     */
    private array $answerTypes = ['audio', 'text', 'phone', 'video'];

    public function up(): void
    {
        Schema::table('recordings', function (Blueprint $table): void {
            $table->string('kind', 16)->default('audio')->after('source');
            $table->string('derived_mp4_path')->nullable()->after('derived_mp3_path');
        });

        EnumCheck::add('recordings', 'kind', $this->kinds);

        // La contrainte de `stories.answer_type` a été posée à partir de
        // l'énumération d'alors : elle ignore `video` et refuserait la
        // transition d'une histoire filmée.
        EnumCheck::drop('stories', 'answer_type');
        EnumCheck::add('stories', 'answer_type', $this->answerTypes, nullable: true);
    }

    public function down(): void
    {
        EnumCheck::drop('stories', 'answer_type');
        EnumCheck::add('stories', 'answer_type', ['audio', 'text', 'phone'], nullable: true);

        EnumCheck::drop('recordings', 'kind');

        Schema::table('recordings', function (Blueprint $table): void {
            $table->dropColumn(['kind', 'derived_mp4_path']);
        });
    }
};
