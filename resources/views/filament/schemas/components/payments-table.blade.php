@if(!$payments->isEmpty())
    <table class="payments-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Статус оплаты</th>
                <th>ID оплаты в платежной системе</th>
                <th>Дата создания</th>
                <th>Дата обновления</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payments as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>{{ $payment->statusName() }}</td>
                    <td>{{ $payment->payment_gateway_transaction }}</td>
                    <td>{{ $payment->created_at }}</td>
                    <td>{{ $payment->updated_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<style>
    .payments-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border: 1px solid #fbcfe8;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(244, 114, 182, 0.1);
        overflow: hidden;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        font-size: 14px;
        color: #374151;
    }

    .payments-table th {
        background: #ca4592;
        color: #fff;
        text-align: left;
        font-weight: 600;
        padding: 12px 16px;
        font-size: 14px;
    }

    .payments-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #fbcfe8;
    }

    .payments-table tr:last-child td {
        border-bottom: none;
    }

    .payments-table tr:nth-child(even) td {
        background-color: rgba(202, 69, 146, 0.1);
    }

    .payments-table tr:hover td {
        background-color: rgba(202, 69, 146, 0.2);
    }

    .payments-table th:first-child {
        border-top-left-radius: 16px;
    }

    .payments-table th:last-child {
        border-top-right-radius: 16px;
    }

    .payments-table tr:last-child td:first-child {
        border-bottom-left-radius: 16px;
    }

    .payments-table tr:last-child td:last-child {
        border-bottom-right-radius: 16px;
    }
</style>
