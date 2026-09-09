{{--
    L'action du courriel, et elle seule, en terracotta (charte, règle 1).

    `color` est accepté pour rester compatible avec les notifications du
    framework, qui passent `success` ou `error` selon le niveau, et ignoré :
    nous n'avons qu'une couleur d'action, et un bouton se voit par son
    isolement, pas par sa teinte. Le tableau imbriqué est ce qui permet à
    Outlook de dessiner un bouton ; la cellule extérieure le centre.
--}}
@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="button-cell">
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
