<?php

namespace App\Http\Controllers\Admin\Finance;

use App\DataTables\Admin\Finance\JournalEntriesDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Models\Booking;
use App\Models\JournalEntry;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Finance\JournalLineRepositoryInterface;
use Illuminate\Contracts\View\View;

/**
 * Journal entries (ledger.view), read-only: the listing and one entry with its lines. An entry is never edited; a
 * mistake is corrected by a compensating entry that links back to it.
 */
final class JournalEntryController extends AdminController
{
    public function index(JournalEntriesDataTable $table): mixed
    {
        return $table->render('admin.journal-entries.index');
    }

    public function show(JournalEntry $journalEntry, JournalLineRepositoryInterface $lines, BookingRepositoryInterface $bookings): View
    {
        $booking = $journalEntry->reference_type === 'booking' && $journalEntry->reference_id !== null ? $bookings->find($journalEntry->reference_id) : null;

        return view('admin.journal-entries.show', [
            'entry' => $journalEntry->load(['reversalOf', 'reversedBy']),
            'lines' => $lines->forEntry($journalEntry),
            'booking' => $booking instanceof Booking ? $booking : null,
        ]);
    }
}
