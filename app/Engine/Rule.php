<?php

declare(strict_types=1);

namespace App\Engine;

use App\Enums\EngineAudience;
use App\Enums\EngineRuleId;
use App\Models\EngineEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Une des onze règles du moteur de complétion (annexe C).
 *
 * Le partage des rôles est délibéré : la règle **détecte** et **agit**, le
 * tick **enregistre**. Aucune règle n'écrit dans `engine_events` — la ligne
 * est insérée par le tick, avant l'action, dans la même transaction, et sa
 * clé unique porte l'idempotence. Si chaque règle écrivait la sienne, onze
 * implémentations pourraient se tromper sur l'ordre, et celle qui se
 * tromperait enverrait deux fois le même message.
 */
interface Rule
{
    public function id(): EngineRuleId;

    /**
     * À qui cette occurrence parle. Chaque public a sa limite, et elles ne se
     * ressemblent pas (voir `EngineAudience`).
     *
     * Le public dépend de l'occurrence et non de la règle, parce qu'une même
     * règle change parfois d'interlocuteur : l'invitation restée sans réponse
     * relance le narrateur à J+7, puis en parle à l'Initiateur·rice à J+14.
     * Un public fixe ferait compter la seconde alerte dans le quota du
     * premier.
     */
    public function audience(Occurrence $occurrence): EngineAudience;

    /**
     * Les occasions de parler, en **une requête** — jamais une boucle PHP sur
     * tous les projets : à mille familles, le tick horaire n'y survivrait pas.
     *
     * @return Collection<int, Occurrence>
     */
    public function detect(CarbonImmutable $now): Collection;

    /**
     * La limite anti-culpabilisation propre à la règle : deux rappels et on
     * se taît, une alerte par mois, un nudge par histoire.
     */
    public function isCapped(Occurrence $occurrence): bool;

    /**
     * Agit, et rend ce qu'il faut consigner dans `action_taken`.
     *
     * @return array<string, mixed>
     */
    public function fire(Occurrence $occurrence): array;

    /**
     * La reprise attendue a-t-elle eu lieu ?
     *
     * `null` signifie « on ne sait pas encore » : le job de mesure repassera.
     * `false` signifie « le délai est écoulé, ça n'a rien produit » — et c'est
     * un résultat aussi précieux que l'autre.
     */
    public function resumed(EngineEvent $event, CarbonImmutable $now): ?bool;
}
