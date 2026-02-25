<?php
require_once __DIR__ . '/auth.php';
require_role('superadmin');

$path = STORAGE_DIR . '/sources.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw = $_POST['json'] ?? '';
  $data = json_decode($raw, true);
  if (!is_array($data)) {
    $err = "Invalid JSON";
  } else {
    json_write($path, $data);
    $ok = "Saved!";
  }
}

$current = file_exists($path) ? file_get_contents($path) : "[]";
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title>Sources</title>
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="brand">VOD Demo</div>
      <a class="nav" href="/">首页</a>
      <a class="nav" href="/search.php">搜索</a>
      <a class="nav active" href="/admin/sources.php">源管理</a>
    </aside>

    <main class="main">
      <h2 class="h2">Edit sources.json</h2>
      <?php if (!empty($err)): ?><div class="alert bad"><?= h($err) ?></div><?php endif; ?>
      <?php if (!empty($ok)): ?><div class="alert ok"><?= h($ok) ?></div><?php endif; ?>

      <form method="post">
        <textarea name="json" class="textarea" spellcheck="false"><?= h($current) ?></textarea>
        <div class="actions">
          <button class="btn" type="submit">Save</button>
          <a class="btn ghost" href="/">Back</a>
        </div>
      </form>
    </main>
  </div>
</body>
</html>
