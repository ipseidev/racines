{{--
    Courriel de la question de la semaine.

    Un seul bouton, une seule action. La question est dans la carte de la
    page d'enregistrement — blanche, sous un filet d'or, en Fraunces — parce
    qu'elle est lue sur un téléphone, souvent par une personne de plus de
    75 ans, et parce que le courriel doit ressembler à la page qu'il ouvre.
    Le rappel anti-hameçonnage est en clair, comme l'exige le doc 04 §9 :
    aucune de nos pages ne demande de mot de passe ni de paiement. L'adresse
    du support est dans le pied, commun à tous les courriels (T-237).
--}}
<x-mail::message>
# {{ __('notifications.prompt.greeting', ['name' => $firstName]) }}

<x-mail::question>
{{ $question }}
</x-mail::question>

<x-mail::button :url="$link">
{{ __('notifications.prompt.button') }}
</x-mail::button>

{{ __('notifications.prompt.no_password') }}

{{ __('notifications.prompt.signature', ['brand' => $brand]) }}
</x-mail::message>
