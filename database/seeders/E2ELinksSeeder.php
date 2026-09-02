<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\AddNarrator;
use App\Actions\CreateProject;
use App\Actions\ProposeStory;
use App\Enums\Offer;
use App\Enums\ProjectStatus;
use App\Enums\QuestionTheme;
use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\Project;
use App\Models\Question;
use App\Models\Story;
use App\Models\User;
use App\Services\Tokens\TokenService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Liens d'enregistrement à valeur connue, pour la démonstration locale et
 * pour la suite bout en bout.
 *
 * **Un lien par scénario, sur sa propre histoire.** C'est le point important :
 * les tests Playwright tournent en parallèle, et un lien partagé faisait
 * qu'un test enregistrait l'histoire que le suivant s'attendait à trouver
 * vierge. L'isolation vit ici, pas dans un `--workers=1` qui masquerait le
 * problème.
 *
 * Ces liens n'existent que dans une base semée par ce seeder, qui refuse de
 * tourner en production. Deux d'entre eux sont morts par construction.
 */
final class E2ELinksSeeder extends Seeder
{
    private const OWNER_EMAIL = 'liens@example.test';

    /** @var array<string, string> */
    private const SCENARIOS = [
        'record' => 'Racontez-nous votre premier jour d’école.',
        'guard' => 'Quel objet avez-vous gardé de votre enfance ?',
        'resume' => 'Quel était le métier de votre père ?',
        'denied' => 'Quelle chanson vous rappelle votre jeunesse ?',
        'a11y' => 'Comment était la cuisine de votre enfance ?',
        'budget' => 'Quel voyage vous a le plus marqué ?',
        'expired' => 'Quelle est votre plus belle rencontre ?',
        'revoked' => 'Qu’aimeriez-vous que l’on retienne de vous ?',
    ];

    /** @var array<string, array<string, mixed>> */
    private const LINK_STATE = [
        'expired' => ['expires_at' => '-1 day'],
        'revoked' => ['revoked_at' => '-1 hour'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Ce seeder ne doit pas tourner en production.');
        }

        // Le décor comprend les compteurs de limitation de débit, qui vivent
        // dans le cache et survivent à `migrate:fresh`. Sans ce vidage, le
        // scénario « demander un nouveau lien » — une demande par heure et par
        // jeton, et c'est la bonne règle produit — ne passe qu'une fois par
        // heure sur la machine, et échoue au deuxième appel de la suite.
        Cache::flush();

        $owner = User::query()->firstOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Banc d’essai',
                'password' => Hash::make((string) config('product.seeding.admin_password')),
                'email_verified_at' => now(),
            ],
        );

        $project = Project::query()->where('owner_user_id', $owner->id)->first();

        if ($project instanceof Project) {
            return;
        }

        $project = app(CreateProject::class)->handle($owner, Offer::Pilot, []);
        $project->status = ProjectStatus::Active;
        $project->save();

        app(AddNarrator::class)->handle($project, [
            'first_name' => 'Odette',
            'display_name' => 'Odette',
            'phone_e164' => '+33600000001',
            'birth_year' => 1943,
        ]);

        $project->refresh();

        foreach (self::SCENARIOS as $scenario => $text) {
            $question = Question::query()->firstOrCreate(
                ['slug' => 'e2e-'.$scenario],
                ['text' => $text, 'theme' => QuestionTheme::Childhood],
            );

            $story = app(ProposeStory::class)->handle($project, $question);

            $this->seedLink($story, $scenario);
        }
    }

    /**
     * La ligne est insérée sans `TokenService::issue()`, qui tire un jeton
     * aléatoire par définition. Même parti que les états de `StoryFactory` :
     * un seeder construit un décor.
     */
    private function seedLink(Story $story, string $scenario): void
    {
        $token = new AccessToken([
            'type' => TokenType::Record,
            'scope' => ['record', 'decide_share'],
            'expires_at' => now()->addDays(30),
        ]);

        $token->token_hash = TokenService::hash(self::token($scenario));
        $token->subject()->associate($story);

        foreach (self::LINK_STATE[$scenario] ?? [] as $column => $offset) {
            $token->{$column} = now()->modify((string) $offset);
        }

        $token->save();
    }

    /** Valeur connue d'un lien, complétée à 43 caractères. */
    public static function token(string $scenario): string
    {
        return str_pad("demo-{$scenario}-link", 43, 'x');
    }
}
