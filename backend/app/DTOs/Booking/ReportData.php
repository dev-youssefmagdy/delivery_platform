<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\ReportCategory;

final readonly class ReportData
{
    public function __construct(public ReportCategory $category, public string $description) {}
}
