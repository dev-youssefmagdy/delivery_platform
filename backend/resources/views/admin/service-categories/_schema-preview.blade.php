{{-- Client-side preview of the draft form. $draft: ServiceFormSchema --}}
@php
    $p = static fn (string $key): string => __('admin.service_categories.preview.'.$key);
@endphp
<div x-data="schemaPreview(@js($draft->fields), @js(app()->getLocale()))" class="space-y-5">
    <p class="text-sm text-slate-600">{{ $p('hint') }}</p>

    <div class="flex items-center gap-3">
        <label for="schema-preview-locale" class="form-label !mb-0">{{ $p('as_locale') }}</label>
        <select id="schema-preview-locale" class="form-control max-w-40" x-model="locale">
            @foreach (\App\Enums\Common\Locale::cases() as $locale)
                <option value="{{ $locale->value }}">{{ $locale->label() }}</option>
            @endforeach
        </select>
    </div>

    <template x-if="fields.length === 0">
        <x-admin.alert type="info">{{ $p('empty') }}</x-admin.alert>
    </template>

    <div class="mx-auto max-w-xl space-y-4 rounded-(--radius-card) border border-slate-200 bg-slate-50 p-5" x-bind:dir="locale === 'ar' ? 'rtl' : 'ltr'" x-bind:lang="locale">
        <template x-for="field in fields" x-bind:key="field.key">
            <div x-show="isVisible(field)" data-preview-field x-bind:data-field-key="field.key" x-bind:data-field-type="field.type">
                <label class="form-label">
                    <span x-text="text(field.label)"></span>
                    <span x-show="field.required" class="form-required" aria-hidden="true">*</span>
                </label>

                <template x-if="['text', 'integer', 'decimal', 'weight'].includes(field.type)">
                    <div class="flex items-center gap-2">
                        <input class="form-control" x-bind:type="field.type === 'text' ? 'text' : 'number'" step="any" x-model="answers[field.key]">
                        <span x-show="field.type === 'weight'" class="text-sm text-slate-500">kg</span>
                    </div>
                </template>
                <template x-if="field.type === 'textarea'">
                    <textarea class="form-control" rows="3" x-model="answers[field.key]"></textarea>
                </template>
                <template x-if="field.type === 'boolean'">
                    <div class="flex gap-4 text-sm text-slate-700">
                        <label class="inline-flex items-center gap-2"><input type="radio" class="form-check rounded-full" x-bind:name="'p-' + field.key" value="1" x-on:change="answers[field.key] = true"> {{ $p('yes') }}</label>
                        <label class="inline-flex items-center gap-2"><input type="radio" class="form-check rounded-full" x-bind:name="'p-' + field.key" value="0" x-on:change="answers[field.key] = false"> {{ $p('no') }}</label>
                    </div>
                </template>
                <template x-if="field.type === 'select'">
                    <select class="form-control" x-model="answers[field.key]">
                        <option value="">{{ $p('choose') }}</option>
                        <template x-for="option in field.options" x-bind:key="option.value"><option x-bind:value="option.value" x-text="text(option.label)"></option></template>
                    </select>
                </template>
                <template x-if="field.type === 'multiselect'">
                    <div class="space-y-1">
                        <template x-for="option in field.options" x-bind:key="option.value">
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" class="form-check" x-on:change="toggleOption(field, option.value)"> <span x-text="text(option.label)"></span></label>
                        </template>
                    </div>
                </template>
                <template x-if="field.type === 'item_checklist'">
                    {{-- v2: each ticked item takes a quantity and optional note, weight and dimensions; free-text lines follow. --}}
                    <div class="space-y-2">
                        <template x-for="option in field.options" x-bind:key="option.value">
                            <div x-data="{ on: false }" class="rounded-(--radius-control) border border-slate-200 bg-white px-3 py-2">
                                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" class="form-check" x-model="on" x-on:change="toggleOption(field, option.value)"> <span x-text="text(option.label)"></span></label>
                                <div x-show="on" class="mt-2 grid gap-2 sm:grid-cols-3">
                                    <input class="form-control" type="number" min="1" value="1" aria-label="{{ $p('quantity') }}" placeholder="{{ $p('quantity') }}">
                                    <input class="form-control" type="number" step="any" aria-label="{{ $p('weight') }}" placeholder="{{ $p('weight') }}">
                                    <input class="form-control" type="text" aria-label="{{ $p('item_note') }}" placeholder="{{ $p('item_note') }}">
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
                <template x-if="field.type === 'date'"><input type="date" class="form-control" x-model="answers[field.key]"></template>
                <template x-if="field.type === 'time'"><input type="time" class="form-control" x-model="answers[field.key]"></template>
                <template x-if="field.type === 'datetime'"><input type="datetime-local" class="form-control" x-model="answers[field.key]"></template>
                <template x-if="field.type === 'contact'">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input class="form-control" type="text" placeholder="{{ $p('name') }}" x-model="answers[field.key].name">
                        <input class="form-control" type="tel" dir="ltr" placeholder="{{ $p('phone') }}" x-model="answers[field.key].phone">
                    </div>
                </template>
                <template x-if="field.type === 'photo'">
                    <x-admin.button variant="secondary" size="sm" icon="photo" disabled>{{ $p('add_photo') }}</x-admin.button>
                </template>
                <template x-if="field.type === 'dimensions'">
                    <div class="grid gap-2 sm:grid-cols-3">
                        <input class="form-control" type="number" step="any" placeholder="{{ $p('length') }}" x-model="answers[field.key].length">
                        <input class="form-control" type="number" step="any" placeholder="{{ $p('width') }}" x-model="answers[field.key].width">
                        <input class="form-control" type="number" step="any" placeholder="{{ $p('height') }}" x-model="answers[field.key].height">
                    </div>
                </template>

                <p class="form-hint" x-show="field.min !== null && field.max !== null && ['integer', 'decimal', 'weight', 'dimensions'].includes(field.type)">{{ $p('range') }} <span x-text="field.min + ' – ' + field.max" dir="ltr"></span></p>
                <p class="form-hint" x-show="text(field.help)" x-text="text(field.help)"></p>
            </div>
        </template>
    </div>
</div>
