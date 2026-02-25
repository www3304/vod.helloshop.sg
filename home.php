<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once APP_ROOT . '/app/Services/SourceManager.php';
require_once APP_ROOT . '/app/Services/RouterService.php';

$bestKey = RouterService::bestProviderKey();

$providers = SourceManager::enabledProviders();
$p = null;
foreach ($providers as $pp) {
  if ($bestKey && $pp->key() === $bestKey) { $p = $pp; break; }
}
if (!$p && $providers) $p = $providers[0];

$items = [];
if ($p) {
  $res = $p->latest(1);
  $items = $res['items'] ?? [];
}

// ✅ Ads config
$ads = function_exists('getAds') ? (getAds() ?? []) : [];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/app.css">
  <title>Home</title>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
   <?php $type = ''; include __DIR__ . '/sidebar.php'; ?>
  </aside>

  <main class="main">
    <div class="topbar">
      <form class="searchbar" action="/search.php" method="get">
        <input name="q" placeholder="搜索影片 / 剧集..." />
        <button>搜索</button>
      </form>

      <div class="pill">
        Best Source: <?=h($bestKey ? SourceManager::nameByKey($bestKey) : '-')?>
      </div>
    </div>

    <?php if (!empty($ads['homepage_banner'])): ?>
      <div style="margin: 16px 0;">
        <a href="<?=h($ads['homepage_banner']['link'] ?? '#')?>" target="_blank" rel="nofollow noopener">
          <img
            src="<?=h($ads['homepage_banner']['image'] ?? '')?>"
            alt="Advertisement"
            style="width:100%;max-height:220px;object-fit:cover;border-radius:14px;display:block;"
          >
        </a>
      </div>
    <?php endif; ?>

    <h2 class="h2">热门</h2>

    <div class="grid">
      <?php
        $interval = (int)($ads['list_inline']['interval'] ?? 0);
        $counter = 0;
      ?>

      <?php foreach($items as $it): ?>
        <?php
          $counter++;
          $srcName = SourceManager::nameByKey((string)($it['sourceKey'] ?? ''));
        ?>
        <a class="card" href="/detail.php?source=<?=urlencode($it['sourceKey'])?>&id=<?=urlencode($it['id'])?>">
          <img class="poster" src="<?=h($it['poster'])?>" alt="">
          <div class="t"><?=h($it['title'])?></div>
          <div class="src"><?=h($srcName)?></div>
        </a>

        <?php if ($interval > 0 && !empty($ads['list_inline']) && ($counter % $interval === 0)): ?>
          <a
            class="card"
            href="<?=h($ads['list_inline']['link'] ?? '#')?>"
            target="_blank"
            rel="nofollow noopener"
            style="text-decoration:none;"
          >
            <img
              class="poster"
              src="<?=h($ads['list_inline']['image'] ?? '')?>"
              alt="Advertisement"
              style="object-fit:cover;"
            >
            <div class="t">Sponsored</div>
            <div class="src">Advertisement</div>
          </a>
        <?php endif; ?>

      <?php endforeach; ?>
    </div>
  </main>
</div>
</body>
</html>
