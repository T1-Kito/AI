@extends('layouts.app', ['title' => 'Nap tien', 'crumb' => 'Nap tien'])

@section('content')
    <section class="section-heading">
        <h1>Nap tien vi</h1>
        <p>Nhap so tien ban muon nap, tao giao dich va quet QR de chuyen khoan nhanh.</p>
    </section>

    <div class="wallet-grid">
        <section class="wallet-main topup-modern">
            <div class="topup-hero">
                <div>
                    <h2>Nap tien tu dong qua ngan hang</h2>
                    <p>He thong se tu dong cong so du ngay khi webhook xac nhan giao dich thanh cong.</p>
                </div>
                <span class="badge-soft">Nap linh hoat theo nhu cau</span>
            </div>

            <form id="topup-form" class="topup-card modern-card" method="POST" action="{{ route('wallet.store') }}">
                @csrf

                <div class="method-tabs modern-tabs">
                    <label>
                        <input type="radio" name="method" value="bank" checked>
                        <span>Ngan hang</span>
                    </label>
                    <label>
                        <input type="radio" name="method" value="crypto">
                        <span>Crypto</span>
                    </label>
                </div>

                <label class="field-label" for="topup-amount">So tien nap</label>
                <div class="topup-line modern-line">
                    <input id="topup-amount" name="amount" type="number" min="1" step="1" value="{{ old('amount', $suggestedAmount) }}" required>
                    <button id="create-topup-btn" class="primary-button" type="submit">Tao giao dich</button>
                </div>
                <small>Ban co the nap so tien bat ky. Vui long chuyen dung noi dung de he thong doi chieu.</small>
                <p id="topup-error" class="form-error" hidden></p>
            </form>

            <section class="panel wallet-history">
                <h2>Lich su nap tien</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>So tien</th>
                                <th>Phi</th>
                                <th>Ma giao dich</th>
                                <th>Phuong thuc</th>
                                <th>Trang thai</th>
                                <th>Thoi gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($deposits as $deposit)
                                <tr>
                                    <td>{{ number_format($deposit->amount, 0, ',', '.') }}d</td>
                                    <td>{{ number_format($deposit->fee, 0, ',', '.') }}d</td>
                                    <td class="accent-text">{{ $deposit->code }}</td>
                                    <td>{{ $deposit->method === 'bank' ? 'Ngan hang' : 'Crypto' }}</td>
                                    <td>
                                        <span class="status {{ $deposit->status === 'completed' ? 'success' : 'pending' }}">
                                            {{ $deposit->status }}
                                        </span>
                                    </td>
                                    <td>{{ $deposit->created_at->format('H:i d-m-Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">Chua co giao dich nap tien.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $deposits->links() }}
            </section>
        </section>

        <aside class="wallet-side modern-side">
            <span>So du hien tai</span>
            <strong>{{ number_format(auth()->user()->wallet_balance, 0, ',', '.') }}d</strong>
            <a class="outline-button wide" href="{{ route('shop.index') }}">Quay lai cua hang</a>
        </aside>
    </div>

    <div id="topup-modal" class="topup-modal" aria-hidden="true">
        <div class="topup-modal__overlay" data-close-modal></div>
        <div class="topup-modal__content" role="dialog" aria-modal="true" aria-labelledby="topup-modal-title">
            <button type="button" class="topup-modal__close" data-close-modal>&times;</button>
            <h3 id="topup-modal-title">Thanh toan giao dich nap tien</h3>
            <p class="topup-modal__hint">Quet ma QR hoac chuyen khoan thu cong theo thong tin ben duoi.</p>

            <div class="bank-card modal-bank-card">
                <div class="bank-meta">
                    <strong id="modal-bank-name"></strong>
                    <span>Ma ngan hang: <b id="modal-bank-code"></b></span>
                    <span>So TK: <b id="modal-account-number"></b></span>
                    <span>Chu TK: <b id="modal-account-name"></b></span>
                    <span>So tien: <b id="modal-amount"></b></span>
                    <span>Noi dung CK: <b id="modal-code" class="accent-text"></b></span>
                </div>
                <div class="qr-image-box">
                    <img id="modal-qr" src="" alt="QR thanh toan" loading="lazy">
                </div>
            </div>

            <div id="modal-status" class="topup-status pending">Dang cho thanh toan...</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('topup-form');
            const amountInput = document.getElementById('topup-amount');
            const submitBtn = document.getElementById('create-topup-btn');
            const errorBox = document.getElementById('topup-error');

            const modal = document.getElementById('topup-modal');
            const modalStatus = document.getElementById('modal-status');
            const modalQr = document.getElementById('modal-qr');

            const modalFields = {
                bankName: document.getElementById('modal-bank-name'),
                bankCode: document.getElementById('modal-bank-code'),
                accountNumber: document.getElementById('modal-account-number'),
                accountName: document.getElementById('modal-account-name'),
                amount: document.getElementById('modal-amount'),
                code: document.getElementById('modal-code'),
            };

            let pollTimer = null;

            const formatCurrency = (value) => `${new Intl.NumberFormat('vi-VN').format(value)}d`;

            const showError = (message) => {
                errorBox.textContent = message;
                errorBox.hidden = false;
            };

            const clearError = () => {
                errorBox.hidden = true;
                errorBox.textContent = '';
            };

            const openModal = () => {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            };

            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            };

            modal.querySelectorAll('[data-close-modal]').forEach((el) => {
                el.addEventListener('click', closeModal);
            });

            const setPendingStatus = () => {
                modalStatus.className = 'topup-status pending';
                modalStatus.textContent = 'Dang cho thanh toan...';
            };

            const setSuccessStatus = () => {
                modalStatus.className = 'topup-status success';
                modalStatus.textContent = 'Thanh toan thanh cong! Vi da duoc cong tien.';
            };

            const startPollingStatus = (depositId) => {
                if (pollTimer) clearInterval(pollTimer);

                pollTimer = setInterval(async () => {
                    try {
                        const response = await fetch(`/wallet/topup/${depositId}/status`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const result = await response.json();

                        if (result?.is_completed) {
                            setSuccessStatus();
                            clearInterval(pollTimer);
                            pollTimer = null;
                            setTimeout(() => window.location.reload(), 1400);
                        }
                    } catch (error) {
                        // keep polling silently
                    }
                }, 3000);
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearError();

                const amount = Number.parseInt(amountInput.value, 10);
                if (!amount || amount < 1) {
                    showError('So tien nap phai lon hon 0.');
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Dang tao...';

                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        const message = result?.message || result?.error || 'Khong tao duoc giao dich, vui long thu lai.';
                        showError(message);
                        return;
                    }

                    const payload = result.data;
                    modalFields.bankName.textContent = payload.bank_account.bank_name;
                    modalFields.bankCode.textContent = payload.bank_account.bank_code;
                    modalFields.accountNumber.textContent = payload.bank_account.account_number;
                    modalFields.accountName.textContent = payload.bank_account.account_name;
                    modalFields.amount.textContent = formatCurrency(payload.amount);
                    modalFields.code.textContent = payload.code;
                    modalQr.src = payload.qr_url;
                    modalQr.alt = `Ma QR giao dich ${payload.code}`;

                    setPendingStatus();
                    openModal();
                    startPollingStatus(payload.deposit_id);
                } catch (error) {
                    showError('Co loi he thong, vui long thu lai sau.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Tao giao dich';
                }
            });
        });
    </script>
@endsection