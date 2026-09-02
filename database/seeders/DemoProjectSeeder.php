<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\AddFamilyMember;
use App\Actions\AddNarrator;
use App\Actions\CreateProject;
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
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Un projet de démonstration, pour développer et pour les tests bout en bout
 * des blocs suivants.
 *
 * Cinq histoires dans cinq états, dont une seule visible des proches : c'est
 * ce qui permet de vérifier d'un coup d'œil, sur n'importe quel écran, que la
 * règle de visibilité tient. Le seeder est rejouable sans doublon.
 */
final class DemoProjectSeeder extends Seeder
{
    private const OWNER_EMAIL = 'demo@example.test';

    /** @var list<string> */
    private const STATES = ['proposed', 'recorded', 'to_review', 'shared', 'hidden'];

    /** @var array<string, array{string, QuestionTheme}> */
    private const QUESTIONS = [
        'proposed' => ['Quel est votre premier souvenir d’école ?', QuestionTheme::Childhood],
        'recorded' => ['Comment vos parents se sont-ils rencontrés ?', QuestionTheme::FamilyOrigins],
        'to_review' => ['Quel métier rêviez-vous de faire enfant ?', QuestionTheme::Work],
        'shared' => ['Racontez-nous la maison de votre enfance.', QuestionTheme::Places],
        'hidden' => ['Quelle épreuve vous a le plus appris ?', QuestionTheme::Hardships],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Ce seeder ne doit pas tourner en production.');
        }

        $owner = User::query()->firstOrCreate(
            ['email' => self::OWNER_EMAIL],
            [
                'name' => 'Démonstration',
                'password' => Hash::make((string) config('product.seeding.admin_password')),
                'email_verified_at' => now(),
            ],
        );

        $project = Project::query()->where('owner_user_id', $owner->id)->first();

        if ($project instanceof Project) {
            return;
        }

        $project = app(CreateProject::class)->handle($owner, Offer::Pilot, ['prompt_day' => 3]);
        $project->status = ProjectStatus::Active;
        $project->save();
        $project->startCollection();

        app(AddNarrator::class)->handle($project, [
            'first_name' => 'Marie',
            'last_name' => 'Delaunay',
            'display_name' => 'Marie',
            'phone_e164' => '+33600000000',
            'birth_year' => 1941,
        ]);

        foreach ([['Camille', 'Petite-fille'], ['Julien', 'Fils'], ['Alice', 'Nièce']] as [$name, $relationship]) {
            app(AddFamilyMember::class)->handle($project, $owner, [
                'display_name' => $name,
                'relationship' => $relationship,
                'email' => mb_strtolower($name).'@example.test',
            ]);
        }

        $project->refresh();

        foreach (self::STATES as $index => $state) {
            [$text, $theme] = self::QUESTIONS[$state];

            $question = Question::query()->firstOrCreate(
                ['slug' => 'demo-'.$state],
                ['text' => $text, 'theme' => $theme, 'order_hint' => $index + 1],
            );

            $story = Story::factory()
                ->forProject($project)
                ->{$this->factoryState($state)}()
                ->create(['question_id' => $question->id]);

            if ($state === 'proposed') {
                $this->seedDemoLinks($story);
            }
        }
    }

    /**
     * Trois liens d'enregistrement à valeur connue : un valable, un expiré, un
     * révoqué.
     *
     * Ils permettent d'ouvrir les trois écrans en local sans passer par
     * tinker, et de les éprouver de bout en bout. Ils n'existent que dans une
     * base semée par ce seeder, qui refuse de tourner en production ; deux des
     * trois sont morts par construction.
     *
     * Les lignes sont insérées directement, sans `TokenService::issue()`, qui
     * tire un jeton aléatoire par définition. C'est le même parti que les
     * états de `StoryFactory` : un seeder construit un décor.
     */
    private function seedDemoLinks(Story $story): void
    {
        $links = [
            self::demoToken('record') => [],
            self::demoToken('expired') => ['expires_at' => now()->subDay()],
            self::demoToken('revoked') => ['revoked_at' => now()->subHour()],
        ];

        foreach ($links as $plain => $attributes) {
            $token = new AccessToken([
                'type' => TokenType::Record,
                'scope' => ['record', 'decide_share'],
                'expires_at' => now()->addDays(30),
            ]);

            $token->token_hash = TokenService::hash($plain);
            $token->subject()->associate($story);
            $token->forceFill($attributes);
            $token->save();
        }
    }

    /**
     * Valeur connue d'un lien de démonstration, complétée à 43 caractères.
     */
    public static function demoToken(string $name): string
    {
        return str_pad("demo-{$name}-link", 43, 'x');
    }

    /**
     * Les fabriques nomment les états en camel : `to_review` devient
     * `toReview()`, `in_book` deviendrait `inBook()`.
     */
    private function factoryState(string $state): string
    {
        return lcfirst(str_replace('_', '', ucwords($state, '_')));
    }
}
