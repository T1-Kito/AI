@extends('layouts.app', ['title' => 'Cua hang', 'crumb' => 'Cua hang'])

@section('content')
    <section class="section-heading">
        <h1>Cua hang</h1>
        <p>Xem gia va san pham cong khai. Chon mua nhanh san pham ban thich.</p>
    </section>

    <form class="search-bar" method="GET" action="{{ route('shop.index') }}">
        <span>Q</span>
        <input name="search" value="{{ $search }}" placeholder="Tim san pham...">
        <input type="hidden" name="category" value="{{ $activeCategory }}">
    </form>

    <div class="category-row">
        <span>Danh muc:</span>
        <a class="chip {{ $activeCategory === 'all' ? 'active' : '' }}" href="{{ route('shop.index', ['search' => $search]) }}">Tat ca</a>
        @foreach ($categories as $category)
            <a class="chip {{ $activeCategory === $category ? 'active' : '' }}" href="{{ route('shop.index', ['category' => $category, 'search' => $search]) }}">{{ $category }}</a>
        @endforeach
    </div>

    <div class="product-grid">
        @forelse ($products as $product)
            @php($image = $product->primaryImage())
            <article class="product-card">
                <span class="product-badge">{{ $product->category }}</span>
                <div class="product-media">
                    @if ($image)
                        @php($imageUrl = str_starts_with($image->path, 'images/') ? asset($image->path) : asset('storage/' . ltrim($image->path, '/')))
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}">
                    @else
                        <div class="placeholder-logo">
                            <span>*</span>
                            <strong>{{ strtoupper(substr($product->name, 0, 10)) }}</strong>
                        </div>
                    @endif
                </div>
                <h2>{{ $product->name }}</h2>
                <div class="tags">
                    @if ($product->duration)
                        <span>{{ $product->duration }}</span>
                    @endif
                    <span>Bao hanh</span>
                    <span>Huong dan</span>
                </div>
                <div class="price-row">
                    @if ($product->old_price)
                        <del>{{ number_format($product->old_price, 0, ',', '.') }}d</del>
                    @endif
                    <strong>{{ number_format($product->sellingPrice(), 0, ',', '.') }}d</strong>
                </div>

                @auth
                    @if (! auth()->user()->is_admin)
                        <button class="card-action" type="button" data-open-modal="buy-modal-{{ $product->id }}">
                            Mua ngay
                        </button>

                        <dialog class="buy-modal" id="buy-modal-{{ $product->id }}">
                            <form method="dialog">
                                <button class="modal-close" type="submit" aria-label="Dong">x</button>
                            </form>
                            <form method="POST" action="{{ route('shop.buy', $product) }}" class="buy-modal-form">
                                @csrf
                                <input type="hidden" name="customer_name" value="{{ auth()->user()->name }}">
                                <input type="hidden" name="customer_email" value="{{ auth()->user()->email }}">
                                <input type="hidden" name="quantity" value="1">

                                <h2>Xac nhan mua hang</h2>
                                <p>
                                    Ban co chac muon mua <strong>{{ $product->name }}</strong>
                                    voi gia <b>{{ number_format($product->sellingPrice(), 0, ',', '.') }}d</b>?
                                </p>

                                <label>
                                    Ma giam gia (neu co)
                                    <div class="coupon-line">
                                        <input name="coupon_code" placeholder="Nhap ma giam gia...">
                                        <button type="button">Kiem tra</button>
                                    </div>
                                </label>

                                <small>So du tai khoan se duoc tru sau khi bam xac nhan.</small>

                                <div class="modal-actions">
                                    <button class="ghost-button" type="button" data-close-modal>Huy</button>
                                    <button class="primary-button" type="submit">Xac nhan mua</button>
                                </div>
                            </form>
                        </dialog>
                    @else
                        <a class="card-action" href="{{ route('admin.products.edit', $product) }}">Sua trong admin</a>
                    @endif
                @else
                    <a class="card-action" href="{{ route('login') }}">Dang nhap de mua</a>
                @endauth
            </article>
        @empty
            <div class="empty-state">Chua co san pham nao. Vao Admin de them san pham dau tien nha.</div>
        @endforelse
    </div>

    {{ $products->links() }}

    <script>
        document.querySelectorAll('[data-open-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.openModal);
                if (modal) {
                    modal.showModal();
                }
            });
        });

        document.querySelectorAll('.buy-modal').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.close();
                }
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                button.closest('dialog')?.close();
            });
        });
    </script>
@endsection
