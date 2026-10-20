<?php

namespace App\Http\Requests\Admin\Customers;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Queue the JSON personal-data export of one customer (needs customers.export).
 */
class ExportCustomerDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof User && Gate::allows('exportData', $customer);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
