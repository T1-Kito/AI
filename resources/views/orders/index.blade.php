@extends('layouts.app', ['title' => 'Don hang', 'crumb' => 'Don hang'])

@section('content')
    <section class="admin-header">
        <div>
            <h1>Don hang cua toi</h1>
            <p>Cac san pham ban da mua bang so du trong tai khoan.</p>
        </div>
        <a class="primary-button" href="{{ route('shop.index') }}">Mua tiep</a>
    </section>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>San pham</th>
                        <th>So luong</th>
                        <th>Tong tien</th>
                        <th>Key</th>
                        <th>Trang thai</th>
                        <th>Thoi gian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>{{ $order->product?->name }}</td>
                            <td>{{ $order->quantity }}</td>
                            <td>{{ number_format($order->total, 0, ',', '.') }}d</td>
                            <td><code>{{ $order->purchased_key ?: 'Dang cap' }}</code></td>
                            <td><span class="status success">{{ $order->status }}</span></td>
                            <td>{{ $order->created_at->format('H:i d-m-Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Ban chua co don hang nao.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    </section>
@endsection
