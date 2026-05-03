@extends('layouts.app', ['title' => 'Admin don hang', 'crumb' => 'Admin / Don hang'])

@section('content')
    <section class="admin-header">
        <div>
            <h1>Quan ly don hang</h1>
            <p>Doi soat order tren web, order TozPie, key da tra va xu ly retry/refund.</p>
        </div>
    </section>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>TozPie</th>
                        <th>Khach</th>
                        <th>San pham</th>
                        <th>Tien</th>
                        <th>Key</th>
                        <th>Trang thai</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ $order->upstream_order_id ?: '-' }}</td>
                            <td>{{ $order->user?->email ?: $order->customer_email }}</td>
                            <td>{{ $order->product?->name }}</td>
                            <td>{{ number_format($order->total, 0, ',', '.') }}d</td>
                            <td><code>{{ $order->purchased_key ?: '-' }}</code></td>
                            <td><span class="status {{ $order->status === 'paid' ? 'success' : 'neutral' }}">{{ $order->status }}</span></td>
                            <td class="actions">
                                @if (in_array($order->status, ['failed', 'pending'], true))
                                    <form method="POST" action="{{ route('admin.orders.retry', $order) }}">
                                        @csrf
                                        <button type="submit">Retry</button>
                                    </form>
                                @endif
                                @if ($order->user_id && ! in_array($order->status, ['refunded'], true))
                                    <form method="POST" action="{{ route('admin.orders.refund', $order) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Hoan tien don nay?')">Refund</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">Chua co don hang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    </section>
@endsection
