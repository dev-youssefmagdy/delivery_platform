<?php

namespace App\Http\Controllers\Admin\Geography;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Geography\CoveragePreviewRequest;
use App\Services\Geography\CoveragePreviewService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Admin coverage preview: resolves a point (or a two-point trip) like the API does. Read-only, nothing is stored.
 */
final class CoveragePreviewController extends AdminController
{
    public function __construct(private readonly CoveragePreviewService $preview) {}

    public function show(): View
    {
        return view('admin.coverage-preview.show');
    }

    public function run(CoveragePreviewRequest $request): AdminFormResponse
    {
        $result = $this->preview->preview($request->origin(), $request->destination());

        return $this->formResponse()->replaceHtml('#coverage-result', view('admin.coverage-preview._result', ['preview' => $result])->render());
    }
}
