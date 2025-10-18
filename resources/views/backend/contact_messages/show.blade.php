{{-- resources/views/backend/contact_messages/show.blade.php --}}
@extends('layouts.main')

@section('title', 'ดูข้อความติดต่อ')
@section('breadcrumb-item', 'จัดการข้อมูลหน้าแรก')
@section('breadcrumb-item-active', 'ดูข้อความติดต่อ')

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- กล่องหลัก --}}
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h5 class="mb-1 text-wrap">
                        <span class="me-1">หัวข้อ : {{ $contactMessage->subject }}</span>
                        @if ($contactMessage->status === 'done')
                            <span class="badge border border-success text-success-emphasis bg-success-light shadow-sm">
                                <i class="ph-duotone ph-check-circle"></i> ดำเนินการแล้ว
                            </span>
                        @else
                            <span class="badge border border-warning text-warning-emphasis bg-warning-light shadow-sm">
                                <i class="ph-duotone ph-envelope-open"></i> รอดำเนินการ
                            </span>
                        @endif
                    </h5>
                    <div class="small text-muted">
                        โดย <strong class="text-dark">{{ $contactMessage->name }}</strong>
                        · {{ $contactMessage->email }}
                        @if ($contactMessage->phone)
                            · {{ $contactMessage->phone }}
                        @endif
                        <div class="mt-1">
                            ส่งเมื่อ {{ $contactMessage->created_at?->format('d/m/Y H:i') }}
                            @if ($contactMessage->read_at)
                                · อ่านเมื่อ {{ $contactMessage->read_at?->format('d/m/Y H:i') }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="comment-list">

                        {{-- คำถามจากผู้ติดต่อ --}}
                        <div class="comment border rounded-3 mb-4 p-0">
                            <div class="comment-block p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge text-bg-primary">
                                        <i class="ti ti-user"></i> ผู้ติดต่อ
                                    </span>
                                    <span class="text-muted small">
                                        {{ $contactMessage->created_at?->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                <div class="lh-base">{!! nl2br(e($contactMessage->message)) !!}</div>
                                <div class="border-top mt-3 pt-2 small text-muted">
                                    📡 IP: {{ $contactMessage->ip ?? '-' }}<br>
                                    🖥️ User Agent: <code class="text-muted">{{ $contactMessage->user_agent ?? '-' }}</code>
                                </div>
                            </div>
                        </div>

                        {{-- คำตอบจากเจ้าหน้าที่ --}}
                        @if ($contactMessage->reply_message)
                            <div class="comment border border-primary rounded-3 mb-4 p-0 bg-white">
                                <div class="comment-block p-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-primary text-white">
                                            <i class="ti ti-message-check"></i> คำตอบจากเจ้าหน้าที่
                                        </span>
                                        <span class="text-muted small">
                                            @if ($contactMessage->replied_at)
                                                {{ $contactMessage->replied_at?->format('d/m/Y H:i') }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="lh-base">{!! nl2br(e($contactMessage->reply_message)) !!}</div>
                                    <div class="small text-muted mt-2">
                                        ตอบโดย {{ $contactMessage->handler?->name ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ฟอร์มตอบกลับ --}}
                        <div class="comment p-0">
                            <div class="comment-block border border-secondary shadow-sm rounded-3 p-4 bg-light">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="badge bg-secondary text-white px-3 py-2">
                                        <i class="ti ti-reply"></i> ตอบกลับผู้ติดต่อ
                                    </span>
                                    <span class="text-dark small fw-semibold">
                                        อีเมล: {{ $contactMessage->email }}
                                    </span>
                                </div>

                                @if (session('success'))
                                    <div class="alert alert-success py-2">{{ session('success') }}</div>
                                @endif

                                <form method="post" action="{{ route('backend.contact-messages.reply', $contactMessage) }}" class="d-flex flex-column">
                                    @csrf
                                    <textarea name="reply_message" class="form-control @error('reply_message') is-invalid @enderror" rows="4" placeholder="พิมพ์ข้อความตอบกลับ...">{{ old('reply_message', $contactMessage->reply_message) }}</textarea>
                                    @error('reply_message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="d-flex justify-content-end gap-2 pt-3 flex-wrap align-items-center">
                                        <a href="{{ route('backend.contact-messages.index') }}" class="btn btn-light border border-secondary">
                                            <i class="ti ti-arrow-left"></i> ย้อนกลับ
                                        </a>

                                        <button type="submit" name="_action" value="reply" class="btn btn-primary">
                                            <i class="ti ti-send"></i> ส่งคำตอบ
                                        </button>

                                        @if ($contactMessage->status !== 'done')
                                            <button type="button" class="btn btn-primary js-done-btn" data-title="{{ \Illuminate\Support\Str::limit($contactMessage->subject, 80) }}">
                                                <i class="ti ti-check"></i> ดำเนินการแล้ว
                                            </button>
                                        @endif
                                    </div>
                                    <input type="hidden" name="_action" value="reply" id="replyAction">
                                </form>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .comment-block .lh-base {
            word-break: break-word;
        }

        .bg-light {
            background-color: #f8f9fa !important;
        }
    </style>
@endpush

@section('scripts')
    <script>
        (function() {
            function confirmDone(title, cb) {
                Swal.fire({
                    icon: 'question',
                    title: 'ยืนยันทำเสร็จ?',
                    html: `ต้องการทำเครื่องหมายว่า <b>${title}</b> ดำเนินการเสร็จแล้วหรือไม่`,
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยัน',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true,
                    focusCancel: true
                }).then(res => {
                    if (res.isConfirmed) cb();
                });
            }

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.js-done-btn');
                if (!btn) return;
                const form = btn.closest('form');
                const title = btn.dataset.title || 'รายการนี้';
                confirmDone(title, () => {
                    form.querySelector('#replyAction').value = 'done';
                    form.submit();
                });
            });
        })();
    </script>
@endsection
