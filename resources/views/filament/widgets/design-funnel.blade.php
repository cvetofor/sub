<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            .ds-head{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:16px}
            .ds-title{font-size:16px;font-weight:600}
            .ds-sub{font-size:13px;color:#6b7280;margin-top:2px}
            .ds-period{display:flex;align-items:center;gap:8px;font-size:14px;color:#4b5563}
            .ds-period select{border:1px solid #d1d5db;border-radius:8px;padding:6px 32px 6px 10px;font-size:14px;background:#fff}
            .ds-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:16px}
            .ds-tile{border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px}
            .ds-tile .k{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#6b7280}
            .ds-tile .v{margin-top:4px;font-size:24px;font-weight:600;font-variant-numeric:tabular-nums;display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 16px}
            .ds-tile .v small{font-size:13px;font-weight:400;color:#6b7280}
            .ds-tile .t{margin-top:4px;font-size:14px;line-height:1.45}
            .ds-c{color:#3F7D2A}.ds-q{color:#CA4592}
            .ds-wrap{overflow-x:auto}
            .ds-table{width:100%;border-collapse:collapse;font-size:14px;min-width:640px}
            .ds-table th{text-align:left;padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;font-weight:600;white-space:nowrap}
            .ds-table td{padding:10px 12px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
            .ds-table th.num,.ds-table td.num{text-align:right;font-variant-numeric:tabular-nums}
            .ds-table td.pct{color:#6b7280}
            .ds-table tr.primary td{background:#f9fafb;font-weight:600}
            .ds-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600}
            .ds-pill.classic{background:#E6F0DD;color:#2F6420}.ds-pill.quiz{background:#FDECF3;color:#9A2A6B}
            .ds-note{margin-top:12px;font-size:12px;color:#6b7280}
            .ds-dash{color:#9ca3af}
        </style>
        <div wire:poll.30s>
            <div class="ds-head">
                <div>
                    <div class="ds-title">Воронка по дизайнам</div>
                    <div class="ds-sub">Уникальные посетители, заявки и оплаты · обновлено {{ $updatedAt }}</div>
                </div>
                <div class="ds-period">
                    <label for="ds-period">Период</label>
                    <select id="ds-period" wire:model.live="period">
                        @foreach ($periods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="ds-tiles">
                <div class="ds-tile">
                    <div class="k">Главная метрика · оплатили из зашедших</div>
                    <div class="v">
                        <span><span class="ds-c">{{ $convC === null ? '—' : number_format($convC, 2, ',', ' ') . '%' }}</span> <small>дизайн 1</small></span>
                        <span><span class="ds-q">{{ $convQ === null ? '—' : number_format($convQ, 2, ',', ' ') . '%' }}</span> <small>квиз</small></span>
                    </div>
                </div>
                <div class="ds-tile">
                    <div class="k">Оплачено, ₽</div>
                    <div class="v"><span>{{ number_format($n['classic']['revenue'], 0, ',', ' ') }} <small>дизайн 1</small></span><span>{{ number_format($n['quiz']['revenue'], 0, ',', ' ') }} <small>квиз</small></span></div>
                </div>
                <div class="ds-tile">
                    <div class="k">Достаточно ли данных</div>
                    <div class="t">
                        @if ($enough)
                            <b class="ds-c">Да: больше 500 визитов на дизайн и от 20 оплат суммарно.</b>
                        @else
                            Пока нет. Нужно от 500 визитов на каждый дизайн, от 20 оплат суммарно и не меньше 14 дней.
                        @endif
                    </div>
                </div>
            </div>

            <div class="ds-wrap">
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th>Шаг</th>
                            <th class="num ds-c">Дизайн 1 · классика</th>
                            <th class="num">%</th>
                            <th class="num ds-q">Дизайн 2 · квиз</th>
                            <th class="num">%</th>
                            <th>Лидер</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="{{ $row['label'] === 'Оплатили' ? 'primary' : '' }}">
                                <td>{{ $row['label'] }}</td>
                                <td class="num">{{ number_format($row['classic'], 0, ',', ' ') }}</td>
                                <td class="num pct">{{ $row['pc'] === null ? '—' : number_format($row['pc'], 1, ',', ' ') . '%' }}</td>
                                <td class="num">{{ number_format($row['quiz'], 0, ',', ' ') }}</td>
                                <td class="num pct">{{ $row['pq'] === null ? '—' : number_format($row['pq'], 1, ',', ' ') . '%' }}</td>
                                <td>
                                    @if ($row['leader'] === 'classic')
                                        <span class="ds-pill classic">Дизайн 1</span>
                                    @elseif ($row['leader'] === 'quiz')
                                        <span class="ds-pill quiz">Квиз</span>
                                    @else
                                        <span class="ds-dash">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="ds-note">Доли считаются от визитов того же дизайна, последняя строка от оформивших заявку. Разница меньше половины процента лидером не считается. Решение принимаем по главной метрике при достаточном объёме данных.</div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
