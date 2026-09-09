{{--
    Le code à usage unique. Il est posé seul, en grand : c'est ce que la
    personne vient chercher, et elle le recopie souvent d'un écran à l'autre.
    Aucun lien dans ce courriel, c'est voulu (doc 04 §9) : un code qui arrive
    avec un lien à cliquer entraîne exactement le réflexe qu'on veut
    désapprendre. La marque et la durée sont dites ; le pied redit qui écrit.
--}}
<x-mail::message>
# {{ __('notifications.otp.greeting') }}

{{ __('notifications.otp.code_intro') }}

<x-mail::code>
{{ $code }}
</x-mail::code>

{{ __('notifications.otp.expiry_line', ['minutes' => $minutes]) }}

{{ __('notifications.otp.warning_line') }}
</x-mail::message>
