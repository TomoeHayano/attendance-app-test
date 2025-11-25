<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * 管理者用：勤怠詳細更新リクエスト
 *
 * @property-read string|null $clock_in
 * @property-read string|null $clock_out
 * @property-read array|null  $breakRecords
 * @property-read string|null $remarks
 */
class AdminDetailUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        /** @var array<string, mixed> $data */
        $data = $this->all();

        if (isset($data['clock_in'])) {
            $data['clock_in'] = $this->normalizeTime($data['clock_in']);
        }

        if (isset($data['clock_out'])) {
            $data['clock_out'] = $this->normalizeTime($data['clock_out']);
        }

        if (! empty($data['breakRecords']) && is_array($data['breakRecords'])) {
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

    /**
     * @param  mixed $value
     * @return string|null
     */
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

    public function authorize(): bool
    {

        return auth('admin')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [

            'clock_in'  => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],

            'breakRecords'         => ['array'],
            'breakRecords.*.start' => ['nullable', 'date_format:H:i', 'regex:/^\d{2}:\d{2}$/'],
            'breakRecords.*.end'   => ['nullable', 'date_format:H:i', 'regex:/^\d{2}:\d{2}$/'],

            'remarks' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $timeFormatMessage = '時間は「HH:MM」形式の半角で入力してください';

        return [
            'clock_in.required'  => '出勤時間を入力してください',
            'clock_out.required' => '退勤時間を入力してください',

            'clock_in.date_format'                => $timeFormatMessage,
            'clock_out.date_format'               => $timeFormatMessage,
            'breakRecords.*.start.date_format'    => $timeFormatMessage,
            'breakRecords.*.end.date_format'      => $timeFormatMessage,
            'breakRecords.*.start.regex'          => $timeFormatMessage,
            'breakRecords.*.end.regex'            => $timeFormatMessage,

            'remarks.required' => '備考を記入してください',
            'remarks.max'      => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $clockIn  = $this->input('clock_in');   // "HH:MM"
            $clockOut = $this->input('clock_out');  // "HH:MM"

            if ($clockIn !== null && $clockOut !== null && $clockIn >= $clockOut) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }

            /** @var array<int, array{start?: string|null, end?: string|null, required?: mixed}> $breakRecords */
            $breakRecords = $this->input('breakRecords', []);

            foreach ($breakRecords as $index => $breakRecord) {
                $start = $breakRecord['start'] ?? null;
                $end   = $breakRecord['end'] ?? null;

                $start = $start === '' ? null : $start;
                $end   = $end === '' ? null : $end;

                $isRequired = ! empty($breakRecord['required']);

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

                if ($start === null && $end === null) {
                    continue;
                }

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

            //休憩2以降
            $completeBreaks = [];
            foreach ($breakRecords as $index => $breakRecord) {
                $start = $breakRecord['start'] ?? null;
                $end   = $breakRecord['end'] ?? null;

                if ($start === null || $end === null || $start === '' || $end === '') {
                    continue;
                }

                $completeBreaks[] = [
                    'start' => $start,
                    'end'   => $end,
                    'index' => $index,
                ];
            }

            $prevEnd    = null;
            $seenBreaks = [];

            foreach ($completeBreaks as $break) {
                $idx   = $break['index'];
                $start = $break['start'];
                $end   = $break['end'];

                if ($prevEnd !== null && $start < $prevEnd) {
                    $validator->errors()->add("breakRecords.$idx.start", '休憩時間が不適切な値です');
                    $validator->errors()->add("breakRecords.$idx.end", '休憩時間が不適切な値です');
                }

                foreach ($seenBreaks as $prev) {
                    $overlap = ($start < $prev['end']) && ($end > $prev['start']);
                    if ($overlap) {
                        $validator->errors()->add("breakRecords.$idx.start", '休憩時間が重複しています');
                        $validator->errors()->add("breakRecords.$idx.end", '休憩時間が重複しています');
                        break;
                    }
                }

                $seenBreaks[] = $break;
                $prevEnd      = $end;
            }
        });
    }
}
