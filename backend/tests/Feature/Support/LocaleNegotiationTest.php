<?php

namespace Tests\Feature\Support;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * SetLocale: user preference → session → Accept-Language → ar (Plan §4.4).
 */
class LocaleNegotiationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $probe = fn () => response()->json(['locale' => app()->getLocale(), 'carbon' => Carbon::getLocale()]);

        Route::middleware('api')->get('api/v1/_test/locale', $probe);
        Route::middleware('web')->get('_test/locale', $probe);
    }

    public function test_defaults_to_arabic_without_accept_language(): void
    {
        $this->getJson('/api/v1/_test/locale', ['Accept-Language' => ''])
            ->assertJson(['locale' => 'ar', 'carbon' => 'ar'])
            ->assertHeader('Content-Language', 'ar');
    }

    public function test_uses_the_best_supported_accept_language(): void
    {
        $this->getJson('/api/v1/_test/locale', ['Accept-Language' => 'en-US,en;q=0.9'])->assertJson(['locale' => 'en', 'carbon' => 'en']);
        $this->getJson('/api/v1/_test/locale', ['Accept-Language' => 'fr-FR,ar;q=0.8,en;q=0.5'])->assertJson(['locale' => 'ar']);
        $this->getJson('/api/v1/_test/locale', ['Accept-Language' => 'ar-EG'])->assertJson(['locale' => 'ar']);
    }

    public function test_unsupported_languages_fall_back_to_arabic(): void
    {
        $this->getJson('/api/v1/_test/locale', ['Accept-Language' => 'fr-FR,de;q=0.8'])->assertJson(['locale' => 'ar']);
    }

    public function test_user_preference_wins_over_accept_language(): void
    {
        $user = new class extends User implements HasLocalePreference
        {
            protected $table = 'users';

            public function preferredLocale(): string
            {
                return 'en';
            }
        };
        $user->forceFill(['id' => 1, 'name' => 'x', 'email' => 'x@example.com', 'password' => 'x']);

        $this->actingAs($user)->getJson('/api/v1/_test/locale', ['Accept-Language' => 'ar'])->assertJson(['locale' => 'en']);
    }

    public function test_session_locale_wins_over_accept_language_on_web_routes(): void
    {
        $this->withSession([SetLocale::SESSION_KEY => 'en'])
            ->get('/_test/locale', ['Accept-Language' => 'ar'])
            ->assertJson(['locale' => 'en']);
    }

    public function test_invalid_session_locale_is_ignored(): void
    {
        $this->withSession([SetLocale::SESSION_KEY => 'xx'])
            ->get('/_test/locale', ['Accept-Language' => ''])
            ->assertJson(['locale' => 'ar']);
    }
}
