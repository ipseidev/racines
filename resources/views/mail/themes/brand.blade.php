@php
/*
 * Le thème des courriels (T-237) : une vue Blade et non un fichier CSS,
 * parce que la charte vit dans BrandSettings et que Laravel rend le thème à
 * chaque courriel avant de le poser en styles en ligne. Les huit valeurs de
 * la marque viennent des réglages ; les tons de dessin — lin, sable, or —
 * sont fixes, comme dans app.css : ce sont des choix de dessin, pas
 * d'identité. Si la marque change, le lin et l'or restent.
 *
 * Ce qu'on tient de la direction artistique (docs/design/README.md) :
 * la terracotta sur le bouton d'action et nulle part ailleurs ; Fraunces
 * pour les titres et la question, Inter à 19 px pour le texte, parce que
 * la seconde cible a quatre-vingts ans ; l'or en filet, jamais en petit
 * texte ; le crème de la page en fond, pas un gris.
 */
$brand = \App\Support\Brand::settings();

$fontName = fn (string $name): string => str_replace(["'", '"', ';', '{', '}'], '', $name);
$display = "'".$fontName($brand->font_display)."', Georgia, 'Iowan Old Style', 'Palatino Linotype', 'Times New Roman', serif";
$body = "'".$fontName($brand->font_body)."', -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif";

$primary = $brand->color_primary;
$accent = $brand->color_accent;
$accentForeground = $brand->color_accent_foreground;
$background = $brand->color_background;
$surface = $brand->color_surface;
$text = $brand->color_text;
$muted = $brand->color_muted;

$linen = '#F3EADB';
$sand = '#E0D6C7';
$gold = '#C9A24B';
@endphp
/* Base */

body {
    -webkit-text-size-adjust: none;
    background-color: {{ $background }};
    color: {{ $text }};
    font-family: {!! $body !!};
    height: 100%;
    line-height: 1.6;
    margin: 0;
    padding: 0;
    width: 100% !important;
}

td, p, li, a, span {
    font-family: {!! $body !!};
}

p,
ul,
ol,
blockquote {
    line-height: 1.6;
    text-align: left;
}

a {
    color: {{ $primary }};
    text-decoration: underline;
}

a img {
    border: none;
}

/* Typographie */

h1 {
    color: {{ $primary }};
    font-family: {!! $display !!};
    font-size: 32px;
    font-style: italic;
    font-weight: 500;
    font-variation-settings: 'SOFT' 50, 'WONK' 1;
    letter-spacing: -0.01em;
    line-height: 1.2;
    margin: 0 0 22px 0;
    text-align: left;
}

h2 {
    color: {{ $primary }};
    font-family: {!! $display !!};
    font-size: 24px;
    font-weight: 600;
    line-height: 1.25;
    margin: 28px 0 12px 0;
    text-align: left;
}

h3 {
    color: {{ $text }};
    font-size: 19px;
    font-weight: 600;
    line-height: 1.4;
    margin: 24px 0 8px 0;
    text-align: left;
}

p {
    color: {{ $text }};
    font-size: 19px;
    line-height: 1.6;
    margin: 0 0 20px 0;
    text-align: left;
}

ul,
ol {
    color: {{ $text }};
    font-size: 19px;
    margin: 0 0 20px 0;
    padding-left: 26px;
}

li {
    margin: 0 0 8px 0;
}

blockquote {
    border-left: 3px solid {{ $gold }};
    color: {{ $text }};
    font-family: {!! $display !!};
    font-size: 21px;
    font-style: italic;
    margin: 0 0 20px 0;
    padding: 4px 0 4px 20px;
}

blockquote p {
    font-family: {!! $display !!};
    font-size: 21px;
    margin: 0;
}

img {
    max-width: 100%;
}

.font-display {
    font-family: {!! $display !!};
}

/* Cadre */

.wrapper {
    background-color: {{ $background }};
    margin: 0;
    padding: 0;
    width: 100%;
}

.content {
    margin: 0;
    padding: 0;
    width: 100%;
}

/* En-tête */

.header {
    padding: 44px 40px 30px 40px;
    text-align: center;
}

.header a {
    color: {{ $primary }};
    text-decoration: none;
}

.mark {
    border: 0;
    display: inline-block;
    height: 27px;
    margin: 0 10px 0 0;
    vertical-align: middle;
    width: 48px;
}

.logo {
    border: 0;
    display: inline-block;
    height: 36px;
    max-height: 36px;
    width: auto;
}

.brand-name {
    color: {{ $primary }};
    display: inline-block;
    font-family: {!! $display !!};
    font-size: 28px;
    font-variation-settings: 'SOFT' 80, 'WONK' 1;
    font-weight: 600;
    letter-spacing: -0.01em;
    line-height: 1;
    vertical-align: middle;
}

