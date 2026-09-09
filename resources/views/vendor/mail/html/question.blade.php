{{--
    La question posée, telle que la narratrice la voit sur sa page : une
    carte blanche à filet sable, un filet d'or, puis la question en Fraunces
    (`Record.tsx`, `.record-card`). Grande, parce qu'elle est lue sur un
    téléphone, souvent par une personne de plus de soixante-quinze ans.

    Pas de ligne vide à l'intérieur : le bloc HTML se ferme à la première
    ligne vide pour l'analyseur Markdown, et le reste serait pris pour du
    texte.
--}}
<table class="question" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="question-cell">
<table class="question-rule-table" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="question-rule" width="40" height="2">&nbsp;</td>
</tr>
</table>
<p class="question-text font-display">{!! preg_replace('/\s*\n\s*/', ' ', trim((string) $slot)) !!}</p>
</td>
</tr>
</table>
