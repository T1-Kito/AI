@extends('layouts.app', ['title' => 'Cau hinh', 'crumb' => 'Admin / Cau hinh'])

@section('content')
    <section class="admin-header">
        <div>
            <h1>Cau hinh he thong</h1>
            <p>Quan ly API TozPie, webhook ngan hang, markup mac dinh va che do bao tri.</p>
        </div>
    </section>

    <section class="panel settings-panel">
        <form class="product-form" method="POST" action="{{ route('admin.settings.api') }}">
            @csrf
            <label>
                TozPie base URL
                <input name="base_url" value="{{ old('base_url', $baseUrl ?: 'https://ai.tozpie.net') }}" required>
            </label>

            <label>
                UPSTREAM_API_KEY
                <input name="api_key" placeholder="{{ $apiKeySet ? 'Da cau hinh, de trong neu khong doi' : 'Dan API key TozPie vao day' }}">
            </label>

            <div class="form-row">
                <label>
                    Markup mac dinh (%)
                    <input name="markup" type="number" min="0" max="500" value="{{ old('markup', $markup) }}" required>
                </label>
                <label>
                    BANK_WEBHOOK_TOKEN
                    <input name="bank_token" placeholder="{{ $bankTokenSet ? 'Da cau hinh, de trong neu khong doi' : 'Token bao mat webhook ngan hang' }}">
                </label>
            </div>

            <label class="check-line">
                <input name="maintenance" type="checkbox" value="1" @checked($maintenance)>
                Bat Maintenance Mode cho cua hang
            </label>

            <button class="primary-button" type="submit">Luu cau hinh</button>
        </form>
    </section>
@endsection
