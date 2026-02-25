<?php
require_once __DIR__ . '/auth.php';
require_login();
?>


<?php
require_once __DIR__ . '/../app/bootstrap.php';

$adsFile = APP_ROOT . '/storage/ads.json';

// 读取现有广告
$ads = [];
if (file_exists($adsFile)) {
    $ads = json_decode(file_get_contents($adsFile), true) ?? [];
}

// 保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ads = [
        "homepage_banner" => [
            "type" => "image",
            "image" => $_POST['homepage_image'] ?? '',
            "link"  => $_POST['homepage_link'] ?? ''
        ],
        "list_inline" => [
            "type" => "image",
            "image" => $_POST['list_image'] ?? '',
            "link"  => $_POST['list_link'] ?? '',
            "interval" => (int)($_POST['list_interval'] ?? 6)
        ],
        "player_popup" => [
            "type" => "image",
            "image" => $_POST['popup_image'] ?? '',
            "link"  => $_POST['popup_link'] ?? '',
            "delay_seconds" => (int)($_POST['popup_delay'] ?? 1)
        ],
        "player_preroll" => [
            "type" => "video",
            "video" => $_POST['preroll_video'] ?? '',
            "link"  => $_POST['preroll_link'] ?? '',
            "skip_after_seconds" => (int)($_POST['preroll_skip'] ?? 5),
            "max_seconds" => (int)($_POST['preroll_max'] ?? 15),
            "muted" => isset($_POST['preroll_muted']),
            "cooldown_minutes" => (int)($_POST['preroll_cooldown'] ?? 15)
        ]
    ];

    file_put_contents($adsFile, json_encode($ads, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $saved = true;
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Ads Manager</title>
<style>
body{font-family:system-ui;margin:30px;max-width:900px}
input{width:100%;padding:8px;margin:6px 0}
label{font-weight:600}
button{padding:10px 16px;margin-top:10px}
.section{border:1px solid #ddd;padding:20px;margin-bottom:20px;border-radius:10px}
.success{background:#e6ffed;padding:10px;border-radius:8px;margin-bottom:20px}
</style>
</head>
<body>

<h1>Ads Management</h1>

<?php if (!empty($saved)): ?>
<div class="success">Saved successfully!</div>
<?php endif; ?>

<form method="post">

<div class="section">
<h3>Homepage Banner</h3>
<label>Image URL</label>
<input name="homepage_image" value="<?= htmlspecialchars($ads['homepage_banner']['image'] ?? '') ?>">
<label>Link</label>
<input name="homepage_link" value="<?= htmlspecialchars($ads['homepage_banner']['link'] ?? '') ?>">
</div>

<div class="section">
<h3>List Inline</h3>
<label>Image URL</label>
<input name="list_image" value="<?= htmlspecialchars($ads['list_inline']['image'] ?? '') ?>">
<label>Link</label>
<input name="list_link" value="<?= htmlspecialchars($ads['list_inline']['link'] ?? '') ?>">
<label>Interval (every N items)</label>
<input name="list_interval" value="<?= htmlspecialchars($ads['list_inline']['interval'] ?? 6) ?>">
</div>

<div class="section">
<h3>Popup Ad</h3>
<label>Image URL</label>
<input name="popup_image" value="<?= htmlspecialchars($ads['player_popup']['image'] ?? '') ?>">
<label>Link</label>
<input name="popup_link" value="<?= htmlspecialchars($ads['player_popup']['link'] ?? '') ?>">
<label>Delay Seconds</label>
<input name="popup_delay" value="<?= htmlspecialchars($ads['player_popup']['delay_seconds'] ?? 1) ?>">
</div>

<div class="section">
<h3>Pre-roll Video</h3>
<label>Video URL</label>
<input name="preroll_video" value="<?= htmlspecialchars($ads['player_preroll']['video'] ?? '') ?>">
<label>Link</label>
<input name="preroll_link" value="<?= htmlspecialchars($ads['player_preroll']['link'] ?? '') ?>">
<label>Skip After Seconds</label>
<input name="preroll_skip" value="<?= htmlspecialchars($ads['player_preroll']['skip_after_seconds'] ?? 5) ?>">
<label>Max Seconds</label>
<input name="preroll_max" value="<?= htmlspecialchars($ads['player_preroll']['max_seconds'] ?? 15) ?>">
<label>Cooldown Minutes</label>
<input name="preroll_cooldown" value="<?= htmlspecialchars($ads['player_preroll']['cooldown_minutes'] ?? 15) ?>">
<label>
<input type="checkbox" name="preroll_muted" <?= !empty($ads['player_preroll']['muted']) ? 'checked' : '' ?>>
 Muted autoplay
</label>
</div>

<button type="submit">Save</button>

</form>

<div style="margin-bottom:20px;">
Logged in as: <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong>
(<?= htmlspecialchars($_SESSION['user']['role']) ?>)
|
<a href="/admin/logout.php">Logout</a>
</div>
</body>
</html>
