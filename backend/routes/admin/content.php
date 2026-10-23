<?php

use App\Enums\Content\ContentType;
use App\Enums\Content\WorkflowAction;
use App\Http\Controllers\Admin\Content\AppLinkController;
use App\Http\Controllers\Admin\Content\ArticleController;
use App\Http\Controllers\Admin\Content\BannerController;
use App\Http\Controllers\Admin\Content\CategoryController;
use App\Http\Controllers\Admin\Content\ContentWorkflowController;
use App\Http\Controllers\Admin\Content\FaqController;
use App\Http\Controllers\Admin\Content\MediaController;
use App\Http\Controllers\Admin\Content\MenuController;
use App\Http\Controllers\Admin\Content\PageController;
use App\Models\ContentAppLink;
use App\Models\ContentArticle;
use App\Models\ContentBanner;
use App\Models\ContentCategory;
use App\Models\ContentFaq;
use App\Models\ContentMedia;
use App\Models\ContentMenu;
use App\Models\ContentPage;
use Illuminate\Support\Facades\Route;

/*
| CMS (content.manage / content.publish). Loaded by routes/admin.php inside the authenticated admin group.
| Workflow entities (pages, articles, FAQs, banners, menus) share the workflow / preview / revision routes below.
*/
$workflowEntity = static function (string $prefix, string $controller, string $model, string $param): void {
    Route::prefix($prefix)->name($prefix.'.')->controller($controller)->group(function () use ($model, $param): void {
        Route::get('/', 'index')->name('index')->can('viewAny', $model);
        Route::get('create', 'create')->name('create')->can('create', $model);
        Route::post('/', 'store')->name('store')->can('create', $model);
        Route::get('{'.$param.'}', 'show')->whereNumber($param)->name('show')->can('view', $param);
        Route::get('{'.$param.'}/activity', 'activity')->whereNumber($param)->name('activity')->can('view', $param);
        Route::get('{'.$param.'}/edit', 'edit')->whereNumber($param)->name('edit')->can('update', $param);
        Route::put('{'.$param.'}', 'update')->whereNumber($param)->name('update')->can('update', $param);
        Route::delete('{'.$param.'}', 'destroy')->whereNumber($param)->name('destroy')->can('delete', $param);
    });
};

$workflowEntity('content-pages', PageController::class, ContentPage::class, 'contentPage');
$workflowEntity('content-articles', ArticleController::class, ContentArticle::class, 'contentArticle');
$workflowEntity('content-faqs', FaqController::class, ContentFaq::class, 'contentFaq');
$workflowEntity('content-banners', BannerController::class, ContentBanner::class, 'contentBanner');
$workflowEntity('content-menus', MenuController::class, ContentMenu::class, 'contentMenu');

Route::prefix('content/{contentType}/{content}')->whereIn('contentType', ContentType::values())->whereNumber('content')->name('content.')->controller(ContentWorkflowController::class)->group(function (): void {
    Route::get('preview', 'preview')->name('preview')->can('viewAny', ContentPage::class);
    Route::get('revisions', 'revisions')->name('revisions')->can('viewAny', ContentPage::class);
    Route::post('revisions/{number}/restore', 'restoreRevision')->whereNumber('number')->name('revisions.restore')->can('viewAny', ContentPage::class);
    Route::post('{action}', 'transition')->whereIn('action', WorkflowAction::values())->name('transition')->can('viewAny', ContentPage::class);
});

$assets = static function (string $prefix, string $controller, string $model, string $param): void {
    Route::prefix($prefix)->name($prefix.'.')->controller($controller)->group(function () use ($model, $param): void {
        Route::get('/', 'index')->name('index')->can('viewAny', $model);
        Route::get('create', 'create')->name('create')->can('create', $model);
        Route::post('/', 'store')->name('store')->can('create', $model);
        Route::get('{'.$param.'}/activity', 'activity')->whereNumber($param)->name('activity')->can('view', $param);
        Route::get('{'.$param.'}/edit', 'edit')->whereNumber($param)->name('edit')->can('update', $param);
        Route::put('{'.$param.'}', 'update')->whereNumber($param)->name('update')->can('update', $param);
        Route::delete('{'.$param.'}', 'destroy')->whereNumber($param)->name('destroy')->can('delete', $param);
    });
};

$assets('content-categories', CategoryController::class, ContentCategory::class, 'contentCategory');
$assets('content-app-links', AppLinkController::class, ContentAppLink::class, 'contentAppLink');
$assets('content-media', MediaController::class, ContentMedia::class, 'contentMedia');
