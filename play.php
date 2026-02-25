<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once APP_ROOT . '/app/Services/ProviderRegistry.php';

$source = $_GET['source'] ?? '';
$id = $_GET['id'] ?? '';
$ep = (string)($_GET['ep'] ?? '1');

$p = ProviderRegistry::byKey($source);
if (!$p) die("Provider not found");

$play = $p->play($id, $ep);
$stream = $play['url'] ?? '';
if (!$stream) die("No stream URL returned");

// ✅ Read ads once
$ads = function_exists('getAds') ? (getAds() ?? []) : [];

// popup config
$popup = $ads['player_popup'] ?? null;
$delay = (int)($popup['delay_seconds'] ?? 0);

// preroll config
$preroll = $ads['player_preroll'] ?? null;
$prVideo = $preroll['video'] ?? '';
$prLink  = $preroll['link'] ?? '#';
$skipAfter = (int)($preroll['skip_after_seconds'] ?? 5);
$maxSeconds = (int)($preroll['max_seconds'] ?? 15);
$muted = !empty($preroll['muted']);

$cooldownMinutes = (int)($preroll['cooldown_minutes'] ?? 15);
$cooldownMs = max(0, $cooldownMinutes) * 60 * 1000;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Play</title>
  <style>
    body{margin:20px;font-family:system-ui}
  </style>
</head>
<body>
<h2>Playing</h2>
<video id="v" width="960" controls playsinline></video>

<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
const video = document.getElementById('v');
const src = <?=json_encode($stream)?>;

if (Hls.isSupported()) {
  const hls = new Hls();
  hls.loadSource(src);
  hls.attachMedia(video);
} else {
  video.src = src;
}
</script>

<?php if (!empty($popup) && !empty($popup['image'])): ?>
  <!-- ✅ Popup Ad -->
  <div id="popupAd"
       style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);
              justify-content:center;align-items:center;z-index:99999;padding:18px;">
    <div style="position:relative;max-width:720px;width:100%;">
      <a href="<?=h($popup['link'] ?? '#')?>"
         target="_blank" rel="nofollow noopener">
        <img src="<?=h($popup['image'])?>"
             alt="Advertisement"
             style="width:100%;border-radius:14px;display:block;max-height:80vh;object-fit:contain;">
      </a>

      <button id="closeAd"
              style="position:absolute;top:-12px;right:-12px;width:38px;height:38px;border:0;
                     border-radius:999px;cursor:pointer;font-size:18px;line-height:38px;">
        ✕
      </button>
    </div>
  </div>

  <script>
  (function () {
    var ad = document.getElementById('popupAd');
    var closeBtn = document.getElementById('closeAd');
    if (!ad || !closeBtn) return;

    var KEY = 'vod_popup_last_shown';
    var COOLDOWN_MS = 5 * 60 * 1000; // ✅ 5 minutes
    var now = Date.now();
    var last = parseInt(localStorage.getItem(KEY) || '0', 10);

    function closeAd() { ad.style.display = 'none'; }

    closeBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeAd();
    });

    ad.addEventListener('click', function (e) {
      if (e.target === ad) closeAd();
    });

    if (last && (now - last) < COOLDOWN_MS) return;

    setTimeout(function () {
      ad.style.display = 'flex';
      localStorage.setItem(KEY, String(Date.now()));
    }, <?= max(0, $delay) * 1000 ?>);
  })();
  </script>
<?php endif; ?>

<?php if (!empty($prVideo)): ?>
  <!-- ✅ Pre-roll Video Ad -->
  <div id="prerollWrap" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);
    z-index:100000;justify-content:center;align-items:center;padding:16px;">
    <div style="width:min(960px, 100%);">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <div style="color:#fff;font-weight:600;">Advertisement</div>
        <button id="prerollSkip" disabled
          style="padding:8px 12px;border:0;border-radius:10px;cursor:pointer;">
          Skip in <?= $skipAfter ?>
        </button>
      </div>

      <a href="<?=h($prLink)?>" target="_blank" rel="nofollow noopener" style="display:block;">
        <video id="prerollVideo" playsinline
          style="width:100%;border-radius:14px;background:#000;" <?= $muted ? 'muted' : '' ?>></video>
      </a>
    </div>
  </div>

  <script>
  (function () {
    var mainVideo = document.getElementById('v');
    var wrap = document.getElementById('prerollWrap');
    var pv = document.getElementById('prerollVideo');
    var skipBtn = document.getElementById('prerollSkip');
    if (!mainVideo || !wrap || !pv || !skipBtn) return;

    var KEY = 'vod_preroll_last_shown';
    var now = Date.now();
    var last = parseInt(localStorage.getItem(KEY) || '0', 10);
    var COOLDOWN = <?= (int)$cooldownMs ?>;

    function startMain() {
      wrap.style.display = 'none';
      try { pv.pause(); } catch(e) {}
      try { mainVideo.play(); } catch(e) {}
    }

    if (last && COOLDOWN > 0 && (now - last) < COOLDOWN) {
      startMain();
      return;
    }

    try { mainVideo.pause(); } catch(e) {}

    wrap.style.display = 'flex';
    pv.src = <?= json_encode($prVideo) ?>;
    pv.currentTime = 0;

    pv.play().catch(function(){ /* autoplay may be blocked */ });

    localStorage.setItem(KEY, String(Date.now()));

    var remain = <?= (int)$skipAfter ?>;
    function tick() {
      if (remain <= 0) {
        skipBtn.disabled = false;
        skipBtn.textContent = 'Skip Ad';
        return;
      }
      skipBtn.textContent = 'Skip in ' + remain;
      remain--;
      setTimeout(tick, 1000);
    }
    tick();

    skipBtn.addEventListener('click', function (e) {
      e.preventDefault();
      if (skipBtn.disabled) return;
      startMain();
    });

    pv.addEventListener('ended', startMain);

    var MAX = <?= (int)$maxSeconds ?>;
    if (MAX > 0) {
      setTimeout(function(){ startMain(); }, MAX * 1000);
    }
  })();
  </script>
<?php endif; ?>

</body>
</html>
