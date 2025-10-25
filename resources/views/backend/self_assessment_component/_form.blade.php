{{-- resources/views/backend/assessment/_form.blade.php --}}
@push('css')
    <style>
        .section-empty {
            background: var(--bs-light-bg-subtle);
            border: 1px dashed var(--bs-border-color);
            border-radius: .5rem;
            padding: .75rem 1rem;
            color: var(--bs-secondary-color);
        }

        .sticky-action {
            position: sticky;
            bottom: 0;
            background: var(--bs-body-bg);
            border-top: 1px solid var(--bs-border-color);
            padding: .75rem 1rem;
            text-align: right;
            z-index: 100;
        }
    </style>
@endpush

<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="ph-duotone ph-clipboard-text fs-4"></i>
        <h5 class="mb-0">ขั้นที่ 2 : ประเมินตนเองตามเกณฑ์องค์ประกอบการจัดหน่วยบริการทั้ง 6 ด้าน</h5>
    </div>

    <div class="card-body">

        <div class="border rounded p-2 mb-3 bg-body-tertiary d-flex flex-wrap align-items-center gap-3">
            {{-- หน่วยบริการ --}}
            <div class="d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-hospital fs-5"></i>
                <span class="text-muted">หน่วยบริการ</span>
                <span class="fw-semibold">{{ $summary['unit_name'] ?? '-' }}</span>
            </div>

            <div class="vr"></div>

            {{-- ระดับ --}}
            <div class="d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-medal fs-5"></i>
                <span class="text-muted">ระดับ</span>
                <x-level-badge :level="$summary['level'] ?? null" class="ms-1" />
            </div>


            <div class="vr"></div>

            {{-- ปีงบประมาณ --}}
            <div class="d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-calendar-blank fs-5"></i>
                <span class="text-muted">ปีงบประมาณ</span>
                <span class="fw-semibold">{{ $summary['fiscal_year_th'] ?? $summary['fiscal_year'] + 543 }}</span>
            </div>

            <div class="vr"></div>

            {{-- รอบ --}}
            <div class="d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-number-circle-one fs-5"></i>
                <span class="text-muted">รอบ</span>
                <span class="fw-semibold">{{ $summary['round'] ?? '-' }}</span>
            </div>
        </div>


        <div class="alert alert-primary d-flex align-items-center mb-4 shadow-sm border-0 rounded-3">
            <i class="ph-duotone ph-clipboard-text fs-4 me-3"></i>
            <div>
                โปรดตอบแบบประเมินให้ครบทุกข้อ
                <br class="d-sm-none">
                เมื่อทำครบทั้งหมดแล้ว จึงจะสามารถกด <span class="fw-semibold">“ส่งแบบประเมินให้ สคร./สสจ. พิจารณา”</span> ได้
            </div>
        </div>



        <div class="accordion" id="accComp">
            @foreach ($components as $comp)
                @php
                    $sections = $sectionsByComp[$comp->id] ?? collect();
                    $qsAll = $sections->flatMap(fn($sec) => $questionsBySection[$sec->id] ?? collect());
                    $total = $qsAll->count();
                    $answered = $qsAll
                        ->filter(function ($q) use ($answerMap) {
                            $a = $answerMap[$q->id] ?? null;
                            return !is_null($a?->answer_bool) || filled($a?->answer_text);
                        })
                        ->count();
                    $percent = $total > 0 ? round(($answered / $total) * 100) : 0;
                @endphp

                <div class="accordion-item">
                    <h2 class="accordion-header" id="h{{ $comp->id }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c{{ $comp->id }}" aria-expanded="false">
                            <div class="d-flex align-items-center w-100">
                                <span>องค์ประกอบที่ {{ $comp->no }}. {{ $comp->name }}</span>
                                <div class="ms-auto d-flex align-items-center gap-2 text-nowrap me-2">
                                    <span class="text-muted small">ทำแล้ว {{ $answered }}/{{ $total }}</span>
                                    <span class="badge {{ $percent == 100 ? 'bg-success' : 'bg-primary' }}">{{ $percent }}%</span>
                                    <div class="progress" style="height:4px; width:100px;">
                                        <div class="progress-bar {{ $percent == 100 ? 'bg-success' : 'bg-primary' }}" style="width:{{ $percent }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </h2>

                    <div id="c{{ $comp->id }}" class="accordion-collapse collapse" data-bs-parent="#accComp">
                        <div class="accordion-body">
                            @foreach ($sections as $sec)
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
                                                @php
                                                    $current = old("answers.$q->id.bool", is_null($ans?->answer_bool) ? null : ($ans->answer_bool ? '1' : '0'));
                                                @endphp
                                                <div class="d-flex gap-3">
                                                    <label class="form-check">
                                                        <input type="radio" class="form-check-input" name="answers[{{ $q->id }}][bool]" value="1" @checked($current === '1')>
                                                        <span class="form-check-label">มี</span>
                                                    </label>
                                                    <label class="form-check">
                                                        <input type="radio" class="form-check-input" name="answers[{{ $q->id }}][bool]" value="0" @checked($current === '0')>
                                                        <span class="form-check-label">ไม่มี</span>
                                                    </label>
                                                </div>
                                            @elseif ($q->answer_type === 'single')
                                                @php
                                                    $opts = is_array($q->options) ? $q->options : (json_decode($q->options, true) ?: []);
                                                    $current = old("answers.$q->id.text", $ans?->answer_text);
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

        <div class="card mt-3">
            <div class="card-header py-2">
                <h6 class="mb-0">ข้อเสนอเพื่อการพัฒนา / แผนยกระดับหน่วยบริการ</h6>
            </div>
            <div class="card-body">
                <div id="suggestionList" class="d-flex flex-column gap-3">
                    @php
                        $existing = isset($form)
                            ? $form->suggestions
                                ->map(
                                    fn($s) => [
                                        'id' => $s->id,
                                        'text' => $s->text,
                                        'attachment_path' => $s->attachment_path,
                                    ],
                                )
                                ->toArray()
                            : (isset($suggestions)
                                ? $suggestions->toArray()
                                : []);
                        $rows = old('suggestions', count($existing) ? $existing : [[]]);
                    @endphp

                    @forelse ($rows as $i => $row)
                        <div class="border rounded p-2 suggestion-item">
                            @isset($row['id'])
                                <input type="hidden" name="suggestions[{{ $i }}][id]" value="{{ $row['id'] }}">
                            @endisset

                            <div class="mb-2">
                                <label class="form-label mb-1">ข้อเสนอ/แผน (พิมพ์เป็นข้อ)</label>
                                <textarea name="suggestions[{{ $i }}][text]" class="form-control" rows="2">{{ old("suggestions.$i.text", $row['text'] ?? '') }}</textarea>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-grow-1">
                                    <input type="file" name="suggestions[{{ $i }}][file]" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                    @if (!empty($row['attachment_path']))
                                        <div class="form-text">
                                            ไฟล์ปัจจุบัน:
                                            <a href="{{ Storage::url($row['attachment_path']) }}" target="_blank">เปิดไฟล์</a>
                                        </div>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-remove-suggestion">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="border rounded p-2 suggestion-item">
                            <div class="mb-2">
                                <label class="form-label mb-1">ข้อเสนอ/แผน (พิมพ์เป็นข้อ)</label>
                                <textarea name="suggestions[0][text]" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-grow-1">
                                    <input type="file" name="suggestions[0][file]" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-remove-suggestion">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="text-end mt-3">
                    <button type="button" id="btnAddSuggestion" class="btn btn-outline-primary">
                        <i class="ti ti-plus"></i> เพิ่มข้อเสนอ
                    </button>
                </div>
            </div>
        </div>

        {{-- ปรับปุ่มด้านล่าง --}}
        <div class="sticky-action d-flex justify-content-between align-items-center flex-wrap gap-2">
            {{-- ปุ่มฝั่งซ้าย --}}
            <a href="{{ route('backend.self-assessment-service-unit-level.edit', $suLevel->id) }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left"></i> กลับขั้นตอนที่ 1 พิจารณาสถานะหน่วยบริการ
            </a>



            {{-- ปุ่มฝั่งขวา --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary" onclick="document.getElementById('__action').value='save'">
                    <i class="ti ti-device-floppy"></i> บันทึกแบบร่าง
                </button>

                <button type="submit" class="btn btn-primary" onclick="if(confirm('ยืนยันการส่งแบบประเมินให้ สคร./สสจ. ใช่หรือไม่')){document.getElementById('__action').value='submit'}else{return false;}">
                    <i class="ti ti-send"></i> ส่งแบบประเมินให้ สคร./สสจ.
                </button>
            </div>
        </div>

    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const list = document.getElementById('suggestionList');
            const addBtn = document.getElementById('btnAddSuggestion');

            const nextIndex = () => list.querySelectorAll('.suggestion-item').length;

            const makeItem = (idx) => {
                const wrap = document.createElement('div');
                wrap.className = 'border rounded p-2 suggestion-item';
                wrap.innerHTML = `
            <div class="mb-2">
                <label class="form-label mb-1">ข้อเสนอ/แผน (พิมพ์เป็นข้อ)</label>
                <textarea name="suggestions[${idx}][text]" class="form-control" rows="2" required></textarea>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="flex-grow-1">
                    <input type="file" name="suggestions[${idx}][file]" class="form-control"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                </div>
                <button type="button" class="btn btn-outline-danger btn-remove-suggestion">
                    <i class="ti ti-trash"></i>
                </button>
            </div>`;
                return wrap;
            };

            addBtn?.addEventListener('click', () => {
                list.appendChild(makeItem(nextIndex()));
            });

            list.addEventListener('click', (e) => {
                if (e.target.closest('.btn-remove-suggestion')) {
                    const items = list.querySelectorAll('.suggestion-item');
                    if (items.length > 1) e.target.closest('.suggestion-item').remove();
                }
            });
        });
    </script>
@endpush
