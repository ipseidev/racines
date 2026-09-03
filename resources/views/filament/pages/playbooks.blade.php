{{--
    Les playbooks, dépliables un par un.

    Rendus côté serveur depuis le markdown du dépôt : le support les lit dans
    son outil de travail, pas dans un second onglet — et un support au
    téléphone avec une famille en deuil n'ouvrira pas un second onglet.
--}}
<x-filament-panels::page>
    <div class="fi-section-content-ctn">
        @forelse ($this->playbooks() as $playbook)
            <details class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <summary class="cursor-pointer px-6 py-4 text-base font-semibold text-gray-950 dark:text-white">
                    {{ $playbook['title'] }}
                </summary>

                <div class="prose prose-sm max-w-none px-6 pb-6 dark:prose-invert">
                    {!! $playbook['html'] !!}
                </div>
            </details>
        @empty
            <p>{{ __('admin.playbooks.empty') }}</p>
        @endforelse
    </div>
</x-filament-panels::page>
