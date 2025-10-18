{{-- resources/views/frontend/faq/index.blade.php --}}
@extends('layouts.frontend')

@section('title', 'คำถามที่พบบ่อย')
@section('meta_description', 'คำถามที่พบบ่อยเกี่ยวกับการใช้งานระบบประเมินผลการจัดบริการหน่วยบริการสุขภาพผู้เดินทาง')
@section('canonical', route('frontend.faq.index'))
@section('og_title', 'คำถามที่พบบ่อย')
@section('og_description', 'รวมคำถามและคำตอบเกี่ยวกับระบบหน่วยบริการสุขภาพผู้เดินทาง จากกรมควบคุมโรค')

@section('page_header')
    <header class="bg-light-page border-bottom py-3">
        <div class="container">
            <h1 class="h3 mb-2 section-title text-dark">คำถามที่พบบ่อย (FAQ)</h1>
            <nav aria-label="breadcrumb" class="text-end">
                <ol class="breadcrumb mb-0 d-inline-flex justify-content-end">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">หน้าแรก</a></li>
                    <li class="breadcrumb-item active" aria-current="page">คำถามที่พบบ่อย</li>
                </ol>
            </nav>
        </div>
    </header>
@endsection

@section('content')
    <!-- #################### FAQ Section #################### -->
    @if ($faqs->count())
        <section id="faq" class="py-5 bg-light-custom">
            <div class="container pt-3 pb-5">
                <div class="accordion" id="faqAccordion">
                    @foreach ($faqs as $faq)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $loop->index }}">
                                <button class="accordion-button {{ !$loop->first ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $loop->index }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="collapse{{ $loop->index }}">
                                    {{ $faq->question }}
                                </button>
                            </h2>
                            <div id="collapse{{ $loop->index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    {!! nl2br(e($faq->answer)) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
