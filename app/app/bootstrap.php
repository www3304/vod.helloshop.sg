<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');

define('APP_ROOT', dirname(__DIR__));
define('STORAGE_DIR', APP_ROOT . '/storage');
define('CACHE_DIR', STORAGE_DIR . '/cache');

if (!is_dir(CACHE_DIR)) {
  @mkdir(CACHE_DIR, 0775, true);
}

function json_read(string $path, $default = []) {
  if (!file_exists($path)) return $default;
  $raw = file_get_contents($path);
  $data = json_decode($raw, true);
  return is_array($data) ? $data : $default;
}

function json_write(string $path, $data): void {
  $tmp = $path . '.tmp';
  file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  rename($tmp, $path);
}

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function getAds() {
    $file = __DIR__ . '/../storage/ads.json';
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true);
}
