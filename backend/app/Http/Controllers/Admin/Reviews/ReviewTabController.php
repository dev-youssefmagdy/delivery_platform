<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\DataTables\Admin\Reviews\ReviewAppealsDataTable;
use App\DataTables\Admin\Reviews\ReviewReportsDataTable;
use App\Enums\Reviews\AppealStatus;
use App\Enums\Reviews\RatingReportStatus;
use App\Http\Controllers\Admin\AdminController;

/**
 * Lazy tabs of the Reviews page: reports and appeals, each its own Yajra DataTable with its own collapsed filters card.
 */
final class ReviewTabController extends AdminController
{
    public function reports(ReviewReportsDataTable $table): mixed
    {
        return $table->render('admin.reviews.tabs.queue', ['tableId' => ReviewReportsDataTable::TABLE_ID, 'statuses' => RatingReportStatus::options(), 'intro' => __('reputation.admin.tabs.reports_intro')]);
    }

    public function appeals(ReviewAppealsDataTable $table): mixed
    {
        return $table->render('admin.reviews.tabs.queue', ['tableId' => ReviewAppealsDataTable::TABLE_ID, 'statuses' => AppealStatus::options(), 'intro' => __('reputation.admin.tabs.appeals_intro')]);
    }
}
