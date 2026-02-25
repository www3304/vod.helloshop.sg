<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once APP_ROOT . '/app/Services/SourceManager.php';
require_once APP_ROOT . '/app/Services/RouterService.php';

$bestKey = RouterService::bestProviderKey();
$providers = SourceManager::enabledProviders();

// pick provider
$p = null;
foreach ($providers as $pp) {
  if ($bestKey && method_exists($pp, 'key') && $pp->key() === $bestKey) { 
    $p = $pp; 
    break; 
  }
}
if (!$p && $providers) $p = $providers[0];

// load categories
$cats = [];
if ($p && method_exists($p, 'classes')) {
  $tmp = $p->classes();
  if (is_array($tmp) && count($tmp) > 0) $cats = $tmp;
}

// fallback
if (!$cats) {
  $cats = [
    ['id'=>'1','name'=>'电影'],
    ['id'=>'2','name'=>'电视剧'],
    ['id'=>'3','name'=>'综艺'],
    ['id'=>'4','name'=>'动漫'],
    ['id'=>'6','name'=>'动作片'],
    ['id'=>'7','name'=>'喜剧片'],
    ['id'=>'8','name'=>'爱情片'],
    ['id'=>'9','name'=>'科幻片'],
    ['id'=>'10','name'=>'恐怖片'],
    ['id'=>'11','name'=>'剧情片'],
    ['id'=>'12','name'=>'战争片'],
    ['id'=>'13','name'=>'国产剧'],
  ];
}

$type = (string)($type ?? '');
?>

<div class="brand">VOD Demo</div>

<a class="nav <?= (basename($_SERVER['PHP_SELF'])==='home.php')?'active':'' ?>" href="/home.php">首页</a>
<a class="nav <?= (basename($_SERVER['PHP_SELF'])==='search.php')?'active':'' ?>" href="/search.php">搜索</a>

<div style="margin-top:10px;opacity:.7;font-size:12px;padding:0 10px;">分类</div>

<?php foreach ($cats as $c): ?>
  <?php
    $cid = (string)($c['id'] ?? '');
    $cname = (string)($c['name'] ?? $cid);
    $active = ($cid === $type) ? 'active' : '';
  ?>
  <a class="nav <?= $active ?>" href="/category.php?type=<?= urlencode($cid) ?>">
    <?= htmlspecialchars($cname) ?>
  </a>
<?php endforeach; ?>

<a class="nav" href="/admin/sources.php">源管理</a>