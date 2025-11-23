<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceDetailUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['clock_in'])) {
            $data['clock_in'] = $this->normalizeTime($data['clock_in']);
        }

        if (isset($data['clock_out'])) {
            $data['clock_out'] = $this->normalizeTime($data['clock_out']);
        }

        if (!empty($data['breakRecords']) && is_array($data['breakRecords'])) {
            foreach ($data['breakRecords'] as $index => $break) {
                if (isset($break['start'])) {
                    $data['breakRecords'][$index]['start'] = $this->normalizeTime($break['start']);
                }
                if (isset($break['end'])) {
                    $data['breakRecords'][$index]['end'] = $this->normalizeTime($break['end']);
                }
            }
        }

        $this->replace($data);
    }

    private function normalizeTime($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '';
        }

        $normalized = mb_convert_kana($normalized, 'na', 'UTF-8');
        $normalized = str_replace('：', ':', $normalized);

        return $normalized;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 出勤・退勤
            'clock_in'  => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],

            // 休憩（配列想定）
            'breakRecords'         => ['array'],
            'breakRecords.*.start' => ['nullable', 'date_format:H:i', 'regex:/^\d{2}:\d{2}$/'],
            'breakRecords.*.end'   => ['nullable', 'date_format:H:i', 'regex:/^\d{2}:\d{2}$/'],

            // 備考
            'remarks' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        $timeFormatMessage = '時間は「HH:MM」形式の半角で入力してください';

        return [
            'clock_in.required'  => '出勤時間を入力してください',
            'clock_out.required' => '退勤時間を入力してください',

            'clock_in.date_format'     => $timeFormatMessage,
            'clock_out.date_format'    => $timeFormatMessage,
            'breakRecords.*.start.date_format' => $timeFormatMessage,
            'breakRecords.*.end.date_format'   => $timeFormatMessage,
            'breakRecords.*.start.regex'       => $timeFormatMessage,
            'breakRecords.*.end.regex'         => $timeFormatMessage,

            'remarks.required' => '備考を記入してください',
            'remarks.max'      => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $clockIn  = $this->input('clock_in');   // "HH:MM"
            $clockOut = $this->input('clock_out');  // "HH:MM"

            // 1. 出勤 > 退勤 / 退勤 < 出勤 の場合（どちらも同じ条件）
            if ($clockIn !== null && $clockOut !== null && $clockIn >= $clockOut) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                $validator->errors()->add('clIntelephense: ock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $breakRecords = $this->input('breakRecords', []);

            foreach ($breakRecords as $index => $breakRecord) {
                $start = $breakRecord['start'] ?? null; // 休憩開始
                $end   = $breakRecord['end'] ?? null;   // 休憩終了

                $start = $start === '' ? null : $start;
                $end   = $end === '' ? null : $end;

                $isRequired = !empty($breakRecord['required']);

                // どちらか一方だけ入力されている場合もエラー
                if (($start !== null && $end === null) || ($start === null && $end !== null)) {
                    $validator->errors()->add(
                        "breakRecords.$index.start",
                        '休憩時間が不適切な値です'
                    );
                    $validator->errors()->add(
                        "breakRecords.$index.end",
                        '休憩時間が不適切な値です'
                    );
                    continue;
                }

                if ($isRequired && ($start === null || $end === null)) {
                    $validator->errors()->add(
                        "breakRecords.$index.start",
                        '休憩時間が不適切な値です'
                    );
                    $validator->errors()->add(
                        "breakRecords.$index.end",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                    continue;
                }

                // 何も入ってなければスキップ
                if ($start === null && $end === null) {
                    continue;
                }

                // 2. 休憩開始 < 出勤  または  休憩開始 > 退勤
                if ($start !== null) {
                    if (
                        ($clockIn !== null && $start < $clockIn) ||
                        ($clockOut !== null && $start > $clockOut)
                    ) {
                        $validator->errors()->add(
                            "breakRecords.$index.start",
                            '休憩時間が不適切な値です'
                        );
                    }
                }

                // 3. 休憩終了 が 休憩開始より前 または 退勤より後
                if ($end !== null) {
                    if (
                        ($start !== null && $end < $start) ||
                        ($clockOut !== null && $end > $clockOut)
                    ) {
                        $validator->errors()->add(
                            "breakRecords.$index.end",
                            '休憩時間もしくは退勤時間が不適切な値です'
                        );
                    }
                }
            }
        });
    }
}
