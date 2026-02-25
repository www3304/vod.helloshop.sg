<?php
declare(strict_types=1);

interface ProviderInterface {
  public function key(): string;
  public function name(): string;

  // Normalized calls:
  public function search(string $q, int $page = 1): array;
  public function detail(string $id): array;
  public function play(string $id, string $ep = '1'): array;

  // Probe target (fast endpoint)
  public function probeUrl(): string;
}
