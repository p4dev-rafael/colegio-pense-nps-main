@php
    use App\Enums\SectionType;
@endphp

<div class="space-y-6" wire:key="public-survey-{{ $token }}">
    @if ($submitted)
        <div class="space-y-3 text-center">
            <h2 class="text-2xl font-semibold text-emerald-700">
                {{ __('survey.public.thanks.heading') }}
            </h2>
            <p class="text-gray-600">{{ __('survey.public.thanks.description') }}</p>
        </div>
    @elseif ($batch === null || (! $batch->isAcceptingResponses() && ! $submitted))
        <div class="rounded-md border border-amber-200 bg-amber-50 p-6 text-amber-800">
            <h2 class="text-lg font-semibold">{{ __('survey.public.closed_title') }}</h2>
            <p class="mt-2 text-sm">{{ __('survey.public.closed_description') }}</p>
        </div>
    @elseif (! $identified)
        <form wire:submit="identify" class="space-y-5">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ __('survey.public.identification.heading') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    @if ($batch->requires_identification)
                        {{ __('survey.public.identification.description') }}
                    @else
                        {{ __('survey.public.identification.description_optional') }}
                    @endif
                </p>
            </div>

            <div>
                <label for="registrationCode" class="block text-sm font-medium text-gray-700">
                    {{ __('survey.public.identification.registration_code') }}
                    @if (! $batch->requires_identification)
                        <span class="font-normal text-gray-500">({{ __('survey.public.identification.optional_hint') }})</span>
                    @endif
                </label>
                <input
                    type="text"
                    id="registrationCode"
                    wire:model="registrationCode"
                    autocomplete="off"
                    class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                    @if ($batch->requires_identification) required @endif
                />
                @if ($identificationError)
                    <p class="mt-2 text-sm text-red-600">{{ $identificationError }}</p>
                @endif
            </div>

            <button
                type="submit"
                class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-auto"
            >
                {{ __('survey.public.identification.continue') }}
            </button>
        </form>
    @else
        <form wire:submit="submit" class="space-y-8">
            <div class="rounded-md bg-indigo-50 p-4 text-sm text-indigo-900">
                <div class="grid grid-cols-1 gap-1 sm:grid-cols-3">
                    @if ($respondentType === \App\Enums\RespondentType::Anonymous->value)
                        <div class="sm:col-span-3">
                            <span class="font-semibold">{{ __('survey.public.form.respondent_label') }}:</span>
                            {{ $respondentName }}
                        </div>
                        <div>
                            <span class="font-semibold">{{ __('survey.public.form.segment_label') }}:</span>
                            {{ $segmentName }}
                        </div>
                        <div class="sm:col-span-2">
                            <span class="font-semibold">{{ __('survey.public.form.unit_label') }}:</span>
                            {{ $unitName }}
                        </div>
                    @else
                        <div>
                            <span class="font-semibold">
                                @if ($respondentType === \App\Enums\RespondentType::Guardian->value)
                                    {{ __('survey.public.form.guardian_label') }}
                                @else
                                    {{ __('survey.public.form.student_label') }}
                                @endif
                                :
                            </span>
                            {{ $respondentName }}
                        </div>
                        <div>
                            <span class="font-semibold">{{ __('survey.public.form.segment_label') }}:</span>
                            {{ $segmentName }}
                        </div>
                        <div>
                            <span class="font-semibold">{{ __('survey.public.form.unit_label') }}:</span>
                            {{ $unitName }}
                        </div>
                    @endif
                </div>
            </div>

            @foreach ($sectionsView as $section)
                <section class="space-y-4 border-t border-gray-200 pt-6 first:border-t-0 first:pt-0">
                    <header>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $section['title'] }}</h3>
                        @if (! empty($section['description']))
                            <p class="mt-1 text-sm text-gray-600">{{ $section['description'] }}</p>
                        @endif
                    </header>

                    @if ($section['type'] === SectionType::Teachers->value && $batch->requires_identification)
                        @if (count($teacherSlots) === 0)
                            <p class="text-sm text-gray-500">—</p>
                        @endif

                        @foreach ($teacherSlots as $slot)
                            <div class="rounded-md bg-gray-50 p-4 ring-1 ring-gray-200">
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $slot['teacher_name'] }}
                                    @if ($slot['subject_name'])
                                        <span class="font-normal text-gray-500">
                                            · {{ __('survey.public.form.teacher_subject') }}: {{ $slot['subject_name'] }}
                                        </span>
                                    @endif
                                </p>

                                <div class="mt-3 space-y-3">
                                    @foreach ($section['questions'] as $question)
                                        <div>
                                            <label class="block text-sm text-gray-800">
                                                {{ $question['text'] }}
                                                @if ($question['is_required'])
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </label>
                                            @include('livewire.survey.partials.scale-input', [
                                                'wireModel' => "teacherAnswers.{$slot['segment_teacher_id']}.{$question['code']}",
                                                'type' => $question['type'],
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="space-y-3">
                            @foreach ($section['questions'] as $question)
                                <div>
                                    <label class="block text-sm text-gray-800">
                                        {{ $question['text'] }}
                                        @if ($question['is_required'])
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    @include('livewire.survey.partials.scale-input', [
                                        'wireModel' => "sectionAnswers.{$section['key']}.{$question['code']}",
                                        'type' => $question['type'],
                                    ])
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach

            @if ($submitError)
                <p class="text-sm text-red-600">{{ $submitError }}</p>
            @endif

            <button
                type="submit"
                class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-auto"
                wire:loading.attr="disabled"
            >
                {{ __('survey.public.form.submit') }}
            </button>
        </form>
    @endif
</div>
