@extends('layouts.app', ['title' => 'Admin Dashboard', 'crumb' => 'Admin / Dashboard'])

@section('content')
    <section class="admin-header">
        <div>
            <h1>Dashboard</h1>
            <p>Theo doi so du TozPie, doanh thu, loi nhuan va cac canh bao van hanh.</p>
        </div>
        <form method="POST" action="{{ route('admin.products.sync') }}">
            @csrf
            <button class="primary-button" type="submit">Sync TozPie</button>
        </form>
    </section>

    <section class="metric-grid">
        <div class="metric-card {{ ($tozPieBalance['balance'] ?? 999999999) < 500000 ? 'danger' : '' }}">
            <span>So du TozPie</span>
            @if ($tozPieBalance)
                <strong>{{ number_format($tozPieBalance['balance'], 0, ',', '.') }}d</strong>
                <small>{{ $tozPieBalance['email'] }}</small>
            @else
                <strong>Chua doc duoc</strong>
                <small>{{ $tozPieError }}</small>
            @endif
        </div>
        <div class="metric-card">
            <span>Doanh thu hom nay</span>
            <strong>{{ number_format($revenueToday, 0, ',', '.') }}d</strong>
            <small>{{ $ordersToday }} don thanh cong</small>
        </div>
        <div class="metric-card">
            <span>Loi nhuan uoc tinh</span>
            <strong>{{ number_format($profitToday, 0, ',', '.') }}d</strong>
            <small>Gia ban tru gia nhap</small>
        </div>
        <div class="metric-card">
            <span>Tien khach da nap</span>
            <strong>{{ number_format($totalDeposits, 0, ',', '.') }}d</strong>
            <small>{{ $pendingTopups }} giao dich dang cho</small>
        </div>
    </section>

    <div class="admin-grid">
        <section class="panel">
            <h2>Don hang moi</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Khach</th>
                            <th>San pham</th>
                            <th>Tien</th>
                            <th>Trang thai</th>
                            <th>TozPie</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td>{{ $order->user?->email ?: $order->customer_email }}</td>
                                <td>{{ $order->product?->name }}</td>
                                <td>{{ number_format($order->total, 0, ',', '.') }}d</td>
                                <td><span class="status {{ $order->status === 'paid' ? 'success' : 'neutral' }}">{{ $order->status }}</span></td>
                                <td>{{ $order->upstream_order_id ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Chua co don hang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>Thong bao nhanh</h2>
            @forelse ($recentTransactions as $transaction)
                <div class="order-item">
                    <strong>{{ $transaction->user?->email }}</strong>
                    <span>{{ $transaction->type }} - {{ number_format($transaction->amount, 0, ',', '.') }}d</span>
                    <small>{{ $transaction->status }} / {{ $transaction->created_at->format('H:i d-m') }}</small>
                </div>
            @empty
                <p class="muted">Chua co thong bao tai chinh.</p>
            @endforelse

            <h2 class="sub-title">Canh bao hang</h2>
            @forelse ($lowStockProducts as $product)
                <div class="order-item">
                    <strong>{{ $product->name }}</strong>
                    <span>Ton: {{ $product->stock }} / {{ $product->in_stock ? 'con hang' : 'het hang' }}</span>
                </div>
            @empty
                <p class="muted">Hang hoa dang on.</p>
            @endforelse
        </section>
    </div>
@endsection
