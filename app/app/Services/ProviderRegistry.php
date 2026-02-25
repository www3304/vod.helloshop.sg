<?php
declare(strict_types=1);

require_once APP_ROOT . '/app/Services/SourceManager.php';
require_once APP_ROOT . '/app/Services/RouterService.php';

final class ProviderRegistry {
  public static function byKey(string $key) {
    foreach (SourceManager::enabledProviders() as $p) {
      if ($p->key() === $key) return $p;
    }
    return null;
  }

  public static function best() {
    $key = RouterService::bestProviderKey();
    if ($key) {
      $p = self::byKey($key);
      if ($p) return $p;
    }
    // fallback first enabled
    $all = SourceManager::enabledProviders();
    return $all[0] ?? null;
  }
}
