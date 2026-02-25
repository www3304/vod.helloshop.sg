<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_ROOT . '/app/Services/SourceManager.php';
require_once APP_ROOT . '/app/Services/ProbeService.php';

ProbeService::runOnce();
header('Location: /');
exit;
