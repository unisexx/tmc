{{-- resources/views/frontend/news/show.blade.php --}}
@extends('layouts.frontend')

@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $dt = Carbon::parse($news->created_at)->locale('th');
    $thDate = $dt->translatedFormat('j F ') . ($dt->year + 543);
@endphp

@section('title', $news->title)
@section('meta_description', Str::limit(strip_tags($news->excerpt ?: $news->body), 160))
@section('og_title', $news->title)
@section('og_description', Str::limit(strip_tags($news->excerpt ?: $news->body), 160))
@section('og_image', $news->image_url)

@section('page_header')
    <header class="bg-light-page border-bottom py-3">
        <div class="container">
            <h1 class="h3 mb-2 section-title text-dark">ข่าวประชาสัมพันธ์</h1>
            <nav aria-label="breadcrumb" class="text-end">
                <ol class="breadcrumb mb-0 d-inline-flex justify-content-end">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">หน้าแรก</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('frontend.news.index') }}">ข่าวประชาสัมพันธ์</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($news->title, 60) }}</li>
                </ol>
            </nav>
        </div>
    </header>
@endsection

@section('content')
    <main class="py-5">
        <div class="container pb-5">
            <div class="news-detail">
                <h3 class="news-detail-title">{{ $news->title }}</h3>
                <p class="meta-info">
                    <span class="bi bi-calendar" aria-hidden="true"></span>
                    <time datetime="{{ $news->created_at->toDateString() }}">{{ $thDate }}</time>
                    <span class="bi bi-eye-fill ms-2" aria-hidden="true"></span>
                    <span>{{ number_format((int) ($news->views ?? 0)) }}</span>
                </p>

                <div class="news-detail-content">
                    @if ($news->image_url)
                        <img src="{{ $news->image_url }}" alt="{{ $news->title }}" class="img-fluid rounded mb-4">
                    @endif

                    {{-- เนื้อหาข่าวจากฐานข้อมูล --}}
                    {!! $news->body !!}
                </div>

                {{-- แกลเลอรีเพิ่มเติม (หากมี) --}}
                @if (!empty($news->gallery ?? []))
                    <div class="news-gallery mt-5">
                        <h5 class="mb-3">ภาพเพิ่มเติมจากข่าว</h5>
                        <div class="row g-2">
                            @foreach ($news->gallery as $i => $img)
                                <div class="col-12 col-md-4 col-lg-3">
                                    <a href="{{ $img }}" class="glightbox" data-gallery="news-gallery">
                                        <img src="{{ $img }}" class="img-fluid rounded" alt="ภาพเพิ่มเติม {{ $i + 1 }}">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ปุ่มย้อนกลับ --}}
                <a href="{{ route('frontend.news.index') }}" class="btn btn-outline-primary mt-4">← ย้อนกลับหน้าข่าว</a>
            </div>
        </div>
    </main>
@endsection
