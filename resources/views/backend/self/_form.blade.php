{{-- expect: $components, $sectionsByComp, $questionsBySection, $answerMap --}}
@push('css')
    <style>
        .section-empty {
            background: var(--bs-light-bg-subtle);
            border: 1px dashed var(--bs-border-color);
            border-radius: .5rem;
            padding: .75rem 1rem;
            color: var(--bs-secondary-color)
        }
    </style>
@endpush

<div class="accordion" id="accComp">
    @foreach ($components as $comp)
        <div class="accordion-item">
            <h2 class="accordion-header" id="h{{ $comp->id }}">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c{{ $comp->id }}">
                    องค์ประกอบที่ {{ $comp->no }}. {{ $comp->name }}
                </button>
            </h2>
            <div id="c{{ $comp->id }}" class="accordion-collapse collapse" data-bs-parent="#accComp">
                <div class="accordion-body">
                    @foreach ($sectionsByComp[$comp->id] ?? collect() as $sec)
                        @php $qs = $questionsBySection[$sec->id] ?? collect(); @endphp
                        <div class="mb-3">
                            <div class="fw-semibold mb-1 {{ $qs->isEmpty() ? 'text-secondary' : '' }}">
                                {{ $sec->code ? $sec->code . ') ' : '' }}{{ $sec->title }}
                                @if ($qs->isEmpty())
                                    <span class="badge bg-secondary ms-2">ไม่มีคำถาม</span>
                                @endif
                            </div>
                            @if ($sec->subtitle)
                                <div class="text-muted mb-2">{{ $sec->subtitle }}</div>
                            @endif

                            @forelse ($qs as $q)
                                @php $ans = $answerMap[$q->id] ?? null; @endphp
                                <div class="mb-2 ms-3">
                                    <label class="form-label">{{ $q->code ? $q->code . '. ' : '' }}{{ $q->text }}</label>
                                    @if ($q->answer_type === 'boolean')
                                        <div class="d-flex gap-3">
                                            <label class="form-check">
                                                <input type="radio" class="form-check-input" name="answers[{{ $q->id }}][bool]" value="1" @checked(optional($ans)->answer_bool === true)>
                                                <span class="form-check-label">มี</span>
                                            </label>
                                            <label class="form-check">
                                                <input type="radio" class="form-check-input" name="answers[{{ $q->id }}][bool]" value="0" @checked(optional($ans)->answer_bool === false)>
                                                <span class="form-check-label">ไม่มี</span>
                                            </label>
                                        </div>
                                    @elseif ($q->answer_type === 'single')
                                        @php
                                            $opts = is_array($q->options) ? $q->options : (json_decode($q->options, true) ?: []);
                                            $current = old("answers.$q->id.text", optional($ans)->answer_text);
                                        @endphp
                                        @foreach ($opts as $i => $opt)
                                            @php $oid = "q{$q->id}_{$i}"; @endphp
                                            <div class="form-check">
                                                <input id="{{ $oid }}" type="radio" class="form-check-input" name="answers[{{ $q->id }}][text]" value="{{ $opt }}" @checked($current === $opt)>
                                                <label for="{{ $oid }}" class="form-check-label">{{ $opt }}</label>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            @empty
                                <div class="section-empty d-flex align-items-center gap-2 ms-3">
                                    <i class="ti ti-circle-off"></i>
                                    <span>หัวข้อนี้ไม่มีคำถามในระดับนี้</span>
                                </div>
                            @endforelse
                        </div>
                        <hr class="my-3">
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ข้อเสนอ + ปุ่ม บันทึก/ยกเลิก คงเดิมด้านล่าง --}}
