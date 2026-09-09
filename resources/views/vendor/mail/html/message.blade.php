{{--
    Le message : l'en-tête et le pied sont ceux de la marque, lus dans les
    réglages au moment du rendu — jamais `config('app.name')`.
--}}
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="url('/')" />
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer />
</x-slot:footer>
</x-mail::layout>
