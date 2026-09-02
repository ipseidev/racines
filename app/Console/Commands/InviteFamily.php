<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\InviteFamilyMember;
use App\Models\Project;
use Illuminate\Console\Command;

/**
 * Invite un proche depuis la ligne de commande.
 *
 * L'écran d'ajout arrive au bloc 10 ; cette commande existe pour que le
 * checkpoint du bloc 08 soit jouable sans elle, et pour que le support puisse
 * dépanner une invitation perdue.
 */
final class InviteFamily extends Command
{
    protected $signature = 'family:invite {project : Identifiant du projet} {name : Nom affiché du proche} {contact : Courriel ou numéro au format E.164}';

    protected $description = 'Invite un proche à écouter les histoires d’un projet';

    public function handle(InviteFamilyMember $invite): int
    {
        $project = Project::query()->find($this->argument('project'));

        if ($project === null) {
            $this->components->error('Projet introuvable.');

            return self::FAILURE;
        }

        $contact = (string) $this->argument('contact');
        $isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;

        $member = $invite->handle($project, $project->owner, [
            'display_name' => (string) $this->argument('name'),
            $isEmail ? 'email' : 'phone_e164' => $contact,
        ]);

        $this->components->info(sprintf(
            'Proche %s invité·e (%s). Le lien est parti sur %s.',
            $member->display_name,
            $member->id,
            $isEmail ? 'son courriel' : 'son téléphone',
        ));

        return self::SUCCESS;
    }
}
