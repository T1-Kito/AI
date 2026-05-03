@extends('layouts.app', ['title' => 'Tai chinh', 'crumb' => 'Admin / Tai chinh'])

@section('content')
    <section class="admin-header finance-header">
        <div>
            <h1>Tai chinh & vi khach</h1>
            <p>Xem so du user, cong tru tien thu cong va doi soat giao dich tap trung.</p>
        </div>
    </section>

    <section class="metric-grid finance-stat-grid">
        <article class="metric-card">
            <span>Tong so du he thong</span>
            <strong>{{ number_format($stats['total_balance'], 0, ',', '.') }}d</strong>
            <small>Toan bo khach hang</small>
        </article>
        <article class="metric-card">
            <span>Tong nap thang nay</span>
            <strong>{{ number_format($stats['monthly_deposit_total'], 0, ',', '.') }}d</strong>
            <small>{{ $stats['monthly_deposit_count'] }} giao dich</small>
        </article>
        <article class="metric-card">
            <span>Khach hang moi</span>
            <strong>{{ number_format($stats['new_customers_count'], 0, ',', '.') }}</strong>
            <small>Trong thang hien tai</small>
        </article>
        <article class="metric-card">
            <span>Giao dich cho xu ly</span>
            <strong>{{ number_format($stats['pending_transactions_count'], 0, ',', '.') }}</strong>
            <small>Can doi soat</small>
        </article>
    </section>

    <section class="panel finance-panel">
        <div class="panel-heading">
            <h2>Khach hang</h2>
            <form class="finance-filters" method="GET" action="{{ route('admin.finance.index') }}">
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Tim email / ten khach hang">
                <select name="status">
                    <option value="">Tat ca trang thai so du</option>
                    <option value="has_balance" @selected($filters['status'] === 'has_balance')>Con so du</option>
                    <option value="zero_balance" @selected($filters['status'] === 'zero_balance')>So du = 0</option>
                </select>
                <select name="range">
                    <option value="">Khach hang: Tat ca thoi gian</option>
                    <option value="7d" @selected($filters['range'] === '7d')>Khach hang: 7 ngay qua</option>
                    <option value="30d" @selected($filters['range'] === '30d')>Khach hang: 30 ngay qua</option>
                    <option value="month" @selected($filters['range'] === 'month')>Khach hang: Thang nay</option>
                </select>
                <select name="tx_status">
                    <option value="">Giao dich: Tat ca trang thai</option>
                    <option value="pending" @selected($filters['tx_status'] === 'pending')>Pending</option>
                    <option value="success" @selected($filters['tx_status'] === 'success')>Success</option>
                    <option value="failed" @selected($filters['tx_status'] === 'failed')>Failed</option>
                </select>
                <select name="tx_range">
                    <option value="">Giao dich: Tat ca thoi gian</option>
                    <option value="7d" @selected($filters['tx_range'] === '7d')>Giao dich: 7 ngay qua</option>
                    <option value="30d" @selected($filters['tx_range'] === '30d')>Giao dich: 30 ngay qua</option>
                    <option value="month" @selected($filters['tx_range'] === 'month')>Giao dich: Thang nay</option>
                </select>
                <button class="outline-button small" type="submit">Loc</button>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Ten</th>
                        <th class="text-right">So du</th>
                        <th class="text-center">Hanh dong</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->name }}</td>
                            <td class="text-right">{{ number_format($user->wallet_balance, 0, ',', '.') }}d</td>
                            <td class="text-center">
                                <button class="outline-button small" type="button" data-offcanvas-open="adjust-{{ $user->id }}">Dieu chinh</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">Khong co khach hang phu hop bo loc.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </section>

    <section class="panel finance-panel">
        <h2>Transaction logs</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ma giao dich</th>
                        <th>Khach hang</th>
                        <th>Thoi gian</th>
                        <th>Phuong thuc</th>
                        <th class="text-right">Tien</th>
                        <th class="text-center">Trang thai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="accent-text">{{ $transaction->code }}</td>
                            <td>{{ $transaction->user?->email }}</td>
                            <td>{{ optional($transaction->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $transaction->method }}</td>
                            <td class="text-right">{{ number_format($transaction->amount, 0, ',', '.') }}d</td>
                            <td class="text-center">
                                <span class="status {{ $transaction->status === 'success' ? 'success' : 'neutral' }}">{{ $transaction->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">Chua co giao dich.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @foreach ($users as $user)
        <aside id="adjust-{{ $user->id }}" class="finance-offcanvas" aria-hidden="true">
            <div class="finance-offcanvas__overlay" data-offcanvas-close></div>
            <div class="finance-offcanvas__panel" role="dialog" aria-modal="true" aria-label="Dieu chinh so du">
                <button class="finance-offcanvas__close" type="button" data-offcanvas-close>&times;</button>
                <h3>Dieu chinh so du</h3>
                <p>{{ $user->email }} - So du hien tai: <strong>{{ number_format($user->wallet_balance, 0, ',', '.') }}d</strong></p>

                <form method="POST" action="{{ route('admin.finance.adjust-balance', $user) }}" class="finance-adjust-form">
                    @csrf
                    <label>
                        So tien (+/-)
                        <input name="amount" type="number" placeholder="+50000 hoac -50000" required>
                    </label>
                    <label>
                        Ghi chu
                        <textarea name="note" rows="4" placeholder="Nhap ghi chu noi bo"></textarea>
                    </label>
                    <button class="primary-button" type="submit">Luu thay doi</button>
                </form>
            </div>
        </aside>
    @endforeach

    <script>
        document.querySelectorAll('[data-offcanvas-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = document.getElementById(button.getAttribute('data-offcanvas-open'));
                if (!target) return;
                target.classList.add('is-open');
                target.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            });
        });

        document.querySelectorAll('[data-offcanvas-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const panel = button.closest('.finance-offcanvas');
                if (!panel) return;
                panel.classList.remove('is-open');
                panel.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            });
        });
    </script>
@endsection
