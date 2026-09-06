@include('errors.layout', [
    'titre' => __('errors.419.title'),
    'corps' => __('errors.419.body'),
    'lien' => __('errors.back'),
    'retour' => '/',
    'marque' => \App\Support\Brand::nameSafe(),
    'couleurs' => \App\Support\Brand::cssVariables(),
])
