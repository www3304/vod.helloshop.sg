<?php
declare(strict_types=1);

require_once APP_ROOT . '/app/Providers/ProviderInterface.php';
require_once APP_ROOT . '/app/Services/Cache.php';

final class GenericJsonProvider implements ProviderInterface {
  private array $cfg;

  public function __construct(array $cfg) { $this->cfg = $cfg; }

  public function key(): string { return (string)$this->cfg['key']; }
  public function name(): string { return (string)$this->cfg['name']; }

  public function probeUrl(): string {
    // A lightweight endpoint your API supports; change this.
    return rtrim((string)$this->cfg['api_base'], '/') . '/health';
  }

  private function httpGetJson(string $url, int $timeoutMs = 2500): array {
    $cacheKey = "GET:$url";
    $cached = Cache::get($cacheKey, 30);
    if ($cached !== null) {
      $data = json_decode($cached, true);
      return is_array($data) ? $data : [];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT_MS => $timeoutMs,
      CURLOPT_CONNECTTIMEOUT_MS => 1500,
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
      CURLOPT_USERAGENT => 'vod-demo/1.0'
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($body === false || $err || $code < 200 || $code >= 300) return [];
    Cache::set($cacheKey, (string)$body);

    $data = json_decode((string)$body, true);
    return is_array($data) ? $data : [];
  }

  public function search(string $q, int $page = 1): array {
    // Example: /search?q=xxx&page=1
    $url = rtrim((string)$this->cfg['api_base'], '/') . '/search?q=' . rawurlencode($q) . '&page=' . $page;
    $raw = $this->httpGetJson($url);

    // Normalize to:
    // [ 'items' => [ ['id','title','poster','year','score','sourceKey']... ], 'page'=>1,'hasMore'=>bool ]
    $items = [];
    foreach (($raw['items'] ?? []) as $it) {
      $items[] = [
        'id' => (string)($it['id'] ?? ''),
        'title' => (string)($it['title'] ?? ''),
        'poster' => (string)($it['poster'] ?? ''),
        'year' => (string)($it['year'] ?? ''),
        'score' => (string)($it['score'] ?? ''),
        'sourceKey' => $this->key(),
      ];
    }
    return [
      'items' => $items,
      'page' => $page,
      'hasMore' => (bool)($raw['hasMore'] ?? false),
    ];
  }

  public function detail(string $id): array {
    $url = rtrim((string)$this->cfg['api_base'], '/') . '/detail?id=' . rawurlencode($id);
    $raw = $this->httpGetJson($url);

    // Normalize:
    // ['id','title','poster','desc','genres'=>[],'episodes'=>[['ep','name']...], 'sourceKey']
    $eps = [];
    foreach (($raw['episodes'] ?? []) as $e) {
      $eps[] = ['ep' => (string)($e['ep'] ?? '1'), 'name' => (string)($e['name'] ?? ('EP ' . ($e['ep'] ?? '1'))) ];
    }

    return [
      'id' => (string)($raw['id'] ?? $id),
      'title' => (string)($raw['title'] ?? ''),
      'poster' => (string)($raw['poster'] ?? ''),
      'desc' => (string)($raw['desc'] ?? ''),
      'genres' => (array)($raw['genres'] ?? []),
      'episodes' => $eps,
      'sourceKey' => $this->key(),
    ];
  }

  public function play(string $id, string $ep = '1'): array {
    $url = rtrim((string)$this->cfg['api_base'], '/') . '/play?id=' . rawurlencode($id) . '&ep=' . rawurlencode($ep);
    $raw = $this->httpGetJson($url);

    // Normalize to:
    // ['url' => 'https://...m3u8', 'type' => 'hls'|'mp4', 'headers' => []]
    return [
      'url' => (string)($raw['url'] ?? ''),
      'type' => (string)($raw['type'] ?? 'hls'),
      'headers' => (array)($raw['headers'] ?? []),
    ];
  }
}
