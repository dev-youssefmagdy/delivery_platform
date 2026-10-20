<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Services\Identity\ProfileService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves private profile photos behind a short-lived signed URL (`signed` middleware).
 */
final class MediaController extends Controller
{
    public function __construct(private readonly ProfileService $profiles) {}

    public function userPhoto(string $user): StreamedResponse
    {
        $location = $this->profiles->photoLocation($user) ?? abort(404);
        $disk = Storage::disk($location['disk']);

        abort_unless($disk->exists($location['path']), 404);

        return $disk->response($location['path'], null, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
