@extends('layouts.app', ['title' => 'Tai chinh', 'crumb' => 'Admin / Tai chinh'])

@section('content')
    <section class="admin-header">
        <div>
            <h1>Tai chinh & vi khach</h1>
            <p>Xem so du user, cong tru tien thu cong va doi soat lich su nap.</p>
        </div>
    </section>

    <section class="panel">
        <h2>Khach hang</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Ten</th>
                        <th>So du</th>
                        <th>Dieu chinh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ number_format($user->wallet_balance, 0, ',', '.') }}d</td>
                            <td>
                                <form class="table-form" method="POST" action="{{ route('admin.finance.adjust-balance', $user) }}">
                                    @csrf
                                    <input name="amount" type="number" placeholder="+50000 / -50000" required>
                                    <input name="note" placeholder="Ghi chu">
                                    <button class="outline-button small" type="submit">Luu</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </section>

    <div class="admin-grid finance-grid">
        <section class="panel">
            <h2>Transaction logs</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Khach</th>
                            <th>Loai</th>
                            <th>Ma</th>
                            <th>Tien</th>
                            <th>Trang thai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->user?->email }}</td>
                                <td>{{ $transaction->type }}</td>
                                <td class="accent-text">{{ $transaction->code }}</td>
                                <td>{{ number_format($transaction->amount, 0, ',', '.') }}d</td>
                                <td><span class="status {{ $transaction->status === 'success' ? 'success' : 'neutral' }}">{{ $transaction->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>Lich su nap tien</h2>
            @forelse ($deposits as $deposit)
                <div class="order-item">
                    <strong>{{ $deposit->user?->email }}</strong>
                    <span>{{ number_format($deposit->amount, 0, ',', '.') }}d - {{ $deposit->method }}</span>
                    <small>{{ $deposit->code }} / {{ $deposit->status }}</small>
                </div>
            @empty
                <p class="muted">Chua co nap tien.</p>
            @endforelse
        </section>
    </div>
@endsection
