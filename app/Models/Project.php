<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\AddressForm;
use App\Enums\BookCover;
use App\Enums\BookTitle;
use App\Enums\Cadence;
use App\Enums\Locale;
use App\Enums\Offer;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Enums\PromptSlot;
use App\Enums\ValidationVariant;
use App\Support\Product\ServiceWindow;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Un projet : un livre, un narrateur principal, une Initiateur·rice
 * propriétaire (glossaire §2).
 *
 * @property string $id
 * @property int $owner_user_id
 * @property string|null $cohort_id
 * @property ProjectStatus $status
 * @property Offer $offer
 * @property AddressForm $address_form
 * @property BookCover $book_cover
 * @property BookTitle $book_title
 * @property string|null $book_title_custom
 * @property Locale $locale
 * @property Cadence $cadence
 * @property int $prompt_day
 * @property PromptSlot $prompt_slot
 * @property string $timezone
 * @property CarbonImmutable|null $next_prompt_at
 * @property CarbonImmutable|null $paused_until
 * @property CarbonImmutable|null $collection_started_at
 * @property CarbonImmutable|null $collection_ends_at
 * @property CarbonImmutable|null $finalization_ends_at
 * @property CarbonImmutable|null $hosting_ends_at
 * @property CarbonImmutable|null $erased_at
 * @property ValidationVariant $validation_variant
 * @property string|null $gift_message
 * @property string|null $gift_audio_recording_id
 * @property CarbonImmutable|null $gift_send_at
 * @property CarbonImmutable|null $gift_sent_at
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $declared_sharing_at
 * @property CarbonImmutable|null $refused_at
 * @property string|null $refusal_reason
 * @property string|null $family_code_hash
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $owner
 * @property-read Narrator|null $primaryNarrator Un projet tout juste créé n'en a pas encore.
 * @property-read ProjectProfile|null $profile
 */
