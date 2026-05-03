@extends('layouts.app', ['title' => 'Huong dan cai dat va su dung Cursor Pro', 'crumb' => 'Huong dan / Cursor Pro'])

@section('content')
    <section class="guide-shell">
        <div class="guide-head">
            <h1>Huong dan cai dat va su dung Cursor Pro</h1>
            <p class="guide-meta">Cap nhat: {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <article class="guide-content panel">
            <h2>Ap dung Cursor Version < 3.0 tren macOS va Windows</h2>

            <h3>Cac buoc chuan bi</h3>
            <ul>
                <li>Tai Cursor (neu chua cai): <a href="https://cursor.com/downloads" target="_blank" rel="noopener">tai day</a>.</li>
                <li>Tai file extension Plugin ban phu hop: <a href="#">tai day</a>.</li>
            </ul>

            <h3>Huong dan cai dat va kich hoat</h3>
            <div class="guide-video">
                <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Huong dan Cursor Pro" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
            </div>

            <h3>Duoi day la huong dan chi tiet:</h3>

            <h4>Buoc 1. Cai dat phan mo rong kich hoat</h4>
            <p class="guide-warning">Neu truoc day da cai dat phien ban cu co loi kich hoat plugin, hay go cai dat truoc.</p>
            <p>Ban hay chon 1 trong 2 cach duoi day de thuc hien nhe.</p>

            <p><strong>Cach 1:</strong> Keo va tha Extension Plugin</p>
            <img class="guide-image" src="{{ asset('images/guides/cursor-step-1.jpg') }}" alt="Keo va tha extension">

            <p><strong>Cach 2:</strong> Neu ban khong the keo tha Extension, hay lam thu cong nhu huong dan ben duoi.</p>
            <img class="guide-image" src="{{ asset('images/guides/cursor-step-2.jpg') }}" alt="Mo VSIX trong Cursor">

            <p>Chon tiep FILE Plugin vua tai.</p>
            <img class="guide-image" src="{{ asset('images/guides/cursor-step-3.jpg') }}" alt="Chon file plugin VSIX">

            <h4>Buoc 2. Nhan key va kich hoat</h4>
            <p>Truy cap extension Cursor Pool va lam theo huong dan de nhan key kich hoat.</p>
            <img class="guide-image" src="{{ asset('images/guides/cursor-step-4.jpg') }}" alt="Mo extension Cursor Pool">
        </article>
    </section>
@endsection
