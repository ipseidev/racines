<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\SyncQuestionCorpus;
use App\Support\QuestionCorpus;
use Illuminate\Database\Seeder;

/**
 * Le corpus de questions, semé depuis `database/corpus/questions.php`.
 *
 * Le seeder et `corpus:sync` passent par la même action : le dev, les tests et
 * la production lisent le même fichier, de la même façon. En production, on
 * lance la commande, qui montre ce qu'elle va faire avant de le faire.
 *
 * L'ordre n'est pas cosmétique. Une première question intime fait raccrocher ;
 * une première question facile — « à quoi ressemblait la maison de votre
 * enfance ? » — fait parler dix minutes. `order_hint` porte cet ordre, et
 * `difficulty` permet au moteur de reculer quand quelqu'un s'essouffle.
 */
final class QuestionSeeder extends Seeder
{
    public function run(SyncQuestionCorpus $sync): void
    {
        $sync->apply(QuestionCorpus::default());
    }

    public static function count(): int
    {
        return count(QuestionCorpus::default()->entries());
    }
}
