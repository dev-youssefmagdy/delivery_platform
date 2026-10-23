@props(['blocks' => []])
{{-- Homepage / page sections. One row per block; items are typed one per line as "title | text". --}}
@php
    $text = fn (string $name, string $locale, string $class = 'min-w-60 flex-1') => ['name' => $name.'_'.$locale, 'label' => __('content.blocks.'.$name).' ('.strtoupper($locale).')', 'type' => 'text', 'class' => $class, 'attributes' => ['dir' => $locale === 'ar' ? 'rtl' : 'ltr', 'maxlength' => 191]];
    $area = fn (string $name, string $locale) => ['name' => $name.'_'.$locale, 'label' => __('content.blocks.'.$name).' ('.strtoupper($locale).')', 'type' => 'textarea', 'rows' => 3, 'class' => 'min-w-60 flex-1', 'hint' => $name === 'items' ? __('content.blocks.items_hint') : null, 'attributes' => ['dir' => $locale === 'ar' ? 'rtl' : 'ltr']];
    $fields = [
        ['name' => 'type', 'label' => __('content.blocks.type'), 'type' => 'select', 'options' => \App\Enums\Content\BlockType::options(), 'required' => true, 'class' => 'w-48'],
        $text('title', 'ar') + ['required' => true], $text('title', 'en'),
        $area('body', 'ar'), $area('body', 'en'),
        $area('items', 'ar'), $area('items', 'en'),
        $text('cta_label', 'ar', 'w-48'), $text('cta_label', 'en', 'w-48'),
        ['name' => 'cta_url', 'label' => __('content.blocks.cta_url'), 'type' => 'text', 'class' => 'min-w-60 flex-1', 'attributes' => ['dir' => 'ltr', 'maxlength' => 500]],
        ['name' => 'ref', 'label' => __('content.blocks.ref'), 'type' => 'text', 'class' => 'w-48', 'hint' => __('content.blocks.ref_hint'), 'attributes' => ['dir' => 'ltr', 'maxlength' => 96]],
    ];
@endphp
<x-admin.form.repeater name="blocks" :label="__('content.fields.blocks')" :hint="__('content.hints.blocks')" :fields="$fields" :items="\App\Support\Content\BlockFormMapper::toRows($blocks)" :max="30" :add-label="__('content.blocks.add')" />
