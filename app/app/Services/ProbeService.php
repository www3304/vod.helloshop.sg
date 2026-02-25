<?php
declare(strict_types=1);

final class ProbeService {
  public static function runOnce(): array {
    $metricsPath = STORAGE_DIR . '/metrics.json';
    $metrics = json_read($metricsPath, []);
    $now = time();

    foreach (SourceManager::enabledProviders() as $p) {
      $url = $p->probeUrl();
      $start = microtime(true);

      $ok = false;
      $code = 0;
      $err = '';

      $ch = curl_init($url);
      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT_MS => 2000,
        CURLOPT_CONNECTTIMEOUT_MS => 800,
        CURLOPT_NOBODY => true, // HEAD-like probe
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
      ]);
      curl_exec($ch);
      $err = curl_error($ch);
      $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
      curl_close($ch);

      $ms = (int)round((microtime(true) - $start) * 1000);

      $ok = ($err === '' && $code >= 200 && $code < 400);

      $prev = $metrics[$p->key()] ?? [
        'ok' => 0, 'fail' => 0, 'lat_ewma' => null, 'last_ts' => 0, 'last_ok_ts' => 0
      ];

      // EWMA latency
      $alpha = 0.3;
      $lat = $prev['lat_ewma'];
      $lat = ($lat === null) ? $ms : (int)round($alpha * $ms + (1 - $alpha) * (int)$lat);

      $prev['lat_ewma'] = $lat;
      $prev['last_ts'] = $now;
      if ($ok) { $prev['ok']++; $prev['last_ok_ts'] = $now; }
      else { $prev['fail']++; }

      $metrics[$p->key()] = $prev;
    }

    json_write($metricsPath, $metrics);
    return $metrics;
  }

  public static function getMetrics(): array {
    return json_read(STORAGE_DIR . '/metrics.json', []);
  }
}
