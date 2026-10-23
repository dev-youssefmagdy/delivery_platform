@php($title = $article ? __('content.articles.edit_title') : __('content.articles.create_title'))
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :breadcrumbs="array_filter([
            ['label' => __('content.articles.title'), 'url' => route('admin.content-articles.index')],
            $article ? ['label' => $article->title, 'url' => route('admin.content-articles.show', $article)] : null,
            ['label' => $article ? __('admin.common.edit') : __('admin.common.create')],
        ])"
    />

    <x-admin.card>
        <x-admin.form :action="$article ? route('admin.content-articles.update', $article) : route('admin.content-articles.store')" :method="$article ? 'PUT' : 'POST'">
            @if ($article)
                <input type="hidden" name="lock_version" value="{{ $article->lock_version }}">
            @endif
            <x-admin.form.section :title="__('content.sections.basics')">
                <x-admin.form.enum-select name="type" :label="__('content.fields.article_type')" :enum="\App\Enums\Content\ArticleType::class" :value="$article?->type" required />
                <x-admin.form.input name="slug" dir="ltr" :label="__('content.fields.slug')" :hint="__('content.hints.slug_article')" :value="$article?->slug" maxlength="96" required />
                <x-admin.form.remote-select name="category_id" lookup="article-categories" :label="__('content.fields.category')" :selected="$article?->category ? [$article->category->id => $article->category->name] : []" />
                <x-admin.form.translatable-input name="title" :label="__('content.fields.title')" :value="$article?->getTranslations('title') ?? []" maxlength="191" required />
                <x-admin.form.translatable-textarea name="summary" :label="__('content.fields.summary')" :value="$article?->getTranslations('summary') ?? []" :rows="3" maxlength="500" />
                <x-admin.content.media-select name="cover_media_id" :label="__('content.fields.cover_image')" :media="$article?->cover" />
                <x-admin.form.input name="sort_order" type="number" min="0" max="65535" :label="__('content.fields.sort_order')" :hint="__('content.hints.sort_order')" :value="$article?->sort_order ?? 0" />
                <x-admin.content.fallback-toggle :model="$article" />
            </x-admin.form.section>

            <x-admin.form.section :title="__('content.sections.content')" :description="__('content.sections.content_hint')">
                <x-admin.content.body-fields :model="$article" required />
            </x-admin.form.section>

            <x-admin.content.seo-fields :model="$article" />

            <x-admin.form.actions :cancel="$article ? route('admin.content-articles.show', $article) : route('admin.content-articles.index')" :submit="__('content.save_draft')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
