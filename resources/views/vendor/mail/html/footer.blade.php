{{--
    Le pied, le même sous chaque courriel : de quoi nous joindre, de quoi
    nous reconnaître. Trois repères d'anti-hameçonnage du doc 04 §9 — la
    marque nommée, l'adresse du support, le domaine dont partent nos liens —
    et la mention légale quand l'administration l'a renseignée.

    Rien en or ici : l'or est un filet, jamais un petit texte (charte).
--}}
@php
$brand = \App\Support\Brand::settings();
$supportEmail = \App\Support\Brand::supportEmail();
$legal = trim(implode(' · ', array_filter([$brand->legal_entity, $brand->legal_address], fn (string $value): bool => trim($value) !== '')));
@endphp
<tr>
<td>
<table class="footer" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="footer-cell" align="center">
<table class="footer-rule-table" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="footer-rule" height="1">&nbsp;</td>
</tr>
</table>
<p class="footer-text">{!! __('notifications.layout.help', ['email' => '<a href="mailto:'.e($supportEmail).'" class="footer-link">'.e($supportEmail).'</a>']) !!}</p>
<p class="footer-text">{{ __('notifications.layout.domain', ['domain' => 'https://'.\App\Support\Brand::linksDomain()]) }}</p>
<p class="footer-text footer-legal">{{ __('notifications.layout.copyright', ['year' => date('Y'), 'brand' => $brand->product_name]) }}</p>
@if ($legal !== '')
<p class="footer-text footer-legal">{{ $legal }}</p>
@endif
@if (trim($slot) !== '')
<p class="footer-text">{!! Illuminate\Mail\Markdown::parse($slot) !!}</p>
@endif
</td>
</tr>
</table>
</td>
</tr>
