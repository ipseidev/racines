<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\E2ELinksSeeder;
use Illuminate\Console\Command;
use PragmaRX\Google2FA\Google2FA;

/**
 * Le code à six chiffres du compte d'administration du décor.
 *
 * Le second facteur du compte principal est configuré d'avance par
 * `E2ELinksSeeder`, sur un secret constant, pour que la suite bout en bout
 * puisse se connecter (T-180). L'effet de bord se paie à chaque vérification
 * humaine : l'écran demande un code que personne n'a dans son téléphone, et
 * la session s'arrête là — c'est arrivé deux fois.
 *
 * La commande imprime le code courant et l'adresse `otpauth://` à scanner une
 * bonne fois. Elle ne révèle rien : le secret est en clair dans le dépôt, et
 * n'ouvre qu'un compte de démonstration sur une base locale.
 */
final class DemoTotp extends Command
{
    protected $signature = 'demo:totp';

    protected $description = 'Imprime le code du second facteur du compte d’administration du décor';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('Cette commande ne concerne qu’un décor de démonstration. Jamais en production.');

            return self::FAILURE;
        }

        $secret = E2ELinksSeeder::E2E_TOTP_SECRET;
        $google = new Google2FA;
        $email = (string) config('product.seeding.admin_email');

        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Code</>', '<options=bold>'.$google->getCurrentOtp($secret).'</>');
        $this->components->twoColumnDetail('Valable encore', (30 - time() % 30).' s');
        $this->components->twoColumnDetail('Compte', $email);
        $this->newLine();
        $this->line('  À scanner une fois dans votre application d’authentification :');
        $this->line('  <fg=cyan>'.$google->getQRCodeUrl((string) config('app.name'), $email, $secret).'</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
