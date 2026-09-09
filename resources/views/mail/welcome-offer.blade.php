{{--
    Le code de réduction de bienvenue (T-141). Il dit le code, sa valeur, sa
    date de fin et comment s'en servir, puis mène au tunnel. Rien d'autre :
    un courriel demandé pour un code donne le code — en grand — pas un
    argumentaire. La ligne sur les nouvelles n'apparaît que si la case a été
    cochée.
--}}
<x-mail::message>
# {{ __('notifications.welcome_offer.greeting') }}

{{ __('notifications.welcome_offer.code_intro') }}

<x-mail::code>
{{ $code }}
</x-mail::code>

{{ __('notifications.welcome_offer.value_line', ['amount' => $amount, 'date' => $date]) }}

{{ __('notifications.welcome_offer.how_line') }}

<x-mail::button :url="$url">
{{ __('notifications.welcome_offer.button') }}
</x-mail::button>

@if ($news)
{{ __('notifications.welcome_offer.news_line') }}

@endif
{{ __('notifications.prompt.signature', ['brand' => $brand]) }}

<x-slot:subcopy>
{{ __('notifications.layout.trouble', ['action' => __('notifications.welcome_offer.button')]) }} <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
