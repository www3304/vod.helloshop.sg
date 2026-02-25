<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once APP_ROOT . '/app/Services/SourceManager.php';
require_once APP_ROOT . '/app/Services/ProbeService.php';
require_once APP_ROOT . '/app/Services/RouterService.php';

$best = RouterService::bestProviderKey();
$metrics = ProbeService::getMetrics();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title>VOD Demo</title>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand">VOD Demo</div>
      <a class="nav active" href="/home.php">首页</a>
      <a class="nav" href="/search.php">搜索</a>
      <a class="nav" href="/admin/sources.php">源管理</a>
    </aside>

    <main class="main">
      <div class="topbar">
        <form action="/search.php" method="get" class="searchbar">
          <input name="q" placeholder="搜索影片 / 剧集..." />
          <button>搜索</button>
        </form>
        <div class="pill">Best Source: <?= h($best ?? 'N/A') ?></div>
      </div>

      <h2 class="h2">Sources Status</h2>
      <div class="card">
        <table class="table">
          <thead><tr><th>Key</th><th>OK</th><th>Fail</th><th>Latency EWMA (ms)</th><th>Last OK</th></tr></thead>
          <tbody>
            <?php foreach (SourceManager::allConfigs() as $s): 
              $m = $metrics[$s['key']] ?? ['ok'=>0,'fail'=>0,'lat_ewma'=>null,'last_ok_ts'=>0];
            ?>
              <tr>
                <td><?= h($s['key']) ?></td>
                <td><?= (int)$m['ok'] ?></td>
                <td><?= (int)$m['fail'] ?></td>
                <td><?= h((string)($m['lat_ewma'] ?? '-')) ?></td>
                <td><?= $m['last_ok_ts'] ? date('Y-m-d H:i:s', (int)$m['last_ok_ts']) : '-' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="actions">
          <a class="btn" href="/admin/probe.php">Run Probe Now</a>
        </div>
      </div>

      <p class="muted">
        Next step: connect your client-owned provider APIs to <code>GenericJsonProvider.php</code> mapping.
      </p>
    </main>
  </div>
</body>
</html>
