@include('errors.layout', [
    'titre' => __('errors.429.title'),
    'corps' => __('errors.429.body'),
    'lien' => __('errors.back'),
    'retour' => '/',
    'marque' => \App\Support\Brand::nameSafe(),
    'couleurs' => \App\Support\Brand::cssVariables(),
])
