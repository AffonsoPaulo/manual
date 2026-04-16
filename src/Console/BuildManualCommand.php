<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Console;

use Illuminate\Console\Command;
use ServeraCloud\Manual\Exceptions\ManualException;
use ServeraCloud\Manual\Services\ManualRepository;

final class BuildManualCommand extends Command {
    protected $signature = 'manual:build';

    protected $description = 'Scans documentation, validates routes, warms page cache and builds the search index.';

    public function handle(ManualRepository $manual): int {
        try {
            $result = $manual->build();
        } catch (ManualException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Manual build complete: %d documents scanned, %d visible, %d cached pages, %d search documents.',
            $result['documents'],
            $result['visible_documents'],
            $result['cached_pages'],
            $result['search_documents'],
        ));

        return self::SUCCESS;
    }
}
