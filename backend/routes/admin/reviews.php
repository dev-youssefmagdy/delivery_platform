<?php

use App\Http\Controllers\Admin\Reviews\ReviewController;
use App\Http\Controllers\Admin\Reviews\ReviewTabController;
use App\Models\BookingRating;
use App\Models\RatingAppeal;
use App\Models\RatingReport;
use Illuminate\Support\Facades\Route;

/*
| Reviews and moderation (reviews.view / reviews.moderate). Loaded by routes/admin.php. Reviews, reports and appeals are addressed by uuid.
| There is no edit or delete route: a review's text is never rewritten; moderation only changes its visibility, with a reason.
*/
Route::prefix('reviews')->name('reviews.')->group(function (): void {
    Route::controller(ReviewController::class)->group(function (): void {
        Route::get('/', 'index')->name('index')->can('viewAny', BookingRating::class);
        Route::get('{rating}', 'show')->whereUuid('rating')->name('show')->can('view', 'rating');
        Route::post('{rating}/hide', 'hide')->whereUuid('rating')->name('hide')->can('hide', 'rating');
        Route::post('{rating}/restore', 'restore')->whereUuid('rating')->name('restore')->can('restore', 'rating');
        Route::post('{rating}/under-review', 'underReview')->whereUuid('rating')->name('under-review')->can('markUnderReview', 'rating');
        Route::post('{rating}/reports/{report}/dismiss', 'dismissReport')->whereUuid(['rating', 'report'])->name('reports.dismiss')->can('dismiss', 'report');
        Route::post('{rating}/appeals/{appeal}/decide', 'decideAppeal')->whereUuid(['rating', 'appeal'])->name('appeals.decide')->can('decide', 'appeal');
    });

    Route::controller(ReviewTabController::class)->prefix('tabs')->name('tabs.')->group(function (): void {
        Route::get('reports', 'reports')->name('reports')->can('viewAny', RatingReport::class);
        Route::get('appeals', 'appeals')->name('appeals')->can('viewAny', RatingAppeal::class);
    });
});
