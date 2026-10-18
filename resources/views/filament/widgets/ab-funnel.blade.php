<x-filament-widgets::widget>
    <x-filament::section>
        <div wire:poll.30s class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <div class="text-base font-semibold">Воронка по версиям</div>
                    <div class="text-sm text-gray-500">Уникальные посетители на каждом шаге и доля от открывших главную · обновлено {{ $updatedAt }}</div>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <label for="ab-period" class="text-gray-600">Период</label>
                    <select id="ab-period" wire:model.live="period" class="rounded-lg border-gray-300 text-sm">
                        @foreach ($periods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Главная метрика · до шага 3</div>
                    <div class="mt-1 flex items-baseline gap-4">
                        <div><span class="text-2xl font-semibold" style="color:#CA4592">{{ $visitsA ? number_format($primaryA / $visitsA * 100, 1, ',', ' ') : '—' }}%</span> <span class="text-sm text-gray-500">A</span></div>
                        <div><span class="text-2xl font-semibold" style="color:#3F7D2A">{{ $visitsB ? number_format($primaryB / $visitsB * 100, 1, ',', ' ') : '—' }}%</span> <span class="text-sm text-gray-500">B</span></div>
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Открыли главную</div>
                    <div class="mt-1 text-2xl font-semibold">{{ number_format($visitsA, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">A</span> · {{ number_format($visitsB, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">B</span></div>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Достаточно ли данных</div>
                    <div class="mt-1 text-sm">
                        @if ($enough)
                            <span class="font-semibold text-green-700">Да: больше 300 стартов мастера на версию.</span>
                        @else
                            <span class="text-gray-700">Пока нет. Нужно от 300 стартов мастера на каждую версию и не меньше 14 дней.</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="py-2 pr-4 font-medium">Шаг</th>
                            <th class="py-2 pr-4 text-right font-medium" style="color:#CA4592">A · сегмент</th>
                            <th class="py-2 pr-4 text-right font-medium">% A</th>
                            <th class="py-2 pr-4 text-right font-medium" style="color:#3F7D2A">B · карточки</th>
                            <th class="py-2 pr-4 text-right font-medium">% B</th>
                            <th class="py-2 font-medium">Лидер</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-b border-gray-100 {{ $row['key'] === 'wizard_step_3' ? 'bg-gray-50 font-semibold' : '' }}">
                                <td class="py-2 pr-4">{{ $row['label'] }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format($row['a'], 0, ',', ' ') }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums text-gray-600">{{ $row['pa'] === null ? '—' : number_format($row['pa'], 1, ',', ' ') . '%' }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums">{{ number_format($row['b'], 0, ',', ' ') }}</td>
                                <td class="py-2 pr-4 text-right tabular-nums text-gray-600">{{ $row['pb'] === null ? '—' : number_format($row['pb'], 1, ',', ' ') . '%' }}</td>
                                <td class="py-2">
                                    @if ($row['leader'] === 'a')
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" style="background:#FDECF3;color:#9A2A6B">A</span>
                                    @elseif ($row['leader'] === 'b')
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" style="background:#E6F0DD;color:#2F6420">B</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-xs text-gray-500">Разница меньше половины процента лидером не считается. Решение принимаем по главной метрике при достаточном объёме данных.</div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
