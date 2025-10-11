{{-- resources\views\partials\geo-select-th.blade.php --}}
@php
    $provName = $namePrefix . 'province_code';
    $distName = $namePrefix . 'district_code';
    $subdName = $namePrefix . 'subdistrict_code';
    $postName = $namePrefix . 'postcode';

    $provVal = old($provName, $init['province_code'] ?? '');
    $distVal = old($distName, $init['district_code'] ?? '');
    $subdVal = old($subdName, $init['subdistrict_code'] ?? '');
    $postVal = old($postName, $init['postcode'] ?? '');

    $uid = 'geo_' . substr(md5($provName . $distName . $subdName . $postName . microtime(true)), 0, 6);
@endphp

<div class="row g-3 mt-2" id="{{ $uid }}">
    <div class="col-md-3">
        <label class="form-label" for="{{ $uid }}_province">{{ $labels['province'] }}</label>
        <select class="form-select" id="{{ $uid }}_province" name="{{ $provName }}" data-init="{{ $provVal }}">
            <option value="">— เลือกจังหวัด —</option>
        </select>
        @error($provName)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $uid }}_district">{{ $labels['district'] }}</label>
        <select class="form-select" id="{{ $uid }}_district" name="{{ $distName }}" data-init="{{ $distVal }}" {{ $provVal ? '' : 'disabled' }}>
            <option value="">— เลือกอำเภอ/เขต —</option>
        </select>
        @error($distName)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $uid }}_subdistrict">{{ $labels['subdistrict'] }}</label>
        <select class="form-select" id="{{ $uid }}_subdistrict" name="{{ $subdName }}" data-init="{{ $subdVal }}" {{ $distVal ? '' : 'disabled' }}>
            <option value="">— เลือกตำบล/แขวง —</option>
        </select>
        @error($subdName)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $uid }}_postcode">รหัสไปรษณีย์</label>
        <input type="text" class="form-control" id="{{ $uid }}_postcode" name="{{ $postName }}" data-init="{{ $postVal }}" placeholder="เช่น 10400" inputmode="numeric" maxlength="5" pattern="\d{5}" />
        @error($postName)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>

@pushOnce('js')
    <script>
        (function() {
            const R_PROV = `{{ route('geo.provinces') }}`;
            const R_DIST = `{{ route('geo.districts') }}`;
            const R_SUBD = `{{ route('geo.subdistricts') }}`;
            const R_POST = `{{ route('geo.postcodes') }}`;

            async function fetchJson(url) {
                try {
                    const r = await fetch(url, {
                        credentials: 'same-origin'
                    });
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return await r.json();
                } catch (e) {
                    console.error('[geo-select] fetch failed:', url, e);
                    return [];
                }
            }

            function fillSelect(sel, list, placeholder) {
                if (!sel) return; // guard
                sel.innerHTML = '';
                const opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = placeholder;
                sel.appendChild(opt0);
                list.forEach(i => {
                    const o = document.createElement('option');
                    o.value = i.code;
                    o.textContent = i.title;
                    sel.appendChild(o);
                });
            }

            async function initChain(rootId) {
                const root = document.getElementById(rootId);
                if (!root) return;
                if (root.dataset.inited === '1') return;
                root.dataset.inited = '1';

                const selP = root.querySelector('select[id$="_province"]');
                const selD = root.querySelector('select[id$="_district"]');
                const selS = root.querySelector('select[id$="_subdistrict"]');
                const inpZ = root.querySelector('input[id$="_postcode"]');
                if (!selP || !selD || !selS || !inpZ) return;

                const clearZip = () => {
                    inpZ.value = '';
                };

                // provinces
                const provinces = await fetchJson(R_PROV);
                fillSelect(selP, provinces, '— เลือกจังหวัด —');
                if (selP.dataset.init) selP.value = selP.dataset.init;

                selP.addEventListener('change', async () => {
                    selD.disabled = true;
                    selS.disabled = true;
                    fillSelect(selD, [], '— เลือกอำเภอ/เขต —');
                    fillSelect(selS, [], '— เลือกตำบล/แขวง —');
                    clearZip();

                    const pv = selP.value;
                    if (!pv) return;
                    const dists = await fetchJson(`${R_DIST}?province=${encodeURIComponent(pv)}`);
                    fillSelect(selD, dists, '— เลือกอำเภอ/เขต —');
                    selD.disabled = false;
                });

                selD.addEventListener('change', async () => {
                    selS.disabled = true;
                    fillSelect(selS, [], '— เลือกตำบล/แขวง —');
                    clearZip();

                    const dv = selD.value;
                    if (!dv) return;
                    const subs = await fetchJson(`${R_SUBD}?district=${encodeURIComponent(dv)}`);
                    fillSelect(selS, subs, '— เลือกตำบล/แขวง —');
                    selS.disabled = false;
                });

                selS.addEventListener('change', async () => {
                    clearZip();
                    const sv = selS.value;
                    if (!sv) return;
                    const zips = await fetchJson(`${R_POST}?subdistrict=${encodeURIComponent(sv)}`);
                    if (Array.isArray(zips) && zips.length) inpZ.value = String(zips[0].code || '');
                });

                // Prefill chain
                if (selP.value) {
                    const dists = await fetchJson(`${R_DIST}?province=${encodeURIComponent(selP.value)}`);
                    fillSelect(selD, dists, '— เลือกอำเภอ/เขต —');
                    selD.disabled = false;
                    if (selD.dataset.init) selD.value = selD.dataset.init;
                }
                if (selD.value) {
                    const subs = await fetchJson(`${R_SUBD}?district=${encodeURIComponent(selD.value)}`);
                    fillSelect(selS, subs, '— เลือกตำบล/แขวง —');
                    selS.disabled = false;
                    if (selS.dataset.init) selS.value = selS.dataset.init;
                }
                if (selS.value) {
                    const zips = await fetchJson(`${R_POST}?subdistrict=${encodeURIComponent(selS.value)}`);
                    if (Array.isArray(zips) && zips.length) inpZ.value = String(zips[0].code || '');
                }
                if (inpZ.dataset.init && !inpZ.value) inpZ.value = inpZ.dataset.init;
            }

            function boot() {
                document.querySelectorAll('[id^="geo_"]').forEach(el => initChain(el.id));
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })
        ();
    </script>
@endPushOnce
