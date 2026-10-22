<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\BookingReceipt;
use App\Services\Booking\BookingReceiptService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The printable receipt page `/receipts/{uuid}` (SRS §6.7). Reached only through the short-lived signed link the receipt
 * API returns (`signed` middleware), so the link is the credential; `as=customer|provider` picks which party's figures
 * are shown and `lang=ar|en` the language (Arabic, right-to-left, by default). The page is print-ready (A4), so "print to
 * PDF" produces the PDF receipt; it is noindex and uncacheable.
 */
final class ReceiptController extends Controller
{
    public function __construct(
        private readonly BookingReceiptService $receipts,
        private readonly MoneyFormatter $formatter,
        private readonly CurrencyRegistry $currencies,
    ) {}

    public function show(Request $request, BookingReceipt $receipt): Response
    {
        $party = $request->query('as') === 'provider' ? 'provider' : 'customer';
        $locale = $request->query('lang') === 'en' ? 'en' : 'ar';
        app()->setLocale($locale);
        $currency = $this->currencies->get($receipt->currency);

        $response = response()->view('website.receipt', [
            'receipt' => $party === 'provider' ? $this->receipts->providerView($receipt) : $this->receipts->customerView($receipt),
            'party' => $party,
            'locale' => $locale,
            'dir' => $locale === 'ar' ? 'rtl' : 'ltr',
            'money' => fn (int $minor): string => $this->formatter->format(Money::of($minor, $currency), $locale),
        ]);

        return $response->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow')->header('Referrer-Policy', 'no-referrer');
    }
}
