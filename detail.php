<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once APP_ROOT . '/app/Services/SourceManager.php';
require_once APP_ROOT . '/app/Services/RouterService.php';

$source = (string)($_GET['source'] ?? '');
$id = (string)($_GET['id'] ?? '');
if ($source === '' || $id === '') die("Missing source or id");

$providers = SourceManager::enabledProviders();
$p = null;
foreach ($providers as $pp) {
  if ($pp->key() === $source) { $p = $pp; break; }
}
if (!$p) die("Provider not found: " . h($source));

$sourceName = SourceManager::nameByKey($source);
$d = $p->detail($id);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/app.css">
  <title><?=h($d['title'] ?? 'Detail')?></title>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="brand">VOD Demo</div>
    <a class="nav" href="/home.php">首页</a>
    <a class="nav" href="/search.php">搜索</a>
    <a class="nav" href="/admin/sources.php">源管理</a>
  </aside>

  <main class="main">
    <div class="topbar" style="margin-bottom:12px;">
      <div class="pill">Source: <?=h($sourceName)?></div>
    </div>

    <h2 class="h2"><?=h($d['title'] ?? '')?></h2>

    <?php if (!empty($d['poster'])): ?>
      <img src="<?=h($d['poster'])?>" style="width:220px;max-width:55vw;border-radius:16px">
    <?php endif; ?>

    <p class="muted"><?=h($d['desc'] ?? '')?></p>

    <h3 class="h2">播放列表</h3>
    <?php foreach (($d['episodes'] ?? []) as $ep): ?>
      <div style="margin:8px 0">
        <a class="btn" href="/play.php?source=<?=urlencode($source)?>&id=<?=urlencode($id)?>&ep=<?=urlencode($ep['ep'])?>">
          <?=h($ep['name'] ?? $ep['ep'] ?? 'Play')?>
        </a>
      </div>
    <?php endforeach; ?>
  </main>
</div>
</body>
</html>
