{{--
    Le cadre de tous les courriels (T-237).

    Une lettre sur papier crème plutôt qu'une carte blanche sur fond gris :
    c'est le dessin des pages de la narratrice (docs/design/README.md), et un
    courriel de la marque doit ressembler à sa page. Les styles sont posés en
    ligne au rendu par le thème (`mail/themes/brand.blade.php`) ; ne restent
    ici que ce qu'un attribut `style` ne sait pas porter — les polices, les
    règles d'écran étroit — et ce qu'Outlook exige.
--}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ \App\Support\Brand::name() }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
{{-- Thème clair seul, comme les pages : un livre se lit sur du papier. --}}
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<!--[if mso]>
<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
<![endif]-->
<style>
/*
 * Les deux familles de la charte, auto-hébergées (T-40). Les clients qui
 * savent charger une police les prennent — Apple Mail, Mail d'iOS, une
 * partie d'Android — ; les autres lisent Georgia et Arial, choisis pour
 * leurs métriques proches.
 */
@font-face {
font-family: 'Fraunces';
font-style: normal;
font-weight: 300 700;
src: url('{{ asset('/fonts/fraunces-var-roman.woff2') }}') format('woff2');
}
@font-face {
font-family: 'Fraunces';
font-style: italic;
font-weight: 300 700;
src: url('{{ asset('/fonts/fraunces-var-italic.woff2') }}') format('woff2');
}
@font-face {
font-family: 'Inter';
font-style: normal;
font-weight: 400;
src: url('{{ asset('/fonts/inter-400.woff2') }}') format('woff2');
}
@font-face {
font-family: 'Inter';
font-style: normal;
font-weight: 500;
src: url('{{ asset('/fonts/inter-500.woff2') }}') format('woff2');
}
@font-face {
font-family: 'Inter';
font-style: normal;
font-weight: 600;
src: url('{{ asset('/fonts/inter-600.woff2') }}') format('woff2');
}
/* Sur un téléphone : la colonne prend toute la largeur, les marges se resserrent, le bouton s'étire. */
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}
.footer {
width: 100% !important;
}
.content-cell {
padding: 28px 22px 12px 22px !important;
}
.header-cell {
padding: 32px 22px 24px 22px !important;
}
.footer-cell {
padding: 24px 22px 40px 22px !important;
}
h1 {
font-size: 28px !important;
}
.question-text {
font-size: 23px !important;
}
.code-text {
font-size: 30px !important;
}
}
@media only screen and (max-width: 500px) {
.button {
display: block !important;
width: 100% !important;
box-sizing: border-box !important;
text-align: center !important;
}
}
</style>
<!--[if mso]>
<style>
/* Word ne charge aucune police et tomberait sur Times New Roman : on lui nomme les replis. */
h1, h2, .font-display, .question-text, .brand-name { font-family: Georgia, 'Times New Roman', serif !important; }
body, td, p, a, li, .font-body { font-family: Arial, Helvetica, sans-serif !important; }
</style>
<![endif]-->
{!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
