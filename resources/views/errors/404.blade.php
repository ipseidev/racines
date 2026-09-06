@include('errors.layout', [
    'titre' => __('errors.404.title'),
    'corps' => __('errors.404.body'),
    'lien' => __('errors.back'),
    'retour' => '/',
    'marque' => \App\Support\Brand::nameSafe(),
    'couleurs' => \App\Support\Brand::cssVariables(),
])
