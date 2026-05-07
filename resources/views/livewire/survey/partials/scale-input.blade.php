@php
    use App\Enums\QuestionType;
@endphp

@if ($type === QuestionType::Scale1to5->value)
    <div class="mt-2 flex flex-wrap gap-2">
        @foreach ([1, 2, 3, 4, 5] as $value)
            <label class="inline-flex cursor-pointer">
                <input
                    type="radio"
                    wire:model.live="{{ $wireModel }}"
                    value="{{ $value }}"
                    class="peer sr-only"
                />
                <span
                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 shadow-sm transition-colors peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:shadow-md hover:bg-indigo-50 peer-checked:hover:bg-indigo-700"
                >
                    {{ $value }}
                </span>
            </label>
        @endforeach
        <label class="inline-flex cursor-pointer">
            <input
                type="radio"
                wire:model.live="{{ $wireModel }}"
                value="nsa"
                class="peer sr-only"
            />
            <span
                class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-600 shadow-sm transition-colors peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:shadow-md hover:bg-indigo-50 peer-checked:hover:bg-indigo-700"
            >
                {{ __('survey.public.form.nsa_option') }}
            </span>
        </label>
    </div>
@elseif ($type === QuestionType::Scale0to10->value)
    <div class="mt-2 flex flex-wrap gap-2">
        @foreach (range(0, 10) as $value)
            <label class="inline-flex cursor-pointer">
                <input
                    type="radio"
                    wire:model.live="{{ $wireModel }}"
                    value="{{ $value }}"
                    class="peer sr-only"
                />
                <span
                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 shadow-sm transition-colors peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:shadow-md hover:bg-indigo-50 peer-checked:hover:bg-indigo-700"
                >
                    {{ $value }}
                </span>
            </label>
        @endforeach
    </div>
@elseif ($type === QuestionType::FreeText->value)
    <textarea
        wire:model.live="{{ $wireModel }}"
        rows="3"
        class="mt-2 block w-full rounded-md border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
        placeholder="{{ __('survey.public.form.free_text_placeholder') }}"
    ></textarea>
@endif
