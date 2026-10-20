<?php

namespace Tests\Unit\Provider;

use App\Enums\Provider\FileScanStatus;
use App\Integrations\Files\Drivers\ClamAvFileScanner;
use App\Integrations\Files\Drivers\NoopFileScanner;
use App\Integrations\Files\Exceptions\FileScannerUnavailableException;
use App\Integrations\Files\UploadScanner;
use App\Integrations\IntegrationGuard;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileScannerTest extends TestCase
{
    public function test_the_noop_scanner_never_claims_a_file_is_clean(): void
    {
        $result = (new NoopFileScanner)->scan(__FILE__);

        $this->assertSame(FileScanStatus::Skipped, $result->status);
        $this->assertFalse($result->isInfected());
    }

    public function test_clamd_replies_are_parsed(): void
    {
        $this->assertSame(FileScanStatus::Clean, ClamAvFileScanner::parseReply("stream: OK\0")->status);

        $infected = ClamAvFileScanner::parseReply("stream: Win.Test.EICAR_HDB-1 FOUND\0");
        $this->assertSame(FileScanStatus::Infected, $infected->status);
        $this->assertSame('Win.Test.EICAR_HDB-1', $infected->signature);
    }

    public function test_errors_and_garbage_are_not_a_verdict(): void
    {
        foreach (["INSTREAM size limit exceeded. ERROR\0", '', 'something odd'] as $reply) {
            try {
                ClamAvFileScanner::parseReply($reply);
                $this->fail("'{$reply}' must not be accepted as a verdict.");
            } catch (FileScannerUnavailableException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_an_unreachable_clamd_fails_closed(): void
    {
        $this->expectException(FileScannerUnavailableException::class);

        (new ClamAvFileScanner('127.0.0.1', 1, 1))->scan(__FILE__);
    }

    public function test_the_noop_driver_is_refused_outside_local_and_testing(): void
    {
        $this->expectException(RuntimeException::class);

        IntegrationGuard::assertAllowed(['file_scanner' => 'noop'], 'production', ['fake', 'log', 'noop'], ['local', 'testing']);
    }

    public function test_the_noop_driver_is_allowed_in_testing(): void
    {
        IntegrationGuard::assertAllowed(['file_scanner' => 'noop'], 'testing', ['fake', 'log', 'noop'], ['local', 'testing']);

        $this->addToAssertionCount(1);
        $this->assertTrue(class_exists(UploadScanner::class));
    }
}
