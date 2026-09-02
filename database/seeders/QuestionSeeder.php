<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionTheme;
use App\Models\Question;
use Illuminate\Database\Seeder;

/**
 * Le corpus de questions v1 (annexe A) : soixante questions françaises,
 * séquencées du facile vers l'intime.
 *
 * L'ordre n'est pas cosmétique. Une première question intime fait raccrocher ;
 * une première question facile — « à quoi ressemblait la maison de votre
 * enfance ? » — fait parler dix minutes. `order_hint` porte cet ordre, et
 * `difficulty` permet au moteur de reculer quand quelqu'un s'essouffle.
 *
 * Les slugs sont stables : ce sont eux que citent les tests et les analyses.
 * On n'en renumérote aucun ; on ajoute en fin de liste.
 */
final class QuestionSeeder extends Seeder
{
    /** @var list<array{string, QuestionTheme, int, int, string}> */
    private const CORPUS = [
        ['naissance-recit', QuestionTheme::Childhood, 1, 10, 'Où êtes-vous né·e, et que vous a-t-on raconté sur le jour de votre naissance ?'],
        ['premier-souvenir', QuestionTheme::Childhood, 1, 20, 'Quel est votre tout premier souvenir ?'],
        ['maison-enfance', QuestionTheme::Childhood, 1, 30, 'À quoi ressemblait la maison de votre enfance ? Décrivez une pièce que vous aimiez.'],
        ['betise-enfant', QuestionTheme::Childhood, 1, 40, 'Quel jeu ou quelle bêtise d\'enfant vous fait encore sourire ?'],
        ['apprendre-velo-nager-lire', QuestionTheme::Childhood, 1, 50, 'Qui vous a appris à faire du vélo, à nager ou à lire ? Racontez ce moment.'],
        ['odeur-enfance', QuestionTheme::Childhood, 1, 60, 'Quelle était votre odeur préférée quand vous étiez enfant, et à quoi vous ramène-t-elle ?'],
        ['plat-enfance', QuestionTheme::Childhood, 1, 70, 'Quel plat de votre enfance aimeriez-vous goûter une dernière fois ?'],
        ['dimanche-dix-ans', QuestionTheme::Childhood, 1, 80, 'Comment se passait un dimanche ordinaire chez vous quand vous aviez dix ans ?'],
        ['grands-parents', QuestionTheme::FamilyOrigins, 2, 90, 'Que savez-vous de vos grands-parents ? Racontez-les comme si on ne les avait jamais rencontrés.'],
        ['nom-de-famille', QuestionTheme::FamilyOrigins, 2, 100, 'D\'où vient votre nom de famille, et quelle histoire y est attachée ?'],
        ['expression-famille', QuestionTheme::FamilyOrigins, 2, 110, 'Quelle expression ou quel mot venait de votre famille et n\'existait nulle part ailleurs ?'],
        ['conteur-famille', QuestionTheme::FamilyOrigins, 2, 120, 'Qui, dans votre famille, racontait le mieux les histoires ? Laquelle vous revient ?'],
        ['objet-transmis', QuestionTheme::FamilyOrigins, 2, 130, 'Y a-t-il un objet transmis dans la famille ? Racontez son histoire.'],
        ['tradition-gardee', QuestionTheme::FamilyOrigins, 2, 140, 'Quelle tradition de votre enfance avez-vous gardée, et laquelle avez-vous abandonnée ?'],
        ['adulte-qui-a-compte', QuestionTheme::Youth, 2, 150, 'Quel professeur ou adulte, en dehors de vos parents, a compté pour vous ?'],
        ['musique-quinze-ans', QuestionTheme::Youth, 2, 160, 'Quelle musique écoutiez-vous à quinze ans, et où l\'écoutiez-vous ?'],
        ['premiere-liberte', QuestionTheme::Youth, 2, 170, 'Racontez votre première grande liberté : un voyage, une sortie, un départ.'],
        ['reve-metier', QuestionTheme::Youth, 2, 180, 'Quel était votre rêve de métier à dix-huit ans ?'],
        ['mode-jeunesse', QuestionTheme::Youth, 2, 190, 'Quelle mode ou quelle habitude de votre jeunesse ferait rire les jeunes d\'aujourd\'hui ?'],
        ['ami-perdu-de-vue', QuestionTheme::Youth, 2, 200, 'Racontez un ami d\'enfance ou de jeunesse que vous avez perdu de vue. Que faisiez-vous ensemble ?'],
        ['evenement-du-monde', QuestionTheme::Youth, 2, 210, 'Quel événement du monde vous a marqué·e quand vous étiez jeune, et où étiez-vous ce jour-là ?'],
        ['premier-jour-travail', QuestionTheme::Work, 2, 220, 'Racontez votre premier jour de travail.'],
        ['metier-fierte', QuestionTheme::Work, 2, 230, 'Quel a été le métier dont vous êtes le plus fier·ère, et pourquoi ?'],
        ['travail-et-les-gens', QuestionTheme::Work, 2, 240, 'Qu\'est-ce que votre travail vous a appris sur les gens ?'],
        ['journee-de-travail', QuestionTheme::Work, 2, 250, 'Racontez une journée de travail ordinaire à l\'époque où vous étiez le plus occupé·e.'],
        ['choix-professionnel', QuestionTheme::Work, 3, 260, 'Y a-t-il un choix professionnel que vous referiez autrement ?'],
        ['qui-a-donne-sa-chance', QuestionTheme::Work, 2, 270, 'Qui vous a donné votre chance, et comment ?'],
        ['rencontre-conjoint', QuestionTheme::Love, 3, 280, 'Comment avez-vous rencontré la personne qui a partagé votre vie ?'],
        ['mariage-ou-vie-commune', QuestionTheme::Love, 3, 290, 'Racontez votre mariage, ou le jour où vous avez décidé de vivre ensemble.'],
        ['premier-enfant', QuestionTheme::Love, 3, 300, 'Qu\'avez-vous ressenti en tenant votre premier enfant dans vos bras ?'],
        ['conseil-couple', QuestionTheme::Love, 3, 310, 'Quel conseil vous a-t-on donné sur le couple qui s\'est révélé vrai ?'],
        ['dispute-fou-rire', QuestionTheme::Love, 3, 320, 'Racontez une dispute qui s\'est terminée en fou rire.'],
        ['qualite-pere-mere', QuestionTheme::Love, 3, 330, 'Quelle qualité admiriez-vous le plus chez votre père ? Et chez votre mère ?'],
        ['avec-les-enfants', QuestionTheme::Love, 3, 340, 'Que faisiez-vous avec vos enfants quand ils étaient petits, que vous aimeriez qu\'ils se rappellent ?'],
        ['surnoms-famille', QuestionTheme::Love, 2, 350, 'Quel surnom donnait-on dans la famille, et d\'où venait-il ?'],
        ['lieu-qui-manque', QuestionTheme::Places, 3, 360, 'Quel lieu vous manque le plus, et que reste-t-il de lui aujourd\'hui ?'],
        ['voyage-qui-change', QuestionTheme::Places, 3, 370, 'Racontez un voyage qui vous a changé·e.'],
        ['ville-village-avant', QuestionTheme::Places, 2, 380, 'À quoi ressemblait votre ville ou votre village quand vous étiez jeune ? Qu\'est-ce qui a disparu ?'],
        ['cachette', QuestionTheme::Places, 2, 390, 'Quelle était votre cachette ou votre endroit à vous quand vous vouliez être seul·e ?'],
        ['maison-quittee', QuestionTheme::Places, 3, 400, 'Racontez une maison où vous avez vécu et qu\'il a fallu quitter.'],
        ['plus-beau-jour', QuestionTheme::Joys, 3, 410, 'Quel a été le plus beau jour de votre vie, ou l\'un des plus beaux ?'],
        ['plus-grande-fierte', QuestionTheme::Joys, 3, 420, 'De quoi êtes-vous le plus fier·ère, sans fausse modestie ?'],
        ['cadeau-touchant', QuestionTheme::Joys, 3, 430, 'Quel cadeau reçu vous a le plus touché·e ?'],
        ['fou-rire', QuestionTheme::Joys, 2, 440, 'Racontez un fou rire dont vous vous souvenez encore.'],
        ['petite-habitude-heureuse', QuestionTheme::Joys, 2, 450, 'Quelle petite habitude vous rend heureux·se au quotidien ?'],
        ['epreuve-la-plus-dure', QuestionTheme::Hardships, 4, 460, 'Quelle a été l\'épreuve la plus dure, et qu\'est-ce qui vous a aidé·e à la traverser ?'],
        ['revoir-une-derniere-fois', QuestionTheme::Hardships, 4, 470, 'Y a-t-il une personne que vous auriez aimé revoir une dernière fois ? Que lui diriez-vous ?'],
        ['grande-peur', QuestionTheme::Hardships, 4, 480, 'Racontez un moment où vous avez eu très peur.'],
        ['decision-difficile', QuestionTheme::Hardships, 4, 490, 'Quelle décision difficile avez-vous prise, et la referiez-vous ?'],
        ['lecon-echec', QuestionTheme::Hardships, 4, 500, 'Qu\'avez-vous appris d\'un échec ?'],
        ['vie-reussie', QuestionTheme::BeliefsValues, 4, 510, 'Qu\'est-ce qui, selon vous, fait une vie réussie ?'],
        ['valeur-transmise', QuestionTheme::BeliefsValues, 4, 520, 'Quelle est la valeur que vous avez essayé de transmettre avant toutes les autres ?'],
        ['croyance-changee', QuestionTheme::BeliefsValues, 4, 530, 'Qu\'est-ce que vous croyez aujourd\'hui que vous ne croyiez pas à vingt ans ?'],
        ['priere-poeme-chanson', QuestionTheme::BeliefsValues, 4, 540, 'Y a-t-il une prière, un poème ou une chanson qui vous accompagne ?'],
        ['monde-des-petits-enfants', QuestionTheme::BeliefsValues, 4, 550, 'Que pensez-vous du monde que vos petits-enfants vont connaître ?'],
        ['conseil-dix-huit-ans', QuestionTheme::Legacy, 5, 560, 'Quel conseil donneriez-vous à votre petit-fils ou votre petite-fille pour ses dix-huit ans ?'],
        ['ce-quon-retienne', QuestionTheme::Legacy, 5, 570, 'Qu\'aimeriez-vous que l\'on retienne de vous ?'],
        ['geste-recette-secret', QuestionTheme::Legacy, 3, 580, 'Y a-t-il une recette, un geste ou un savoir-faire que vous seul·e savez faire ? Décrivez-le pas à pas.'],
        ['histoire-jamais-racontee', QuestionTheme::Legacy, 5, 590, 'Quelle histoire n\'avez-vous jamais racontée à personne et pourriez-vous raconter aujourd\'hui ?'],
        ['message-dans-cinquante-ans', QuestionTheme::Legacy, 5, 600, 'Si vous pouviez laisser un message à ceux qui écouteront ces enregistrements dans cinquante ans, que diriez-vous ?'],
    ];

    public function run(): void
    {
        foreach (self::CORPUS as [$slug, $theme, $difficulty, $order, $text]) {
            Question::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'text' => $text,
                    'theme' => $theme,
                    'difficulty' => $difficulty,
                    'order_hint' => $order,
                    'is_active' => true,
                    'locale' => 'fr',
                ],
            );
        }
    }

    public static function count(): int
    {
        return count(self::CORPUS);
    }
}
