<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            .ab-head{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:16px}
            .ab-title{font-size:16px;font-weight:600}
            .ab-sub{font-size:13px;color:#6b7280;margin-top:2px}
            .ab-period{display:flex;align-items:center;gap:8px;font-size:14px;color:#4b5563}
            .ab-period select{border:1px solid #d1d5db;border-radius:8px;padding:6px 32px 6px 10px;font-size:14px;background:#fff}
            .ab-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:16px}
            .ab-tile{border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px}
            .ab-tile .k{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#6b7280}
            .ab-tile .v{margin-top:4px;font-size:24px;font-weight:600;font-variant-numeric:tabular-nums;display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 16px}
            .ab-tile .v small{font-size:13px;font-weight:400;color:#6b7280}
            .ab-tile .t{margin-top:4px;font-size:14px;line-height:1.45}
            .ab-a{color:#CA4592}.ab-b{color:#3F7D2A}
            .ab-wrap{overflow-x:auto}
            .ab-table{width:100%;border-collapse:collapse;font-size:14px;min-width:640px}
            .ab-table th{text-align:left;padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;font-weight:600;white-space:nowrap}
            .ab-table td{padding:10px 12px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
            .ab-table th.num,.ab-table td.num{text-align:right;font-variant-numeric:tabular-nums}
            .ab-table td.pct{color:#6b7280}
            .ab-table tr.primary td{background:#f9fafb;font-weight:600}
            .ab-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600}
            .ab-pill.a{background:#FDECF3;color:#9A2A6B}.ab-pill.b{background:#E6F0DD;color:#2F6420}
            .ab-note{margin-top:12px;font-size:12px;color:#6b7280}
            .ab-dash{color:#9ca3af}
        </style>
        <div wire:poll.30s>
            <div class="ab-head">
                <div>
                    <div class="ab-title">Воронка по версиям</div>
                    <div class="ab-sub">Уникальные посетители на каждом шаге и доля от открывших главную · обновлено {{ $updatedAt }}</div>
                </div>
                <div class="ab-period">
                    <label for="ab-period">Период</label>
                    <select id="ab-period" wire:model.live="period">
                        @foreach ($periods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="ab-tiles">
                <div class="ab-tile">
                    <div class="k">Главная метрика · дошли до шага 3</div>
                    <div class="v">
                        <span><span class="ab-a">{{ $visitsA ? number_format($primaryA / $visitsA * 100, 1, ',', ' ') . '%' : '—' }}</span> <small>A</small></span>
                        <span><span class="ab-b">{{ $visitsB ? number_format($primaryB / $visitsB * 100, 1, ',', ' ') . '%' : '—' }}</span> <small>B</small></span>
                    </div>
                </div>
                <div class="ab-tile">
                    <div class="k">Открыли главную</div>
                    <div class="v"><span>{{ number_format($visitsA, 0, ',', ' ') }} <small>A</small></span><span>{{ number_format($visitsB, 0, ',', ' ') }} <small>B</small></span></div>
                </div>
                <div class="ab-tile">
                    <div class="k">Достаточно ли данных</div>
                    <div class="t">
                        @if ($enough)
                            <b class="ab-b">Да: больше 300 стартов мастера на версию.</b>
                        @else
                            Пока нет. Нужно от 300 стартов мастера на каждую версию и не меньше 14 дней.
                        @endif
                    </div>
                </div>
            </div>

            <div class="ab-wrap">
                <table class="ab-table">
                    <thead>
                        <tr>
                            <th>Шаг</th>
                            <th class="num ab-a">A · сегмент</th>
                            <th class="num">% A</th>
                            <th class="num ab-b">B · карточки</th>
                            <th class="num">% B</th>
                            <th>Лидер</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="{{ $row['key'] === 'wizard_step_3' ? 'primary' : '' }}">
                                <td>{{ $row['label'] }}</td>
                                <td class="num">{{ number_format($row['a'], 0, ',', ' ') }}</td>
                                <td class="num pct">{{ $row['pa'] === null ? '—' : number_format($row['pa'], 1, ',', ' ') . '%' }}</td>
                                <td class="num">{{ number_format($row['b'], 0, ',', ' ') }}</td>
                                <td class="num pct">{{ $row['pb'] === null ? '—' : number_format($row['pb'], 1, ',', ' ') . '%' }}</td>
                                <td>
                                    @if ($row['leader'] === 'a')
                                        <span class="ab-pill a">A</span>
                                    @elseif ($row['leader'] === 'b')
                                        <span class="ab-pill b">B</span>
                                    @else
                                        <span class="ab-dash">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="ab-note">Разница меньше половины процента лидером не считается. Решение принимаем по главной метрике при достаточном объёме данных. Доли считаются от визитов той же версии; посетители с признаками ботов не учитываются.</div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
