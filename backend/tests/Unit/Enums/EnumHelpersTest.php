<?php

namespace Tests\Unit\Enums;

use App\Enums\Common\AuditAction;
use App\Enums\Common\Locale;
use App\Enums\Common\QueueName;
use App\Enums\Support\BadgeColor;
use Tests\TestCase;

class EnumHelpersTest extends TestCase
{
    public function test_values_options_and_labels(): void
    {
        app()->setLocale('en');

        $this->assertSame(['ar', 'en'], Locale::values());
        $this->assertSame(['ar' => 'العربية', 'en' => 'English'], Locale::options());
        $this->assertContains('Created', AuditAction::labels());
        $this->assertSame('in:critical,default,notifications,low', QueueName::inRule());
        $this->assertSame('critical,default,notifications,low', QueueName::workerOrder());
    }

    public function test_labels_are_localized_with_arabic_default(): void
    {
        $this->assertSame('إنشاء', AuditAction::Created->label());

        app()->setLocale('en');
        $this->assertSame('Created', AuditAction::Created->label());
    }

    public function test_try_from_value_and_from_label(): void
    {
        $this->assertSame(Locale::English, Locale::tryFromValue('en'));
        $this->assertNull(Locale::tryFromValue(''));
        $this->assertNull(Locale::tryFromValue(null));
        $this->assertNull(Locale::tryFromValue('fr'));
        $this->assertSame(AuditAction::Deleted, AuditAction::fromLabel('حذف'));
        $this->assertNull(AuditAction::fromLabel('nope'));
    }

    public function test_colors_and_locale_direction(): void
    {
        $this->assertSame(BadgeColor::Danger, AuditAction::Deleted->color());
        $this->assertSame('rtl', Locale::Arabic->direction());
        $this->assertFalse(Locale::English->isRtl());
        $this->assertSame(Locale::Arabic, Locale::default());
    }

    public function test_every_enum_case_has_a_translation_in_both_locales(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach ([AuditAction::class, BadgeColor::class, Locale::class, \App\Enums\Common\ActorType::class, \App\Enums\Common\Platform::class, \App\Enums\Common\HealthStatus::class] as $enum) {
                foreach ($enum::cases() as $case) {
                    $this->assertStringStartsNotWith('enums.', $case->label(), "{$enum}::{$case->name} is missing a [{$locale}] label");
                }
            }
        }
    }
}
