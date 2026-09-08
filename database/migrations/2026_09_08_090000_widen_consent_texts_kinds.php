<?php

declare(strict_types=1);

use App\Enums\ConsentKind;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;

/**
 * `consent_texts.kind` rattrape les quatre motifs ajoutés depuis sa création.
 *
 * `ConsentKind` est stocké dans **deux** tables. Quatre motifs sont arrivés
 * après le 2 septembre — `declared_sharing` (D-10), `mandate_delegation`,
 * puis `early_service_start` et `marketing_email` pour l'acheteur — et les
 * deux migrations qui les ont accueillis n'ont élargi que `consents.kind`.
 * `consent_texts.kind` est resté à huit valeurs.
 *
 * **Et rien ne pouvait le voir.** `EnumCheck::of(ConsentKind::class)` est
 * évalué au moment où la migration tourne, contre le code du jour : une base
 * créée par `migrate:fresh` obtient les douze motifs et paraît saine, une base
 * migrée pas à pas garde les huit d'alors. Les deux divergent en silence, la
 * suite de tests tourne toujours sur la première, et c'est la seconde qui est
 * en production. On l'a découvert en fabriquant un décor de production : le
 * semis des textes échouait sur `declared_sharing` (T-211).
 *
 * Ce que ça coûtait, tant que ça durait : aucun texte n'existait pour les
 * quatre motifs, donc `RecordConsent` levait — à raison, il refuse un accord
 * dont il ne pourrait pas dire ce qui avait été lu. Or `FulfillOrder` recueille
 * les deux accords de l'acheteur **dans sa transaction** : un client cochant
 * « démarrer tout de suite » voyait sa commande annulée, le webhook répondre
 * 500, et Stripe désactiver l'endpoint. La punition de T-169, pour une case.
 *
 * La leçon, portée en §13 des conventions : **ajouter un cas à une
 * énumération oblige à réémettre la contrainte de chaque table qui la
 * stocke**, et une seule ligne de `prod:check` peut le dire — elle compare
 * désormais les casts des modèles aux contraintes vivantes.
 */
return new class extends Migration
{
    /**
     * Les huit motifs d'origine, écrits en clair.
     *
     * Pas `EnumCheck::of()` : c'est exactement ce raccourci qui a fabriqué la
     * divergence, et un `down()` qui relit l'énumération du jour ne
     * redescendrait nulle part.
     *
     * @var list<string>
     */
    private const ORIGINAUX = [
        'voice_recording',
        'transcription',
        'ai_rendering',
        'family_sharing',
        'sensitive_categories',
        'phone_call_recording',
        'photo_rights',
        'post_mortem_directives',
    ];

    public function up(): void
    {
        EnumCheck::drop('consent_texts', 'kind');
        EnumCheck::add('consent_texts', 'kind', EnumCheck::of(ConsentKind::class));
    }

    public function down(): void
    {
        EnumCheck::drop('consent_texts', 'kind');
        EnumCheck::add('consent_texts', 'kind', self::ORIGINAUX);
    }
};
