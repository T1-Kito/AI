@extends('layouts.app', ['title' => $product->exists ? 'Sua san pham' : 'Them san pham', 'crumb' => 'Admin / San pham'])

@section('content')
    <section class="admin-header">
        <div>
            <h1>{{ $product->exists ? 'Sua san pham' : 'Them san pham' }}</h1>
            <p>Nhap thong tin ban hang va chon mot hoac nhieu anh san pham.</p>
        </div>
        <a class="outline-button" href="{{ route('admin.products.index') }}">Quay lai</a>
    </section>

    <form class="product-form" method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <label>
            Ten san pham
            <input name="name" value="{{ old('name', $product->name) }}" required>
        </label>

        <label>
            ID san pham ben TozPie
            <input name="tozpie_product_id" type="number" min="1" value="{{ old('tozpie_product_id', $product->tozpie_product_id ?? $product->api_product_id) }}" placeholder="Vi du: 1">
        </label>

        <label>
            Danh muc
            <input name="category" value="{{ old('category', $product->category) }}" placeholder="Cursor 100 request/ngay" required>
        </label>

        <div class="form-row">
            <label>
                Gia nhap TozPie
                <input name="import_price" type="number" min="0" value="{{ old('import_price', $product->import_price ?? 0) }}">
            </label>
            <label>
                Gia ban cho khach
                <input name="selling_price" type="number" min="0" value="{{ old('selling_price', $product->selling_price ?: $product->price) }}" required>
            </label>
        </div>

        <div class="form-row">
            <label>
                Gia cu
                <input name="old_price" type="number" min="0" value="{{ old('old_price', $product->old_price) }}">
            </label>
        </div>

        <div class="form-row">
            <label>
                Thoi han
                <input name="duration" value="{{ old('duration', $product->duration) }}" placeholder="1 ngay, 1 tuan...">
            </label>
            <label>
                Ton kho
                <input name="stock" type="number" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required>
            </label>
        </div>

        <label>
            Mo ta
            <textarea name="description" rows="4">{{ old('description', $product->description) }}</textarea>
        </label>

        <label>
            Anh san pham
            <input name="images[]" type="file" accept="image/*" multiple>
        </label>

        @if ($product->exists && $product->images->isNotEmpty())
            <div class="image-list">
                @foreach ($product->images as $image)
                    <div class="image-tile">
                        <img src="{{ asset('storage/' . $image->path) }}" alt="">
                        <form method="POST" action="{{ route('admin.product-images.destroy', $image) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Xoa anh</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        <label class="check-line">
            <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->is_active))>
            Hien thi ngoai cua hang
        </label>

        <button class="primary-button" type="submit">{{ $product->exists ? 'Luu thay doi' : 'Them san pham' }}</button>
    </form>
@endsection
