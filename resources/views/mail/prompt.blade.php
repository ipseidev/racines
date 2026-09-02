{{-- 
    Courriel de la question de la semaine.
    
    Un seul bouton, une seule action. La question est en gros caractères
    parce qu'elle est lue sur un téléphone, souvent par une personne de plus
    de 75 ans. Le rappel anti-hameçonnage est en clair, comme l'exige le
    doc 04 §9 : aucune de nos pages ne demande de mot de passe ni de paiement.
--}}
<x-mail::message>
# {{ __('notifications.prompt.greeting', ['name' => $firstName]) }}

<div style="font-size: 22px; line-height: 1.4; margin: 24px 0;">
{{ $question }}
</div>

<x-mail::button :url="$link">
{{ __('notifications.prompt.button') }}
</x-mail::button>

{{ __('notifications.prompt.no_password') }}

{{ __('notifications.prompt.help', ['email' => $supportEmail]) }}

{{ __('notifications.prompt.signature', ['brand' => $brand]) }}
</x-mail::message>
