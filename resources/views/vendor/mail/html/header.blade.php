{{--
    La signature de la marque, centrée comme une page de titre : le
    pictogramme puis le nom, et dessous le filet d'or de l'ouverture (T-232).

    Même règle que `BrandLogo` sur le web : un logo complet téléversé remplace
    le nom ; le pictogramme, lui, l'accompagne, et il est décoratif — le nom
    est là, en texte, et il reste lisible quand la messagerie bloque les
    images, ce qui arrive. Le PNG plutôt que le SVG : voir `Brand::markEmailUrl`.
--}}
@props(['url'])
@php
$brandName = \App\Support\Brand::name();
$logoUrl = \App\Support\Brand::logoUrl();
$markUrl = \App\Support\Brand::markEmailUrl();
@endphp
<tr>
<td class="header header-cell" align="center">
<a href="{{ $url }}" class="header-link" style="display: inline-block;">
@if ($logoUrl !== null)
<img src="{{ $logoUrl }}" class="logo" alt="{{ $brandName }}" height="36" style="height: 36px; width: auto;">
@else
@if ($markUrl !== null)
<img src="{{ $markUrl }}" class="mark" alt="" width="48" height="27" aria-hidden="true">
@endif
<span class="brand-name font-display">{{ $brandName }}</span>
@endif
</a>
<table class="header-rule-table" align="center" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="header-rule" width="40" height="2">&nbsp;</td>
</tr>
</table>
</td>
</tr>