.header-rule-table {
    margin: 20px auto 0 auto;
}

.header-rule {
    background-color: {{ $gold }};
    font-size: 2px;
    height: 2px;
    line-height: 2px;
    width: 40px;
}

/* Corps */

.body {
    background-color: {{ $background }};
    border: 0;
    margin: 0;
    padding: 0;
    width: 100%;
}

.inner-body {
    background-color: transparent;
    margin: 0 auto;
    padding: 0;
    width: 600px;
}

.content-cell {
    max-width: 100vw;
    padding: 30px 40px 16px 40px;
}

/* La question, en carte blanche sous un filet d'or */

.question {
    margin: 4px 0 28px 0;
}

.question-cell {
    background-color: {{ $surface }};
    border: 1px solid {{ $sand }};
    border-radius: 12px;
    padding: 24px 28px 26px 28px;
}

.question-rule-table {
    margin: 0 0 14px 0;
}

.question-rule {
    background-color: {{ $gold }};
    font-size: 2px;
    height: 2px;
    line-height: 2px;
    width: 40px;
}

.question-text {
    color: {{ $primary }};
    font-family: {!! $display !!};
    font-size: 26px;
    font-variation-settings: 'SOFT' 50, 'WONK' 1, 'opsz' 144;
    font-weight: 500;
    line-height: 1.35;
    margin: 0;
    text-align: left;
}

/* Un code à recopier, seul et en grand, sur lin */

.code {
    margin: 4px 0 28px 0;
}

.code-cell {
    background-color: {{ $linen }};
    border-radius: 8px;
    padding: 22px 24px;
    text-align: center;
}

.code-text {
    color: {{ $primary }};
    display: inline-block;
    font-size: 34px;
    font-weight: 600;
    letter-spacing: 0.12em;
    line-height: 1.2;
}

/* Panneau de mise en avant, sur lin */

.panel {
    border-collapse: separate;
    margin: 4px 0 24px 0;
}

.panel-content {
    background-color: {{ $linen }};
    border-radius: 8px;
    color: {{ $text }};
    padding: 18px 22px;
}

.panel-content p {
    color: {{ $text }};
    margin: 0 0 10px 0;
}

.panel-item p:last-of-type {
    margin-bottom: 0;
}

/* Le bouton d'action : terracotta, et rien d'autre en terracotta */

.action {
    margin: 30px auto 30px auto;
    padding: 0;
    text-align: center;
    width: 100%;
}

.button-cell {
    background-color: {{ $accent }};
    border-radius: 8px;
}

.button {
    -webkit-text-size-adjust: none;
    background-color: {{ $accent }};
    border-radius: 8px;
    color: {{ $accentForeground }} !important;
    display: inline-block;
    font-size: 18px;
    font-weight: 600;
    line-height: 22px;
    min-width: 220px;
    overflow: hidden;
    padding: 16px 32px;
    text-align: center;
    text-decoration: none;
}

/* Le lien en clair, pour qui ne peut pas presser le bouton */

.subcopy {
    border-top: 1px solid {{ $sand }};
    margin-top: 28px;
    padding-top: 22px;
}

.subcopy p {
    color: {{ $muted }};
    font-size: 15px;
    line-height: 1.55;
    margin: 0;
}

.subcopy a {
    color: {{ $primary }};
    word-break: break-all;
}

/* Pied */

.footer {
    margin: 0 auto;
    padding: 0;
    text-align: center;
    width: 600px;
}

.footer-cell {
    padding: 26px 40px 52px 40px;
}

.footer-rule-table {
    margin: 0 0 24px 0;
}

.footer-rule {
    background-color: {{ $sand }};
    font-size: 1px;
    height: 1px;
    line-height: 1px;
}

.footer-text {
    color: {{ $muted }};
    font-size: 14px;
    line-height: 1.5;
    margin: 0 0 8px 0;
    text-align: center;
}

.footer-text a,
.footer-link {
    color: {{ $primary }};
    text-decoration: underline;
}

.footer-legal {
    font-size: 13px;
}

/* Tableaux (x-mail::table) */

.table table {
    border-collapse: collapse;
    margin: 24px auto;
    width: 100%;
}

.table th {
    border-bottom: 1px solid {{ $sand }};
    color: {{ $muted }};
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.08em;
    padding: 0 0 10px 0;
    text-align: left;
    text-transform: uppercase;
}

.table td {
    border-bottom: 1px solid {{ $sand }};
    color: {{ $text }};
    font-size: 17px;
    line-height: 1.5;
    padding: 12px 0;
    text-align: left;
}

/* Utilitaires */

.break-all {
    word-break: break-all;
}
