<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once APP_ROOT . '/app/Services/SourceManager.php';
require_once APP_ROOT . '/app/Services/RouterService.php';

$q = trim((string)($_GET['q'] ?? ''));

$items = [];
$bestKey = RouterService::bestProviderKey();

if ($q !== '') {
  $providers = SourceManager::enabledProviders();

  // Put best provider first
  usort($providers, fn($a,$b)=>
    ($a->key()===$bestKey ? -1 : 0) <=> ($b->key()===$bestKey ? -1 : 0)
  );

  foreach ($providers as $p) {
    $res = $p->search($q, 1);
    $items = $res['items'] ?? [];
    if (!empty($items)) break;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/app.css">
  <title>Search</title>
  <style>
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;}
    .card{display:block;text-decoration:none;color:#e5e7eb;position:relative}
    .poster{width:100%;aspect-ratio:2/3;object-fit:cover;border-radius:14px;background:#111827}
    .t{margin-top:8px;font-weight:650}
    .m{margin-top:4px;font-size:12px;color:#9ca3af}
  </style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
   <?php $type = ''; include __DIR__ . '/sidebar.php'; ?>
  </aside>

  <main class="main">
    <div class="topbar">
      <form class="searchbar" action="/search.php" method="get">
        <input name="q" value="<?=h($q)?>" placeholder="搜索影片 / 剧集..." />
        <button>搜索</button>
      </form>
      <div class="pill">Best Source: <?=h($bestKey ?: '-')?></div>
    </div>

    <?php if ($q !== ''): ?>
      <div style="margin:14px 0;color:#9ca3af;">Results for: <b><?=h($q)?></b></div>
    <?php endif; ?>

    <div class="grid">
      <?php foreach($items as $it): ?>
        <a class="card" href="/detail.php?source=<?=urlencode($it['sourceKey'])?>&id=<?=urlencode($it['id'])?>">
          <img class="poster" src="<?=h($it['poster'] ?: '')?>" alt="">
          <div class="t"><?=h($it['title'] ?: '')?></div>
          <div class="m"><?=h(($it['year'] ?: '') . ' ' . ($it['type'] ?: '') . ' ' . ($it['remark'] ?: ''))?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </main>
</div>
</body>
</html>
