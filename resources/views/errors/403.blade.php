@include('errors.layout', [
    'titre' => __('errors.403.title'),
    'corps' => __('errors.403.body'),
    'lien' => __('errors.back'),
    'retour' => '/',
    'marque' => \App\Support\Brand::nameSafe(),
    'couleurs' => \App\Support\Brand::cssVariables(),
])
