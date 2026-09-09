{{--
    Le gabarit des notifications construites par `MailMessage` (T-237) :
    celui du framework, en français, sans `config('app.name')`, et avec un
    seul bouton possible — terracotta, quel que soit le « niveau » que la
    notification déclare. Le salut et la signature de repli viennent des
    traductions ; les notifications du produit fournissent les leurs.
--}}
<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# {{ __('notifications.layout.error_greeting') }}
@else
# {{ __('notifications.layout.greeting') }}
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
{{ __('notifications.layout.salutation', ['brand' => \App\Support\Brand::nameSafe()]) }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
{{ __('notifications.layout.trouble', ['action' => $actionText]) }} <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
