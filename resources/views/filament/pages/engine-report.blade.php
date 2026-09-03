{{--
    Le rapport du moteur : déclenchements, reprises, délai médian.

    La médiane et non la moyenne : une reprise à trente jours tire une moyenne
    et fait croire que le moteur est lent alors qu'il est le plus souvent
    rapide.
--}}
<x-filament-panels::page>
    {{ $this->filtersForm }}

    @php($rows = $this->rows())

    @if (count($rows) === 0)
        <p>{{ __('admin.engine_report.empty') }}</p>
    @else
        <div class="fi-ta-ctn overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <table class="w-full text-start text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.engine_report.rule') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('admin.engine_report.fired') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('admin.engine_report.resumed') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('admin.engine_report.rate') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('admin.engine_report.median') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['label'] }}</td>
                            <td class="px-4 py-3 text-end">{{ $row['fired'] }}</td>
                            <td class="px-4 py-3 text-end">{{ $row['resumed'] }}</td>
                            <td class="px-4 py-3 text-end">
                                {{ $row['rate'] === null ? '—' : number_format($row['rate'], 1) . ' %' }}
                            </td>
                            <td class="px-4 py-3 text-end">
                                {{ $row['median_hours'] === null ? '—' : number_format($row['median_hours'], 1) . ' h' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
