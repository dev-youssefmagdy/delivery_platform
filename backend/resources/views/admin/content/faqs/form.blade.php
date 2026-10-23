@php($title = $faq ? __('content.faqs.edit_title') : __('content.faqs.create_title'))
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :breadcrumbs="array_filter([
            ['label' => __('content.faqs.title'), 'url' => route('admin.content-faqs.index')],
            $faq ? ['label' => $faq->question, 'url' => route('admin.content-faqs.show', $faq)] : null,
            ['label' => $faq ? __('admin.common.edit') : __('admin.common.create')],
        ])"
    />

    <x-admin.card>
        <x-admin.form :action="$faq ? route('admin.content-faqs.update', $faq) : route('admin.content-faqs.store')" :method="$faq ? 'PUT' : 'POST'">
            @if ($faq)
                <input type="hidden" name="lock_version" value="{{ $faq->lock_version }}">
            @endif
            <x-admin.form.section :title="__('content.sections.basics')">
                <x-admin.form.remote-select name="category_id" lookup="faq-categories" :label="__('content.fields.category')" :selected="$faq?->category ? [$faq->category->id => $faq->category->name] : []" />
                <x-admin.form.remote-select name="service_category_id" lookup="service-categories" :label="__('content.fields.service_category')" :hint="__('content.hints.faq_service')" :selected="$faq?->serviceCategory ? [$faq->serviceCategory->id => $faq->serviceCategory->name] : []" />
                <x-admin.form.input name="sort_order" type="number" min="0" max="65535" :label="__('content.fields.sort_order')" :hint="__('content.hints.sort_order')" :value="$faq?->sort_order ?? 0" />
                <x-admin.content.fallback-toggle :model="$faq" />
            </x-admin.form.section>
            <x-admin.form.section :title="__('content.sections.content')" :description="__('content.hints.answer_markdown')">
                <x-admin.form.translatable-input name="question" :label="__('content.fields.question')" :value="$faq?->getTranslations('question') ?? []" maxlength="300" required />
                <x-admin.form.translatable-textarea name="answer" :label="__('content.fields.answer')" :value="$faq?->getTranslations('answer') ?? []" :rows="8" maxlength="20000" required />
            </x-admin.form.section>

            <x-admin.form.actions :cancel="$faq ? route('admin.content-faqs.show', $faq) : route('admin.content-faqs.index')" :submit="__('content.save_draft')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
