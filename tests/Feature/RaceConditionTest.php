<?php

declare(strict_types=1);

namespace Tests\Feature;

use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Race condition proof: launches N concurrent PHP processes
 * that all try to assign the SAME order at the same time.
 *
 * Expected: exactly 1 succeeds, N-1 fail with ALREADY_ASSIGNED.
 */
final class RaceConditionTest extends TestCase
{
    use DatabaseMigrations;

    #[Test]
    public function only_one_process_succeeds_when_assigning_concurrently(): void
    {
        // Arrange: many drivers, one pending order
        Driver::factory()->available()->count(10)->create();
        $order = Order::factory()->pending()->create();

        // Act: spawn 10 concurrent processes
        $concurrentCount = 10;
        $processes = [];

        for ($i = 0; $i < $concurrentCount; $i++) {
            $process = new Process([
                PHP_BINARY,
                'artisan',
                'race-test:assign',
                (string) $order->id,
                '--env=testing',
            ], base_path());

            $process->start();
            $processes[] = $process;
        }

        // Wait for all to finish
        foreach ($processes as $p) {
            $p->wait();
        }

        // Count outcomes by parsing stdout
        $successes = 0;
        $alreadyAssigned = 0;
        $other = 0;

        foreach ($processes as $p) {
            $output = trim($p->getOutput());
            if (str_contains($output, 'SUCCESS')) {
                $successes++;
            } elseif (str_contains($output, 'ALREADY_ASSIGNED')) {
                $alreadyAssigned++;
            } else {
                $other++;
            }
        }

        // Assert: exactly one winner, rest see "already assigned"
        $this->assertSame(1, $successes, 'Exactly one process must succeed');
        $this->assertSame($concurrentCount - 1, $alreadyAssigned);
        $this->assertSame(0, $other);

        // Assert: DB shows the order assigned to exactly one driver
        $order->refresh();
        $this->assertSame(OrderStatus::ASSIGNED->value, $order->status->value);
        $this->assertNotNull($order->driver_id);
    }
}
