<?php

declare(strict_types=1);

namespace App\Books;

use App\Enums\TokenType;
use App\Models\AccessToken;
use App\Models\BookChapter;
use App\Services\Tokens\TokenService;

/**
 * Le jeton QR d'un chapitre, **dérivé** et non tiré au sort.
 *
 * Le problème que cela résout : un QR est imprimé, donc définitif, et il doit
 * survivre à trois choses — une regénération du BAT, une réimpression, et le
 * fait qu'un jeton porteur **n'est jamais stocké en clair** (bloc 03, deux
 * tests le gardent). Un tirage aléatoire à chaque rendu produirait un code
 * différent dans la deuxième édition, et la révocation d'un récit devrait
 * alors retrouver tous les jetons jamais imprimés pour le tuer partout.
 *
 * D'où une dérivation : `HMAC-SHA256(clé de l'application, "qr:" + chapitre)`,
 * ramenée aux quarante-trois caractères que la route attend. La base ne
 * contient toujours que l'empreinte, un vidage de base ne rend donc rien
 * d'utilisable, et le même chapitre redonne le même code aussi longtemps que
 * la clé de l'application ne change pas — ce qui est déjà la condition de
 * lisibilité de toutes les données chiffrées du projet.
 *
 * Le sujet du jeton est **l'histoire**, pas le chapitre : la page `/q/{token}`
 * sert un récit, et le livre n'est qu'un chemin parmi d'autres jusqu'à lui.
 */
final readonly class IssueQrToken
{
    public function __construct(private TokenService $tokens) {}

    public function handle(BookChapter $chapter): AccessToken
    {
        $existing = $chapter->qrToken;

        if ($existing instanceof AccessToken && $existing->revoked_at === null) {
            return $existing;
        }

        // L'empreinte ne se manipule que dans le service de jetons : c'est un
        // invariant du bloc 03, et un test le vérifie sur tout `app/`. Ici on
        // ne fournit que le **code dérivé**.
        $token = $this->tokens->issueDerived(
            TokenType::Qr,
            $chapter->story,
            self::plainFor($chapter),
            ['listen'],
        );

        $chapter->qrToken()->associate($token);
        $chapter->save();

        return $token;
    }

    /**
     * Le code imprimé de ce chapitre.
     *
     * Public parce que le rendu en a besoin à chaque regénération, et qu'il ne
     * peut pas le relire ailleurs : la base n'en garde que l'empreinte.
     */
    public static function plainFor(BookChapter $chapter): string
    {
        $raw = hash_hmac('sha256', 'qr:'.$chapter->getKey(), (string) config('app.key'), true);

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
