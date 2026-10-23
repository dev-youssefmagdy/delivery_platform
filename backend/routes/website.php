<?php

use App\Enums\Common\Locale;
use App\Enums\Policy\PolicyType;
use App\Http\Controllers\Website\AppDownloadController;
use App\Http\Controllers\Website\BlogController;
use App\Http\Controllers\Website\ContactController;
use App\Http\Controllers\Website\ContentPageController;
use App\Http\Controllers\Website\HelpController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\LegalController;
use App\Http\Controllers\Website\ProviderLeadController;
use App\Http\Controllers\Website\ReceiptController;
use App\Http\Controllers\Website\SeoController;
use App\Http\Controllers\Website\TripShareController;
use App\Http\Middleware\Website\SiteLocale;
use App\Http\Middleware\Website\ThrottlePublicForm;
use App\Support\Website\SitePaths;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

/*
| Public website (ADR-01, P5.5). Middleware: `web`. Every page exists once per language: Arabic (default) unprefixed, English under
| /en, each with its own localized path (lang/{locale}/site.php `paths`, see SitePaths). Route names: `site.{locale}.{key}`; the
| language of a request is fixed by its route (SiteLocale), never by the session or Accept-Language, so URLs, canonicals and
| hreflang are stable. English page paths are also the stable paths CMS menus and links use (SitePaths::resolve).
*/
$paths = app(SitePaths::class);
$slug = '[^/]+';

foreach (Locale::cases() as $locale) {
    $uri = static fn (string $key): string => $paths->routeUri($key, $locale);

    Route::middleware(SiteLocale::class.':'.$locale->value)->name('site.'.$locale->value.'.')->group(function () use ($uri, $slug): void {
        Route::get($uri('home'), [HomeController::class, 'show'])->name('home');
        Route::get($uri('customer-app'), [ContentPageController::class, 'customerApp'])->name('customer-app');
        Route::get($uri('apps'), [AppDownloadController::class, 'show'])->name('apps');
        Route::get($uri('how-it-works'), [ContentPageController::class, 'howItWorks'])->name('how-it-works');
        Route::get($uri('safety'), [ContentPageController::class, 'safety'])->name('safety');
        Route::get($uri('about'), [ContentPageController::class, 'about'])->name('about');
        Route::get($uri('services'), [ContentPageController::class, 'services'])->name('services');
        Route::get($uri('service'), [ContentPageController::class, 'service'])->where('slug', $slug)->name('service');
        Route::get($uri('cities'), [ContentPageController::class, 'cities'])->name('cities');
        Route::get($uri('city'), [ContentPageController::class, 'city'])->where('slug', $slug)->name('city');
        Route::get($uri('help'), [HelpController::class, 'index'])->name('help');
        Route::get($uri('help-article'), [HelpController::class, 'article'])->where('slug', $slug)->name('help-article');
        Route::get($uri('blog'), [BlogController::class, 'index'])->name('blog');
        Route::get($uri('blog-article'), [BlogController::class, 'article'])->where('slug', $slug)->name('blog-article');
        Route::get($uri('legal-index'), [LegalController::class, 'index'])->name('legal-index');
        Route::get($uri('legal'), [LegalController::class, 'show'])->where('type', implode('|', PolicyType::values()))->name('legal');

        Route::get($uri('become-provider'), [ProviderLeadController::class, 'show'])->name('become-provider');
        Route::get($uri('contact'), [ContactController::class, 'show'])->name('contact');

        // Forms: validate-before-submit (Precognition) and the real submit share the URL. Throttled per IP (`public-forms`; the
        // validation-only calls use their own limiter), CSRF-protected by the `web` group, honeypot and time trap in the requests.
        Route::middleware([ThrottlePublicForm::class, HandlePrecognitiveRequests::class])->group(function () use ($uri): void {
            Route::post($uri('become-provider'), [ProviderLeadController::class, 'store'])->name('become-provider.submit');
            Route::post($uri('contact'), [ContactController::class, 'store'])->name('contact.submit');
        });
    });
}

// Old/explicit Arabic prefix: the canonical Arabic URL has none.
Route::get('ar/{path?}', static fn (string $path = '') => redirect('/'.ltrim($path, '/'), 301))->where('path', '.*');

Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('website.sitemap');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('website.robots');

// Public trip share (P2.5, SRS §13.1): the token is the only credential, so the endpoints are throttled per IP and every
// failure looks the same. Pages are noindex and uncacheable (set by the controller).
Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('t/{token}', [TripShareController::class, 'show'])->where('token', '[A-Za-z0-9]{43}')->name('website.trip-share');
    Route::get('t/{token}/data', [TripShareController::class, 'data'])->where('token', '[A-Za-z0-9]{43}')->name('website.trip-share.data');
});

// Printable receipt (P3.5, SRS §6.7): reached only through the short-lived signed link the receipt API returns; noindex.
Route::get('receipts/{receipt:uuid}', [ReceiptController::class, 'show'])->whereUuid('receipt')->middleware(['signed', 'throttle:60,1'])->name('website.receipt');