final class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUuids, StoresDatesWithOffset;

    /**
     * Reprend les valeurs par défaut de la migration : une instance qui sort
     * d'une action doit être lisible sans `refresh()`.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ProjectStatus::Draft->value,
        'address_form' => AddressForm::Vous->value,
        'cadence' => Cadence::Weekly->value,
        'prompt_day' => 1,
        'prompt_slot' => PromptSlot::Morning->value,
        'timezone' => 'Europe/Paris',
        'locale' => Locale::French->value,
        'validation_variant' => ValidationVariant::Immediate->value,
        // Posée ici et pas seulement en base : le défaut de colonne ne
        // s'applique qu'à l'insertion, et `RenderBookHtml` lit `book_cover`
        // sur des instances qui viennent parfois d'ailleurs.
        'book_cover' => 'ivory',
        'book_title' => 'first_name',
    ];

    /** @var list<string> */
    protected $fillable = [
        'cohort_id', 'status', 'offer', 'address_form', 'cadence', 'prompt_day',
        'prompt_slot', 'timezone', 'locale', 'gift_message', 'gift_send_at', 'validation_variant',
        'book_cover', 'book_title', 'book_title_custom',
    ];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** @return BelongsTo<Cohort, $this> */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /** @return HasMany<ProjectMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /** @return HasMany<Narrator, $this> */
    public function narrators(): HasMany
    {
        return $this->hasMany(Narrator::class);
    }

    /**
     * Le narrateur principal. Plusieurs narrateurs existent en base ; un seul
     * est principal, garanti par un index unique partiel (PRD §2).
     *
     * @return HasOne<Narrator, $this>
     */
    public function primaryNarrator(): HasOne
    {
        return $this->hasOne(Narrator::class)->where('is_primary', true);
    }

    /**
     * Ce que la famille a dit de la narratrice après l'achat. Absent tant que
     * personne n'a ouvert le tunnel de personnalisation.
     *
     * @return HasOne<ProjectProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(ProjectProfile::class);
    }

    /**
     * Les questions sont-elles en pause ?
     *
     * Une pause a toujours une fin : un arrêt sans terme ferait disparaître
     * le projet en silence, et personne ne saurait s'il faut relancer.
     */
    public function isPaused(): bool
    {
        return $this->paused_until !== null && $this->paused_until->isFuture();
    }

    /**
     * Le message audio du cadeau, quand l'acheteur en a enregistré un.
     *
     * @return BelongsTo<Recording, $this>
     */
    public function giftAudio(): BelongsTo
    {
        return $this->belongsTo(Recording::class, 'gift_audio_recording_id');
    }

    /** @return HasMany<FamilyMember, $this> */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    /**
     * Le livre du projet.
     *
     * `hasMany` bien qu'un index unique n'en permette qu'un : une réimpression
     * est un état du même livre, pas un second livre, mais la relation reste
     * plurielle pour que `whereIn` et `exists()` s'écrivent naturellement.
     *
     * @return HasMany<Book, $this>
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /** @return HasMany<Story, $this> */
    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    /** @return HasMany<Consent, $this> */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    /**
     * Ce que l'Initiateur·rice a changé au corpus pour ce projet.
     *
     * @return HasMany<ProjectQuestionSetting, $this>
     */
    public function questionSettings(): HasMany
    {
        return $this->hasMany(ProjectQuestionSetting::class);
    }

    /**
     * Les noms propres du projet et leur graphie.
     *
     * @return HasMany<LexiconEntry, $this>
     */
    public function lexiconEntries(): HasMany
    {
        return $this->hasMany(LexiconEntry::class);
    }

    /**
     * Durées de collecte et de finalisation de l'offre souscrite (R-2).
     *
     * Le pilote dure douze semaines, finalisation comprise. L'offre cœur, elle,
     * ne se vend plus en mois depuis la v3.0 du dossier : elle vend **52
     * questions**, et c'est le rythme du narrateur qui décide du temps
     * qu'elles prennent — un an à une par semaine, six mois à deux, quatre
     * mois à trois, deux ans tous les quinze jours.
     *
     * Une durée fixe donnait un contenu différent selon le rythme, au même
     * prix : vingt-six histoires pour qui reçoit une question tous les quinze
     * jours, cent quatre pour qui en reçoit deux par semaine. Le nombre est ce
     * que la famille achète ; la durée en découle.
     *
     * Puis trois mois pour boucler le livre, après la dernière question.
     */
    public function collectionWindow(?DateTimeInterface $from = null): ServiceWindow
    {
        $start = CarbonImmutable::instance($from ?? $this->collection_started_at ?? now());

        if ($this->offer === Offer::Pilot) {
            $end = $start->addWeeks((int) config('product.offer.pilot_weeks'));

            return new ServiceWindow($start, $end, $end);
        }

        $end = $start->addWeeks(self::collectionWeeks($this->cadence));

        return new ServiceWindow(
            $start,
            $end,
            $end->addMonths((int) config('product.offer.finalization_months')),
        );
    }

    /**
     * Le nombre de semaines qu'il faut pour poser les 52 questions, au rythme
     * donné.
     *
     * Deux grandeurs distinctes chez `Cadence`, et les confondre donnerait
     * cent quatre questions à un rythme quinzomadaire : `weeks()` est la
     * longueur d'un cycle — une semaine, ou deux — et `timesPerWeek()` le
     * nombre de questions posées dans ce cycle.
     *
     * Arrondi au-dessus : trois questions par semaine font 17,33 semaines
     * pour cinquante-deux, et fermer la fenêtre à 17 laisserait la dernière
     * dehors.
     */
    public static function collectionWeeks(Cadence $cadence): int
    {
        $questions = (int) config('product.offer.core_questions');

        return (int) ceil($questions * $cadence->weeks() / $cadence->timesPerWeek());
    }

    /**
     * Le rythme a changé : la fenêtre suit, pour les questions qui restent.
     *
     * Ce que l'offre vend est un **nombre de questions** (R-2, v3.0), pas une
     * durée. Un narrateur qui passe à une question tous les quinze jours au
     * sixième mois — ce que le moteur lui propose quand il peine (bloc 09) —
     * doit donc voir sa fenêtre s'allonger d'autant : sinon ralentir revient
     * à renoncer à la moitié de ce qu'on a payé, et la proposition du moteur
     * devient un piège.
     *
     * Elle se raccourcit aussi, et c'est voulu : accélérer, c'est recevoir
     * ses questions plus tôt, pas en recevoir davantage.
     *
     * Le décompte part des questions **déjà posées**, pas du temps écoulé :
     * c'est la grandeur que le contrat porte, et la seule qu'une pause ne
     * fausse pas.
     *
     * Sans effet sur le pilote, qui se vend en semaines, ni avant
     * l'acceptation, où la fenêtre n'est pas encore ouverte.
     */
    public function rescheduleWindowForCadence(): self
    {
        if ($this->offer === Offer::Pilot || $this->collection_started_at === null) {
            return $this;
        }

        $remaining = max(
            0,
            (int) config('product.offer.core_questions') - $this->stories()->count(),
        );

        $end = CarbonImmutable::instance(now())->addWeeks(
            (int) ceil($remaining * $this->cadence->weeks() / $this->cadence->timesPerWeek()),
        );

        $this->collection_ends_at = $end;
        $this->finalization_ends_at = $end->addMonths(
            (int) config('product.offer.finalization_months'),
        );
        $this->save();

        return $this;
    }

    /**
     * Ouvre la collecte et fige les trois échéances.
     */
    public function startCollection(?DateTimeInterface $at = null): self
    {
        $window = $this->collectionWindow($at ?? now());

        $this->collection_started_at = $window->collectionStartsAt;
        $this->collection_ends_at = $window->collectionEndsAt;
        $this->finalization_ends_at = $window->finalizationEndsAt;
        $this->save();

        return $this;
    }

    public function isMember(User $user): bool
    {
        return $this->owner_user_id === $user->id
            || $this->members()->where('user_id', $user->id)->exists();
    }

    public function hasRole(User $user, ProjectMemberRole $role): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->where('role', $role->value)
            ->exists();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'offer' => Offer::class,
            'address_form' => AddressForm::class,
            'book_cover' => BookCover::class,
            'book_title' => BookTitle::class,
            'locale' => Locale::class,
            'cadence' => Cadence::class,
            'prompt_slot' => PromptSlot::class,
            'validation_variant' => ValidationVariant::class,
            'prompt_day' => 'integer',
            'next_prompt_at' => 'immutable_datetime',
            'paused_until' => 'immutable_datetime',
            'collection_started_at' => 'immutable_datetime',
            'collection_ends_at' => 'immutable_datetime',
            'finalization_ends_at' => 'immutable_datetime',
            'hosting_ends_at' => 'immutable_datetime',
            'erased_at' => 'immutable_datetime',
            'gift_send_at' => 'immutable_datetime',
            'gift_sent_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'declared_sharing_at' => 'immutable_datetime',
            'refused_at' => 'immutable_datetime',
        ];
    }
}
