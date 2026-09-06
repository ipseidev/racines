@include('errors.layout', [
    'titre' => __('errors.500.title'),
    'corps' => __('errors.500.body'),
    'lien' => __('errors.back'),
    'retour' => '/',
    'marque' => \App\Support\Brand::nameSafe(),
    'couleurs' => \App\Support\Brand::cssVariables(),
])
