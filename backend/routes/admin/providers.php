<?php

use App\Http\Controllers\Admin\Provider\ProviderController;
use App\Http\Controllers\Admin\Provider\ProviderTabController;
use App\Models\LedgerAccount;
use App\Models\PayoutRequest;
use App\Models\ProviderProfile;
use App\Models\RiskSignal;
use Illuminate\Support\Facades\Route;

/*
| Providers (drivers.view / view_pii / verify / reject / suspend, customers.process_deletion for deletion requests).
| Loaded by routes/admin.php. Providers are addressed by uuid. There is no create, edit or delete route: providers
| apply through the driver app, applications are decided in the verification queue and accounts leave only through a
| processed deletion request.
*/
Route::prefix('providers')->name('providers.')->group(function (): void {
    Route::controller(ProviderController::class)->group(function (): void {
        Route::get('/', 'index')->name('index')->can('viewAny', ProviderProfile::class);
        Route::get('{provider:uuid}', 'show')->whereUuid('provider')->name('show')->can('view', 'provider');
        Route::post('{provider:uuid}/reveal', 'reveal')->whereUuid('provider')->name('reveal')->can('viewPii', 'provider');
        Route::post('{provider:uuid}/suspend', 'suspend')->whereUuid('provider')->name('suspend')->can('suspend', 'provider');
        Route::post('{provider:uuid}/reactivate', 'reactivate')->whereUuid('provider')->name('reactivate')->can('reactivate', 'provider');
        Route::post('{provider:uuid}/services/{serviceCategory}/approve', 'approveService')->whereUuid('provider')->whereNumber('serviceCategory')->name('services.approve')->can('decideService', 'provider');
        Route::post('{provider:uuid}/services/{serviceCategory}/reject', 'rejectService')->whereUuid('provider')->whereNumber('serviceCategory')->name('services.reject')->can('decideService', 'provider');
        Route::post('{provider:uuid}/deletion-requests/{deletionRequest}/approve', 'approveDeletion')->whereUuid(['provider', 'deletionRequest'])->name('deletion-requests.approve')->can('processDeletion', 'provider');
        Route::post('{provider:uuid}/deletion-requests/{deletionRequest}/reject', 'rejectDeletion')->whereUuid(['provider', 'deletionRequest'])->name('deletion-requests.reject')->can('processDeletion', 'provider');
    });

    Route::controller(ProviderTabController::class)->prefix('{provider:uuid}/tabs')->name('tabs.')->whereUuid('provider')->group(function (): void {
        Route::get('services', 'services')->name('services')->can('view', 'provider');
        Route::get('vehicles', 'vehicles')->name('vehicles')->can('view', 'provider');
        Route::get('documents', 'documents')->name('documents')->can('view', 'provider');
        Route::get('history', 'history')->name('history')->can('view', 'provider');
        Route::get('jobs', 'jobs')->name('jobs')->can('view', 'provider');
        Route::get('earnings', 'earnings')->name('earnings')->can('view', 'provider');
        Route::get('payouts', 'payouts')->name('payouts')->can('view', 'provider')->can('viewAny', PayoutRequest::class);
        Route::get('wallet', 'wallet')->name('wallet')->can('view', 'provider')->can('viewWallets', LedgerAccount::class);
        Route::get('deletion-requests', 'deletionRequests')->name('deletion-requests')->can('view', 'provider');
        Route::get('ratings', 'ratings')->name('ratings')->can('view', 'provider');
        Route::get('risk', 'risk')->name('risk')->can('view', 'provider')->can('viewAny', RiskSignal::class);
        Route::get('audit', 'audit')->name('audit')->can('view', 'provider');
    });
});
