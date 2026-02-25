<?php
declare(strict_types=1);

final class Cache {
  public static function get(string $key, int $ttlSeconds): ?string {
    $file = CACHE_DIR . '/' . sha1($key) . '.cache';
    if (!file_exists($file)) return null;
    if (time() - filemtime($file) > $ttlSeconds) return null;
    return file_get_contents($file) ?: null;
  }

  public static function set(string $key, string $value): void {
    $file = CACHE_DIR . '/' . sha1($key) . '.cache';
    file_put_contents($file, $value);
  }
}
