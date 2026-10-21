<?php

namespace App\Http\Controllers\Admin\Booking;

use App\DataTables\Admin\Booking\OffersMonitorDataTable;
use App\DTOs\Booking\OfferFilterData;
use App\Http\Controllers\Admin\AdminController;
use App\Services\Offers\OfferMonitorService;
use Illuminate\Http\Request;

/**
 * Offers monitor (offers.view): offers with filters, conversion stats and anomaly flags. Read-only.
 */
final class OfferMonitorController extends AdminController
{
    public function index(Request $request, OffersMonitorDataTable $table, OfferMonitorService $monitor): mixed
    {
        return $table->render('admin.offers.index', ['stats' => $monitor->stats(OfferFilterData::fromRequest($request))]);
    }
}
