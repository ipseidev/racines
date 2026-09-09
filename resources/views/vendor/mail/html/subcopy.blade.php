{{-- Le lien en clair sous le message, pour qui ne peut pas presser le bouton. --}}
<table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="subcopy-cell">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
