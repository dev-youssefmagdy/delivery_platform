<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Booking\TripShareViewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Public trip-share page `/t/{token}` and its polling endpoint (SRS §6.6, §13.1). The token is the only credential. Every
 * failure (unknown, expired, revoked, finished) looks the same, so the page cannot be used to probe tokens. Responses are
 * not cacheable, not indexable and send no referrer.
 */
final class TripShareController extends Controller
{
    public function __construct(private readonly TripShareViewService $shares) {}

    public function show(string $token): View|Response
    {
        $data = $this->shares->view($token);

        $response = response()->view($data === null ? 'website.trip-share-unavailable' : 'website.trip-share', ['trip' => $data, 'token' => $token], $data === null ? 404 : 200);

        return $this->harden($response);
    }

    public function data(string $token): JsonResponse
    {
        $data = $this->shares->view($token);

        if ($data === null) {
            return $this->harden(response()->json(['available' => false], 404));
        }

        return $this->harden(response()->json([
            'available' => true,
            'status' => $data['status'],
            'status_label' => $data['status_label'],
            'position' => $data['position'] === null ? null : ['lat' => $data['position']['lat'], 'lng' => $data['position']['lng'], 'recorded_at' => $data['position']['recorded_at']->toIso8601ZuluString()],
            'expires_at' => $data['expires_at']->toIso8601ZuluString(),
        ]));
    }

    /**
     * @template T of \Symfony\Component\HttpFoundation\Response
     *
     * @param T $response
     * @return T
     */
    private function harden($response)
    {
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
