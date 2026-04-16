<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Console;

use Illuminate\Console\Command;
use ServeraCloud\Manual\Services\ManualRepository;

final class ClearManualCommand extends Command {
    protected $signature = 'manual:clear';

    protected $description = 'Clears the manual page and search caches.';

    public function handle(ManualRepository $manual): int {
        $cleared = $manual->clear();

        $this->info(sprintf('Manual cache cleared: %d entries removed.', $cleared));

        return self::SUCCESS;
    }
}
