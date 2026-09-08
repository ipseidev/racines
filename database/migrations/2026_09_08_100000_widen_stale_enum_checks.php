<?php

declare(strict_types=1);

use App\Enums\ConsentKind;
use App\Enums\PhoneOptionEntry;
use App\Enums\SupportTicketKind;
use App\Support\Database\EnumCheck;
use Illuminate\Database\Migrations\Migration;

/**
 * Trois contraintes rattrapent les cas ajoutés depuis leur dernière émission.
 *
 * Même mécanisme que T-222, trouvé par la ligne de `prod:check` que T-222
 * avait justement fait naître : `EnumCheck::of($enum)` est évalué au moment où
 * la migration tourne, donc une base migrée pas à pas garde la liste d'alors.
 * Trois colonnes avaient divergé en production, et chacune coûtait une erreur
 * 500 sur un chemin qu'un client emprunte :
 *
 *  - **`support_tickets.kind`** refusait `erasure_requested` : une **demande
 *    d'effacement** échouait. C'est une obligation légale, et le doc 04 §2 en
 *    fait un engagement — la plus grave des trois. Elle refusait aussi
 *    `print_order` et `print_defect`, donc commander le livre et signaler un
 *    défaut d'impression (bloc 13).
 *  - **`consents.kind`** refusait `declared_sharing` : la déclaration
 *    d'avance de D-10 ne pouvait pas être consentie, alors qu'elle est
 *    précisément le consentement qui la rend licite (R-4).
 *  - **`phone_options.entry`** refusait `complement` : compléter une commande
 *    déjà passée par l'option téléphone échouait (T-184).
 *
 * `up()` réémet depuis l'énumération du jour, ce qui est juste dans tous les
 * environnements. `down()`, lui, **nomme le delta** au lieu de recopier douze
 * littéraux : c'est ce que cette migration ajoute, et c'est la seule chose
 * qu'un retour arrière doit retirer. Là où T-222 écrivait les huit motifs
 * d'origine en clair, le delta était de quatre sur douze ; ici il est de un,
 * un et trois, et la liste des retirés se lit mieux que celle des gardés.
 */
return new class extends Migration
{
    /**
     * Ce que cette migration ajoute, colonne par colonne.
     *
     * @var array<string, list<string>>
     */
    private const AJOUTS = [
        'consents.kind' => ['declared_sharing'],
        'phone_options.entry' => ['complement'],
        'support_tickets.kind' => ['print_order', 'print_defect', 'erasure_requested'],
    ];

    /** @var array<string, class-string<BackedEnum>> */
    private const ENUMS = [
        'consents.kind' => ConsentKind::class,
        'phone_options.entry' => PhoneOptionEntry::class,
        'support_tickets.kind' => SupportTicketKind::class,
    ];

    public function up(): void
    {
        foreach (self::ENUMS as $colonne => $enum) {
            [$table, $champ] = explode('.', $colonne, 2);

            EnumCheck::drop($table, $champ);
            EnumCheck::add($table, $champ, EnumCheck::of($enum));
        }
    }

    public function down(): void
    {
        foreach (self::ENUMS as $colonne => $enum) {
            [$table, $champ] = explode('.', $colonne, 2);

            EnumCheck::drop($table, $champ);
            EnumCheck::add($table, $champ, array_values(array_diff(
                EnumCheck::of($enum),
                self::AJOUTS[$colonne],
            )));
        }
    }
};
