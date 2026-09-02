<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\StoresDatesWithOffset;
use App\Enums\ProjectMemberRole;
use Database\Factories\ProjectMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Rattachement d'un utilisateur à un projet : Initiateur·rice ou éditeur
 * désigné (glossaire §1). Table interne, identifiant séquentiel.
 *
 * @property int $id
 * @property string $project_id
 * @property int $user_id
 * @property ProjectMemberRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProjectMember extends Model
{
    /** @use HasFactory<ProjectMemberFactory> */
    use HasFactory, StoresDatesWithOffset;

    /** @var list<string> */
    protected $fillable = ['project_id', 'user_id', 'role'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['role' => ProjectMemberRole::class];
    }
}
