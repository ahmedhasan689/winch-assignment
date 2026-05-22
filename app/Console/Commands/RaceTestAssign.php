<?php

namespace App\Console\Commands;

use Domain\Driver\Exceptions\DriverNotAvailableException;
use Domain\Order\Actions\AssignOrderAction;
use Domain\Order\Exceptions\NoAvailableDriverException;
use Domain\Order\Exceptions\OrderAlreadyAssignedException;
use Illuminate\Console\Command;

/**
 * Internal command used ONLY by RaceConditionTest.
 * Invokes AssignOrderAction directly and prints a result token
 * that the test parses to count outcomes.
 */
final class RaceTestAssign extends Command
{
    protected $signature = 'race-test:assign {orderId}';
    protected $description = 'Internal: assign an order for race-condition testing';

    public function handle(AssignOrderAction $action): int
    {
        try {
            $action->execute((int) $this->argument('orderId'));
            $this->line('SUCCESS');
            return self::SUCCESS;
        } catch (OrderAlreadyAssignedException) {
            $this->line('ALREADY_ASSIGNED');
            return self::FAILURE;
        } catch (DriverNotAvailableException | NoAvailableDriverException) {
            $this->line('NO_DRIVER');
            return self::FAILURE;
        }
    }
}
