<?php

namespace App\Integrations\Push\Drivers;

use App\Integrations\Push\Contracts\PushNotifier;
use App\Integrations\Push\Data\PushMessage;
use App\Integrations\Push\Exceptions\PushDeliveryException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

/**
 * Firebase Cloud Messaging HTTP v1 (`POST https://fcm.googleapis.com/v1/projects/{id}/messages:send`). The access token is
 * a short-lived OAuth2 token minted from the service-account JSON (RS256 JWT bearer grant) and cached until shortly
 * before it expires. APNs is reached through FCM: iOS devices register their FCM token, the Firebase project holds the APNs key.
 *
 * IMPLEMENTED FROM THE FIREBASE DOCUMENTATION AND NOT TESTED AGAINST THE LIVE SERVICE (no credentials in this
 * repository); verify on a staging project (docs/INTEGRATIONS.md). The service-account file stays outside the repository
 * and its contents are never logged.
 */
final class FcmPushNotifier implements PushNotifier
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const TOKEN_CACHE_KEY = 'push:fcm:access-token';

    public function __construct(private readonly HttpFactory $http, private readonly Cache $cache) {}

    public function send(PushMessage $message): string
    {
        $projectId = (string) config('integrations.push.fcm.project_id');

        if ($projectId === '') {
            throw new PushDeliveryException('Firebase is not configured.', 'push_not_configured', permanent: true);
        }

        $payload = [
            'message' => [
                'token' => $message->token,
                'notification' => ['title' => $message->title, 'body' => $message->body],
                'data' => array_map(strval(...), $message->data),
                'android' => array_filter([
                    'priority' => $message->highPriority ? 'HIGH' : 'NORMAL',
                    'collapse_key' => $message->collapseKey,
                    'notification' => ['channel_id' => 'platform_default'],
                ]),
                'apns' => [
                    'headers' => ['apns-priority' => $message->highPriority ? '10' : '5'],
                    'payload' => ['aps' => ['sound' => 'default']],
                ],
            ],
        ];

        try {
            $response = $this->http->timeout(10)->withToken($this->accessToken())
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);
        } catch (PushDeliveryException $e) {
            throw $e;
        } catch (Throwable) {
            // Deliberately drop the original exception: its message can contain the request URL or token.
            throw new PushDeliveryException('Firebase could not be reached.', 'push_unreachable');
        }

        if ($response->successful()) {
            return (string) $response->json('name', '');
        }

        $status = (string) $response->json('error.status', '');
        $detail = (string) $response->json('error.details.0.errorCode', '');

        if ($response->status() === 404 || $status === 'NOT_FOUND' || $detail === 'UNREGISTERED') {
            throw PushDeliveryException::unregistered();
        }

        if ($status === 'INVALID_ARGUMENT' || $response->status() === 400) {
            throw new PushDeliveryException('Firebase rejected the message (invalid argument).', 'push_invalid_argument', permanent: true);
        }

        if (in_array($response->status(), [401, 403], true)) {
            $this->cache->forget(self::TOKEN_CACHE_KEY);
            throw new PushDeliveryException('Firebase refused the credentials.', 'push_unauthorised');
        }

        throw new PushDeliveryException('Firebase answered '.$response->status().' ('.$status.').', 'push_provider_error');
    }

    public function name(): string
    {
        return 'fcm';
    }

    /**
     * @throws PushDeliveryException
     */
    private function accessToken(): string
    {
        $cached = $this->cache->get(self::TOKEN_CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $credentials = $this->credentials();
        $now = time();
        $claims = [
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => $credentials['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
        ];
        $unsigned = $this->base64Url((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT'])).'.'.$this->base64Url((string) json_encode($claims));

        $signature = '';

        if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new PushDeliveryException('The Firebase service account key could not sign.', 'push_credentials_invalid', permanent: true);
        }

        $response = $this->http->timeout(10)->asForm()->post($credentials['token_uri'], [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned.'.'.$this->base64Url($signature),
        ]);

        $token = $response->json('access_token');

        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new PushDeliveryException('Firebase did not issue an access token.', 'push_unauthorised');
        }

        $this->cache->put(self::TOKEN_CACHE_KEY, $token, max(60, (int) $response->json('expires_in', 3600) - 120));

        return $token;
    }

    /**
     * @return array{client_email: string, private_key: string, token_uri: string}
     */
    private function credentials(): array
    {
        $path = (string) config('integrations.push.fcm.credentials');
        $json = $path !== '' && is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;

        if (! is_array($json) || ! is_string($json['client_email'] ?? null) || ! is_string($json['private_key'] ?? null)) {
            throw new PushDeliveryException('The Firebase service account file is missing or invalid.', 'push_credentials_invalid', permanent: true);
        }

        return [
            'client_email' => $json['client_email'],
            'private_key' => $json['private_key'],
            'token_uri' => is_string($json['token_uri'] ?? null) ? $json['token_uri'] : 'https://oauth2.googleapis.com/token',
        ];
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
