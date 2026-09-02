<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasTranslatedLabel;

/**
 * D'où vient l'audio.
 *
 * `phone_operator` est l'option payante D-9, opérée par un humain ;
 * `upload_admin` couvre le rattrapage par le support, sur demande écrite.
 */
enum RecordingSource: string
{
    use HasTranslatedLabel;

    case Browser = 'browser';
    case PhoneOperator = 'phone_operator';
    case UploadAdmin = 'upload_admin';
}
