@if ($deliveries->isEmpty())
    <p style="color:#6b7280;font-size:14px">Доставок пока нет: расписание появится после первой оплаты.</p>
@else
    <table class="payments-table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Окно</th>
                <th>Статус</th>
                <th>Напоминание</th>
                <th>Заметка</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveries as $d)
                <tr>
                    <td>{{ $d->planned_date->format('d.m.Y') }} ({{ \App\Support\Days::short($d->planned_date->dayOfWeekIso) }})</td>
                    <td>{{ $d->windowLabel() ?: '—' }}</td>
                    <td>{{ \App\Models\Delivery::STATUSES[$d->status] ?? $d->status }}</td>
                    <td>{{ $d->reminded_at ? $d->reminded_at->format('d.m.Y H:i') : '—' }}</td>
                    <td>{{ $d->note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="color:#6b7280;font-size:13px;margin-top:8px">Отметить доставленной или перенести можно в разделе «Доставки».</p>
@endif
<style>
    .payments-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .payments-table th, .payments-table td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    .payments-table th { font-weight: 600; color: #374151; }
</style>
