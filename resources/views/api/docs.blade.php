@extends('layouts.app', ['title' => 'Tai lieu API', 'crumb' => 'Tai lieu API'])

@section('content')
    <section class="api-doc">
        <div class="api-hero">
            <div>
                <h1>Tai lieu API</h1>
                <p>Goi API bang HTTP, truyen khoa tai khoan qua header <code>X-API-Key</code> hoac query <code>api_key</code>.</p>
            </div>
            <form method="POST" action="{{ route('api.rotate') }}">
                @csrf
                <button class="outline-button" type="submit" onclick="return confirm('Doi API key moi? Key cu se khong dung duoc nua.')">Doi API key</button>
            </form>
        </div>

        <section class="api-key-card">
            <span>API key cua ban</span>
            <code>{{ $apiKey }}</code>
            <small>Khong chia se key cong khai. Co the doi key bat cu luc nao neu bi lo.</small>
        </section>

        <section class="api-section">
            <h2>Goc URL</h2>
            <pre><code>{{ $baseUrl }}</code></pre>
        </section>

        <section class="api-section">
            <h2>GET /api/balance</h2>
            <p>Tra ve so du va thong tin tai khoan.</p>
            <pre><code>{
  "success": true,
  "balance": 100000,
  "email": "user@example.com"
}</code></pre>
        </section>

        <section class="api-section">
            <h2>GET /api/products</h2>
            <p>Lay danh sach san pham tren cua hang.</p>
            <table class="doc-table">
                <tr><th>Truong</th><th>Mo ta</th></tr>
                <tr><td>id</td><td>ID san pham, dung cho <code>product_id</code> khi mua.</td></tr>
                <tr><td>name</td><td>Ten hien thi.</td></tr>
                <tr><td>price</td><td>Gia VND.</td></tr>
                <tr><td>in_stock</td><td>true = con mua duoc; false = het hang hoac tam khoa.</td></tr>
            </table>
            <pre><code>{
  "success": true,
  "products": [
    { "id": 1, "name": "...", "price": 50000, "in_stock": true }
  ]
}</code></pre>
        </section>

        <section class="api-section">
            <h2>POST /api/buy-key</h2>
            <p>API mua hang, tru tien truc tiep trong vi. Header: <code>Content-Type: application/json</code>.</p>
            <table class="doc-table">
                <tr><th>Truong</th><th>Kieu</th><th>Bat buoc</th><th>Mo ta</th></tr>
                <tr><td>product_id</td><td>so</td><td>Co</td><td>ID san pham lay tu <code>/api/products</code>.</td></tr>
                <tr><td>idempotency_key</td><td>chuoi</td><td>Khong</td><td>8-64 ky tu trong <code>[a-zA-Z0-9_-]</code>, tranh tao don trung.</td></tr>
            </table>
            <pre><code>{
  "product_id": 1,
  "idempotency_key": "my-req-uuid-001"
}</code></pre>
            <pre><code>{
  "success": true,
  "key": "KEY-...",
  "order_id": 123
}</code></pre>
        </section>

        <section class="api-section">
            <h2>GET /api/purchase-history</h2>
            <p>Bat buoc <code>start_date</code> va <code>end_date</code> dinh dang Y-m-d. Khoang ngay toi da 7 ngay.</p>
            <pre><code>{
  "success": true,
  "start_date": "2026-03-22",
  "end_date": "2026-03-28",
  "limit": 50,
  "orders": [
    {
      "order_id": 123,
      "product_id": 1,
      "product_name": "...",
      "key": "KEY-...",
      "price": 50000,
      "purchased_at": "2026-03-25 14:30:00"
    }
  ]
}</code></pre>
        </section>

        <section class="api-section">
            <h2>Vi du curl</h2>
            <pre><code>curl -s -H "X-API-Key: {{ $apiKey }}" "{{ $baseUrl }}/api/balance"

curl -s -H "X-API-Key: {{ $apiKey }}" "{{ $baseUrl }}/api/products"

curl -s -X POST -H "X-API-Key: {{ $apiKey }}" -H "Content-Type: application/json" \
  -d "{\"product_id\":1}" "{{ $baseUrl }}/api/buy-key"

curl -s -G -H "X-API-Key: {{ $apiKey }}" \
  --data-urlencode "start_date=2026-03-22" \
  --data-urlencode "end_date=2026-03-28" \
  --data-urlencode "limit=50" \
  "{{ $baseUrl }}/api/purchase-history"</code></pre>
        </section>
    </section>
@endsection
