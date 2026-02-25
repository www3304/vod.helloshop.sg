<?php
declare(strict_types=1);

final class AppleCmsProvider {
  private string $key;
  private string $api;
  private bool $enabled;

  public function __construct(array $row) {
    $this->key = (string)($row['key'] ?? '');
    $this->api = rtrim((string)($row['api'] ?? ''), '/');
    $this->enabled = (bool)($row['enabled'] ?? true);
  }

  public function key(): string { return $this->key; }
  public function enabled(): bool { return $this->enabled; }

  private function cachePath(string $id): string {
    return CACHE_DIR . "/cache_{$this->key}_vod_{$id}.json";
  }

  private function cacheGet(string $id, int $ttlSec = 3600): ?array {
    $p = $this->cachePath($id);
    if (!is_file($p)) return null;
    if (time() - filemtime($p) > $ttlSec) return null;
    $d = json_decode((string)file_get_contents($p), true);
    return is_array($d) ? $d : null;
  }

  private function cacheSet(string $id, array $data): void {
    @file_put_contents($this->cachePath($id), json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
  }

  private function httpGetJson(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_CONNECTTIMEOUT => 4,
      CURLOPT_USERAGENT => 'vod-demo/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!$body || $code < 200 || $code >= 300) return [];
    $json = json_decode($body, true);
    return is_array($json) ? $json : [];
  }

  private function apiUrl(array $q): string {
    // keep your base api, just add query string
    return $this->api . '?' . http_build_query($q);
  }

  /** home list: ac=list */
  public function latest(int $page = 1): array {
    $raw = $this->httpGetJson($this->apiUrl(['ac'=>'list','pg'=>$page]));
    $list = $raw['list'] ?? [];
    $items = [];

    foreach ($list as $x) {
      $id = (string)($x['vod_id'] ?? '');
      $title = (string)($x['vod_name'] ?? '');
      if ($id === '' || $title === '') continue;

      // poster missing in list response => try cache detail
      $cached = $this->cacheGet($id);
      $poster = (string)($cached['poster'] ?? '');

      $items[] = [
        'sourceKey' => $this->key,
        'id' => $id,
        'title' => $title,
        'year' => (string)($cached['year'] ?? ''),
        'poster' => $poster ?: '',
        'remark' => (string)($x['vod_remarks'] ?? ''),
        'type' => (string)($x['type_name'] ?? ''),
      ];
    }

    // optional: enrich first 12 items with detail (to get posters)
    $items = $this->enrichPosters($items, 12);

    return [
      'items' => $items,
      'page' => $page,
      'total_pages' => (int)($raw['pagecount'] ?? 1),
      'total' => (int)($raw['total'] ?? 0),
    ];
  }
  
  /** category list: ac=list&t=TYPE_ID + filters */
  public function listByType(string $type, int $page = 1, array $filters = []): array {

    $q = ['ac'=>'list', 't'=>$type, 'pg'=>$page];

    // year / area / by (hits/time)
    if (!empty($filters['year'])) $q['year'] = (string)$filters['year'];
    if (!empty($filters['area'])) $q['area'] = (string)$filters['area'];
    if (!empty($filters['by']))   $q['by']   = (string)$filters['by'];  // hits / time

    // 可选：多数 AppleCMS 支持 order=desc
    if (!empty($filters['by'])) $q['order'] = 'desc';

    $raw = $this->httpGetJson($this->apiUrl($q));

    $list = $raw['list'] ?? [];
    $items = [];

    foreach ($list as $x) {
      $id = (string)($x['vod_id'] ?? '');
      $title = (string)($x['vod_name'] ?? '');
      if ($id === '' || $title === '') continue;

      $cached = $this->cacheGet($id);
      $poster = (string)($cached['poster'] ?? '');

      $items[] = [
        'sourceKey' => $this->key,
        'id' => $id,
        'title' => $title,
        'year' => (string)($cached['year'] ?? ''),
        'poster' => $poster ?: (string)($x['vod_pic'] ?? ''), // 有些 list 本身有图
        'remark' => (string)($x['vod_remarks'] ?? ''),
        'type' => (string)($x['type_name'] ?? ''),
      ];
    }

    $items = $this->enrichPosters($items, 12);

    return [
      'items' => $items,
      'page' => $page,
      'total_pages' => (int)($raw['pagecount'] ?? 1),
      'total' => (int)($raw['total'] ?? 0),
    ];
  }
  
    /** get categories (if API returns class list) */
  public function classes(): array {
    // many AppleCMS APIs return "class" in list response
    $raw = $this->httpGetJson($this->apiUrl(['ac'=>'list','pg'=>1]));
    $out = [];

    foreach (($raw['class'] ?? []) as $c) {
      $id = (string)($c['type_id'] ?? '');
      $name = (string)($c['type_name'] ?? '');
      if ($id !== '' && $name !== '') {
        $out[] = ['id' => $id, 'name' => $name];
      }
    }

    return $out;
  }

  /** search: wd=keyword */
  public function search(string $q, int $page = 1): array {
    $raw = $this->httpGetJson($this->apiUrl(['wd'=>$q,'pg'=>$page]));
    $list = $raw['list'] ?? [];
    $items = [];

    foreach ($list as $x) {
      $id = (string)($x['vod_id'] ?? '');
      $title = (string)($x['vod_name'] ?? '');
      if ($id === '' || $title === '') continue;

      $cached = $this->cacheGet($id);
      $poster = (string)($cached['poster'] ?? '');

      $items[] = [
        'sourceKey' => $this->key,
        'id' => $id,
        'title' => $title,
        'year' => (string)($cached['year'] ?? ''),
        'poster' => $poster ?: '',
        'remark' => (string)($x['vod_remarks'] ?? ''),
        'type' => (string)($x['type_name'] ?? ''),
      ];
    }

    // enrich first 20 results with detail for posters
    $items = $this->enrichPosters($items, 20);

    return [
      'items' => $items,
      'page' => $page,
      'total_pages' => (int)($raw['pagecount'] ?? 1),
      'total' => (int)($raw['total'] ?? 0),
    ];
  }

  private function enrichPosters(array $items, int $max): array {
    $n = 0;
    foreach ($items as $i => $it) {
      if ($n >= $max) break;
      if (!empty($it['poster'])) continue;

      $d = $this->detail((string)$it['id']);
      if (!empty($d['poster'])) {
        $items[$i]['poster'] = $d['poster'];
        $items[$i]['year'] = (string)($d['year'] ?? '');
      }
      $n++;
    }
    return $items;
  }

  /** detail: ac=detail&ids=ID */
  public function detail(string $id): array {
    // cache 6 hours
    $cached = $this->cacheGet($id, 21600);
    if ($cached) return $cached;

    $raw = $this->httpGetJson($this->apiUrl(['ac'=>'detail','ids'=>$id]));
    $row = ($raw['list'][0] ?? null);
    if (!is_array($row)) return [];

    $title = (string)($row['vod_name'] ?? '');
    $poster = (string)($row['vod_pic'] ?? '');
    $desc = (string)($row['vod_blurb'] ?? '');
    $year = (string)($row['vod_year'] ?? '');
    $doubanId = (string)($row['vod_douban_id'] ?? ''); // just store, we won’t scrape

    // parse play sources
    $groups = $this->parsePlayGroups((string)($row['vod_play_from'] ?? ''), (string)($row['vod_play_url'] ?? ''));

    // Flatten to “episodes” using best group preference: m3u8 > others
    $bestGroup = $this->pickBestGroup($groups);
    $episodes = [];
    foreach (($bestGroup['episodes'] ?? []) as $idx => $ep) {
      $episodes[] = [
        'ep' => (string)($idx + 1),
        'name' => (string)$ep['name'],
        'url' => (string)$ep['url'],
      ];
    }

    $out = [
      'sourceKey' => $this->key,
      'id' => $id,
      'title' => $title,
      'poster' => $poster,
      'desc' => $desc,
      'year' => $year,
      'douban_id' => $doubanId,
      'groups' => $groups,
      'episodes' => $episodes,
    ];

    $this->cacheSet($id, $out);
    return $out;
  }

  private function parsePlayGroups(string $from, string $url): array {
    $fromParts = array_map('trim', explode('$$$', $from));
    $urlParts  = explode('$$$', $url);

    $groups = [];
    $count = max(count($fromParts), count($urlParts));

    for ($i=0; $i<$count; $i++) {
      $name = (string)($fromParts[$i] ?? ("g{$i}"));
      $block = (string)($urlParts[$i] ?? '');

      $episodes = [];
      $entries = array_filter(explode('#', $block));
      foreach ($entries as $e) {
        $p = explode('$', $e, 2);
        $epName = trim((string)($p[0] ?? ''));
        $epUrl  = trim((string)($p[1] ?? ''));
        if ($epUrl === '') continue;
        $episodes[] = ['name'=>$epName ?: 'EP', 'url'=>$epUrl];
      }

      $groups[] = [
        'name' => $name,
        'episodes' => $episodes,
      ];
    }

    return $groups;
  }

  private function pickBestGroup(array $groups): array {
    if (!$groups) return ['name'=>'', 'episodes'=>[]];

    // prefer any group name containing "m3u8"
    foreach ($groups as $g) {
      if (stripos((string)$g['name'], 'm3u8') !== false) return $g;
    }
    // else first non-empty
    foreach ($groups as $g) {
      if (!empty($g['episodes'])) return $g;
    }
    return $groups[0];
  }

  /** play uses detail episodes (already parsed) */
  public function play(string $id, string $ep): array {
    $d = $this->detail($id);
    $idx = max(0, (int)$ep - 1);
    $eps = $d['episodes'] ?? [];
    $url = $eps[$idx]['url'] ?? '';
    return ['url'=>$url];
  }
}
