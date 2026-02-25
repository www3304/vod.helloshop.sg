<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once APP_ROOT . '/app/Services/SourceManager.php';
require_once APP_ROOT . '/app/Services/RouterService.php';

$type = (string)($_GET['type'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

$bestKey = RouterService::bestProviderKey();
$providers = SourceManager::enabledProviders();

// pick provider: best -> first
$p = null;
foreach ($providers as $pp) {
  if ($bestKey && method_exists($pp, 'key') && $pp->key() === $bestKey) { $p = $pp; break; }
}
if (!$p && $providers) $p = $providers[0];

// filters
$filters = [
  'by'   => (string)($_GET['by'] ?? ''),   // hits / time
  'year' => (string)($_GET['year'] ?? ''),
  'area' => (string)($_GET['area'] ?? ''),
];

// load categories (auto if provider supports classes())
$cats = [];
if ($p && method_exists($p, 'classes')) {
  $tmp = $p->classes();
  if (is_array($tmp) && count($tmp) > 0) $cats = $tmp;
}
// fallback categories if API doesn't provide classes
if (!$cats) {
  $cats = [
    ['id' => '1', 'name' => '电影'],
    ['id' => '2', 'name' => '连续剧'],
    ['id' => '3', 'name' => '综艺'],
    ['id' => '4', 'name' => '动漫'],
  ];
}

// ensure default type if missing
if ($type === '' && $cats) {
  $type = (string)($cats[0]['id'] ?? '');
}

// category name
$catName = $type !== '' ? $type : 'Category';
foreach ($cats as $c) {
  if ((string)($c['id'] ?? '') === (string)$type) { $catName = (string)($c['name'] ?? $type); break; }
}

// ✅ helper: keep querystring when clicking pills / pagination
function q(array $override = []): string {
  $p = $_GET;

  foreach ($override as $k => $v) {
    if ($v === null || $v === '') unset($p[$k]);
    else $p[$k] = $v;
  }

  // clicking filters resets page
  if (array_key_exists('by', $override) || array_key_exists('year', $override) || array_key_exists('area', $override)) {
    $p['page'] = 1;
  }

  return '?' . http_build_query($p, '', '&', PHP_QUERY_RFC3986);
}

// get list by type (and filters if provider supports 3rd param)
$items = [];
$totalPages = 1;

if ($p && $type !== '' && method_exists($p, 'listByType')) {
  try {
    $ref = new ReflectionMethod($p, 'listByType');
    if ($ref->getNumberOfParameters() >= 3) {
      $res = $p->listByType($type, $page, $filters);
    } else {
      $res = $p->listByType($type, $page);
    }
  } catch (Throwable $e) {
    // fallback without reflection in case host blocks it
    $res = $p->listByType($type, $page);
  }

  $items = $res['items'] ?? [];
  $totalPages = (int)($res['total_pages'] ?? 1);
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/app.css">
  <title><?= htmlspecialchars($catName) ?> - Category</title>

  <style>
    .pager { display:flex; gap:10px; align-items:center; justify-content:flex-end; margin-top:16px; }
    .pager a, .pager span {
      display:inline-flex; align-items:center; justify-content:center;
      padding:8px 12px; border-radius:10px; text-decoration:none;
      border:1px solid rgba(255,255,255,.10); color:inherit;
      opacity:.95;
    }
    .pager .disabled { opacity:.45; pointer-events:none; }
    .cat-title { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .cat-title .h2 { margin:0; }
    .pill { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; text-decoration:none;
      border:1px solid rgba(255,255,255,.10); }
  </style>
</head>

<body>
<div class="layout">
  <aside class="sidebar">
    <?php include __DIR__ . '/sidebar.php'; ?>
  </aside>

  <main class="main">
    <div class="topbar">
      <form class="searchbar" action="/search.php" method="get">
        <input name="q" placeholder="搜索影片 / 剧集..." />
        <button>搜索</button>
      </form>

      <div class="pill">
        Best Source: <?= htmlspecialchars($bestKey ? SourceManager::nameByKey($bestKey) : '-') ?>
      </div>
    </div>

    <div class="cat-title">
      <h2 class="h2"><?= htmlspecialchars($catName) ?></h2>

      <div class="pager">
        <a class="<?= ($page <= 1 ? 'disabled' : '') ?>" href="<?= q(['page'=>max(1, $page-1)]) ?>">Prev</a>
        <span>Page <?= (int)$page ?> / <?= max(1, (int)$totalPages) ?></span>
        <a class="<?= ($page >= $totalPages ? 'disabled' : '') ?>" href="<?= q(['page'=>$page+1]) ?>">Next</a>
      </div>
    </div>

    <!-- Filters -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin:10px 0;">
      <a class="pill" href="<?= q(['by'=>'hits']) ?>">🔥 热播</a>
      <a class="pill" href="<?= q(['by'=>'time']) ?>">🆕 最新</a>

      <a class="pill" href="<?= q(['year'=>'2024']) ?>">2024</a>
      <a class="pill" href="<?= q(['year'=>'2023']) ?>">2023</a>
      <a class="pill" href="<?= q(['year'=>'2022']) ?>">2022</a>

      <a class="pill" href="<?= q(['area'=>'大陆']) ?>">大陆</a>
      <a class="pill" href="<?= q(['area'=>'欧美']) ?>">欧美</a>
      <a class="pill" href="<?= q(['area'=>'台湾']) ?>">台湾</a>
      <a class="pill" href="<?= q(['area'=>'香港']) ?>">香港</a>

      <a class="pill" href="<?= q(['by'=>null,'year'=>null,'area'=>null]) ?>">清除</a>
    </div>

    <div class="grid">
      <?php if (!$items): ?>
        <div style="opacity:.75;padding:18px;">No items found.</div>
      <?php endif; ?>

      <?php foreach($items as $it): ?>
        <?php $srcName = SourceManager::nameByKey((string)($it['sourceKey'] ?? '')); ?>
        <a class="card" href="/detail.php?source=<?= urlencode((string)($it['sourceKey'] ?? '')) ?>&id=<?= urlencode((string)($it['id'] ?? '')) ?>">
          <img class="poster" src="<?= htmlspecialchars((string)($it['poster'] ?? '')) ?>" alt="">
          <div class="t"><?= htmlspecialchars((string)($it['title'] ?? '')) ?></div>
          <div class="src"><?= htmlspecialchars($srcName) ?></div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="pager" style="justify-content:center;margin-top:22px;">
      <a class="<?= ($page <= 1 ? 'disabled' : '') ?>" href="<?= q(['page'=>max(1, $page-1)]) ?>">Prev</a>
      <span>Page <?= (int)$page ?> / <?= max(1, (int)$totalPages) ?></span>
      <a class="<?= ($page >= $totalPages ? 'disabled' : '') ?>" href="<?= q(['page'=>$page+1]) ?>">Next</a>
    </div>

  </main>
</div>
</body>
</html>