{{-- resources/views/frontend/news/index.blade.php --}}
@extends('layouts.frontend')

@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;
@endphp

@section('title', 'ข่าวประชาสัมพันธ์')
@section('meta_description', 'ข่าวประชาสัมพันธ์จากระบบประเมินผลการจัดบริการหน่วยบริการสุขภาพผู้เดินทาง')
@section('canonical', route('frontend.news.index'))
@section('og_title', 'ข่าวประชาสัมพันธ์')
@section('og_description', 'อัปเดตข่าวล่าสุดจากกลุ่มโรคติดต่อในผู้เดินทางและแรงงานข้ามชาติ กรมควบคุมโรค')

@section('page_header')
    <header class="bg-light-page border-bottom py-3">
        <div class="container">
            <h1 class="h3 mb-2 section-title text-dark">ข่าวประชาสัมพันธ์</h1>
            <nav aria-label="breadcrumb" class="text-end">
                <ol class="breadcrumb mb-0 d-inline-flex justify-content-end">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">หน้าแรก</a></li>
                    <li class="breadcrumb-item active" aria-current="page">ข่าวประชาสัมพันธ์</li>
                </ol>
            </nav>
        </div>
    </header>
@endsection

@section('content')
    <section id="news" class="py-5 mb-5">
        <div class="container pt-4">
            <div class="row news-list">
                @forelse ($newsList as $n)
                    @php
                        $dt = Carbon::parse($n->created_at)->locale('th');
                        $thDate = $dt->translatedFormat('j F ') . ($dt->year + 543);
                    @endphp
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm position-relative">
                            <img src="{{ $n->image_url }}" class="img-fluid" alt="{{ $n->title }}">
                            <div class="card-body">
                                <p class="meta-info text-muted small mb-2">
                                    <i class="bi bi-calendar"></i>
                                    <time datetime="{{ $n->created_at->toDateString() }}">{{ $thDate }}</time>
                                    <span class="ms-3">
                                        <i class="bi bi-eye-fill"></i>
                                        {{ number_format((int) ($n->views ?? 0)) }}
                                    </span>
                                </p>
                                <h5 class="card-title line-clamp-3">{{ $n->title }}</h5>
                                @if ($n->excerpt)
                                    <p class="card-text text-secondary line-clamp-3 mb-0">{{ $n->excerpt }}</p>
                                @endif
                                <a href="{{ route('frontend.news.show', $n->id) }}" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info text-center">ยังไม่มีข่าวประชาสัมพันธ์</div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if (method_exists($newsList, 'links'))
                <div class="d-flex justify-content-center mt-4 blue-pagination">
                    {{ $newsList->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection


@push('styles')
    <style>
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endpush
