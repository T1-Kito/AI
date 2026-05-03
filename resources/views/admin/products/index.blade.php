@extends('layouts.app', ['title' => 'Admin sản phẩm', 'crumb' => 'Admin'])

@section('content')
    <section class="crm-shell">
        <section class="crm-stats">
            <article class="metric-card">
                <span>Sản phẩm tổng</span>
                <strong>{{ number_format($products->total(), 0, ',', '.') }}</strong>
                <small>Đang quản lý trên hệ thống</small>
            </article>
            <article class="metric-card">
                <span>Sản phẩm đang chạy</span>
                <strong>{{ number_format($products->where('is_active', true)->count(), 0, ',', '.') }}</strong>
                <small>Kích hoạt và còn hàng</small>
            </article>
            <article class="metric-card">
                <span>Hết hàng / Ẩn</span>
                <strong>{{ number_format($products->where('in_stock', false)->count(), 0, ',', '.') }}</strong>
                <small>Cần xử lý lại tồn kho</small>
            </article>
            <article class="metric-card">
                <span>Đơn mới gần đây</span>
                <strong>{{ number_format($orders->count(), 0, ',', '.') }}</strong>
                <small>Cập nhật realtime theo phiên</small>
            </article>
        </section>

        <section class="panel admin-toolbox admin-toolbox-modern crm-toolbar">
            <div class="header-actions">
                <form id="sync-form" method="POST" action="{{ route('admin.products.sync') }}">
                    @csrf
                    <button class="outline-button" type="submit">Sync TozPie</button>
                </form>
                <a class="primary-button" href="{{ route('admin.products.create') }}">+ Thêm sản phẩm</a>
            </div>
            <form id="markup-form" method="POST" action="{{ route('admin.products.markup') }}" class="inline-form">
                @csrf
                <label>
                    Markup chung (%)
                    <input name="markup" type="number" min="0" max="500" value="{{ env('TOZPIE_MARKUP_PERCENT', 15) }}">
                </label>
                <button class="primary-button" type="submit">Áp dụng markup</button>
            </form>
            <p class="muted">Giá bán mới = giá nhập TozPie * markup. Các thao tác chạy ngầm, không tải lại trang.</p>
        </section>

        <section class="panel panel-products crm-main-panel full-width-panel">
            <div class="panel-head crm-head">
                <div>
                    <h2>Quản lý sản phẩm</h2>
                    <span class="muted">Bảng dữ liệu chính có filter / search / sort</span>
                </div>
                <div class="crm-filters">
                    <input id="product-search" type="text" placeholder="Tìm sản phẩm, danh mục...">
                    <select id="stock-filter">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="running">Đang chạy</option>
                        <option value="out">Hết hàng</option>
                        <option value="hidden">Đang ẩn</option>
                    </select>
                </div>
            </div>

            <div class="table-wrap products-table-wrap crm-table-wrap">
                <table id="products-table">
                    <thead>
                        <tr>
                            <th data-sort="name">Sản phẩm</th>
                            <th data-sort="category">Danh mục</th>
                            <th data-sort="import">Giá nhập</th>
                            <th data-sort="sell">Giá bán</th>
                            <th data-sort="profit">Lợi nhuận</th>
                            <th data-sort="stock">Tồn</th>
                            <th data-sort="status">Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php
                                $sell = $product->sellingPrice();
                                $profit = max(0, $sell - $product->import_price);
                                $status = (! $product->is_active) ? 'hidden' : (($product->in_stock && $product->stock > 0) ? 'running' : 'out');
                            @endphp
                            <tr data-name="{{ strtolower($product->name) }}" data-category="{{ strtolower($product->category) }}" data-import="{{ (int) $product->import_price }}" data-sell="{{ (int) $sell }}" data-profit="{{ (int) $profit }}" data-stock="{{ (int) $product->stock }}" data-status="{{ $status }}">
                                <td>
                                    <div class="mini-product">
                                        @php($image = $product->primaryImage())
                                        @if ($image)
                                            <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $product->name }}">
                                        @else
                                            <span></span>
                                        @endif
                                        <strong>{{ $product->name }}</strong>
                                    </div>
                                </td>
                                <td>{{ $product->category }}</td>
                                <td>{{ number_format($product->import_price, 0, ',', '.') }}đ</td>
                                <td>{{ number_format($sell, 0, ',', '.') }}đ</td>
                                <td>{{ number_format($profit, 0, ',', '.') }}đ</td>
                                <td>{{ $product->stock }}</td>
                                <td>
                                    <span class="status {{ $status === 'running' ? 'success' : ($status === 'out' ? 'danger' : 'warning') }}">
                                        {{ $status === 'running' ? 'Đang chạy' : ($status === 'out' ? 'Hết hàng' : 'Đang ẩn') }}
                                    </span>
                                </td>
                                <td class="actions quick-actions">
                                    <a href="{{ route('admin.products.edit', $product) }}">Sửa nhanh</a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Xóa sản phẩm này?')">Xóa</button>
                                    </form>
                                    <a href="{{ route('admin.products.edit', $product) }}">Chi tiết</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        </section>

        <section class="panel crm-side-panel activity-panel-below">
            <h2>Hoạt động gần đây</h2>
            @forelse ($orders as $order)
                <div class="order-item">
                    <strong>{{ $order->customer_name }}</strong>
                    <span>{{ $order->product?->name }} x {{ $order->quantity }}</span>
                    <b>{{ number_format($order->total, 0, ',', '.') }}đ</b>
                    <small>{{ $order->customer_phone ?: $order->customer_email }}</small>
                </div>
            @empty
                <p class="muted">Chưa có đơn hàng.</p>
            @endforelse
        </section>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const table = document.getElementById('products-table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const searchInput = document.getElementById('product-search');
            const stockFilter = document.getElementById('stock-filter');
            let sortKey = null;
            let sortDir = 1;

            const applyFilter = () => {
                const q = (searchInput.value || '').toLowerCase().trim();
                const f = stockFilter.value;
                rows.forEach((row) => {
                    const text = `${row.dataset.name} ${row.dataset.category}`;
                    const okText = !q || text.includes(q);
                    const okStatus = f === 'all' || row.dataset.status === f;
                    row.style.display = okText && okStatus ? '' : 'none';
                });
            };

            const sortRows = (key) => {
                if (sortKey === key) sortDir *= -1;
                else { sortKey = key; sortDir = 1; }

                const sorted = [...rows].sort((a, b) => {
                    const av = a.dataset[key] ?? '';
                    const bv = b.dataset[key] ?? '';
                    const an = Number(av), bn = Number(bv);
                    if (!Number.isNaN(an) && !Number.isNaN(bn)) return (an - bn) * sortDir;
                    return av.localeCompare(bv) * sortDir;
                });
                sorted.forEach((row) => tbody.appendChild(row));
                applyFilter();
            };

            table.querySelectorAll('th[data-sort]').forEach((th) => {
                th.style.cursor = 'pointer';
                th.addEventListener('click', () => sortRows(th.dataset.sort));
            });

            searchInput.addEventListener('input', applyFilter);
            stockFilter.addEventListener('change', applyFilter);

            const bindAjaxForm = (id, successText) => {
                const form = document.getElementById(id);
                if (!form) return;
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const btn = form.querySelector('button[type="submit"]');
                    const old = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Đang xử lý...';
                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                                'Accept': 'application/json'
                            },
                            body: new FormData(form)
                        });
                        if (!res.ok) throw new Error('Request failed');
                        btn.textContent = successText;
                        setTimeout(() => { btn.textContent = old; }, 1200);
                    } catch (err) {
                        btn.textContent = 'Thử lại';
                        setTimeout(() => { btn.textContent = old; }, 1200);
                    } finally {
                        btn.disabled = false;
                    }
                });
            };

            bindAjaxForm('sync-form', 'Đã đồng bộ');
            bindAjaxForm('markup-form', 'Đã áp dụng');
        });
    </script>
@endsection
