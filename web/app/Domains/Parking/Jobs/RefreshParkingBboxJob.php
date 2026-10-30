<?php

namespace App\Domains\Parking\Jobs;

use App\Domains\Parking\Services\ParkingCatalog;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Runs after the HTTP response (via ->afterResponse()).
 * Intentionally not queued so shared hosting works without a queue worker.
 */
class RefreshParkingBboxJob
{
    use Dispatchable;

    public function __construct(
        public float $south,
        public float $west,
        public float $north,
        public float $east,
        public ?string $cityHint = null,
        public bool $force = false,
    ) {}

    public function handle(ParkingCatalog $catalog): void
    {
        try {
            $catalog->refreshBbox($this->south, $this->west, $this->north, $this->east, $this->cityHint, $this->force);
        } catch (\Throwable $e) {
            Log::warning('RefreshParkingBboxJob failed', [
                'error' => $e->getMessage(),
                'bbox' => [$this->south, $this->west, $this->north, $this->east],
            ]);
        }
    }
}
