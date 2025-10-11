<?php
// app/Http/Requests/ServiceUnitManagersRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceUnitManagersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update service units') || true;
    }

    public function rules(): array
    {
        return [
            'managers'              => ['array'],
            'managers.*.user_id'    => ['required', 'integer', 'exists:users,id'],
            'managers.*.role'       => ['nullable', 'in:owner,manager,editor,viewer'],
            'managers.*.is_primary' => ['boolean'],
            'managers.*.start_date' => ['nullable', 'date'],
            'managers.*.end_date'   => ['nullable', 'date', 'after_or_equal:managers.*.start_date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $items = collect($this->input('managers', []))->filter(fn($x) => !empty($x['user_id']));
            if ($items->isEmpty()) {
                $v->errors()->add('managers', 'กรุณาเลือกผู้รับผิดชอบอย่างน้อย 1 คน');
                return;
            }
            $primaryCount = $items->where('is_primary', true)->count();
            if ($primaryCount !== 1) {
                $v->errors()->add('managers', 'ต้องกำหนดผู้รับผิดชอบหลักจำนวน 1 คนพอดี');
            }
            // ห้ามซ้ำ user เดิม
            if ($items->pluck('user_id')->duplicates()->isNotEmpty()) {
                $v->errors()->add('managers', 'มีรายชื่อผู้ใช้ซ้ำกัน');
            }
        });
    }
}
