<?php
declare(strict_types=1);

final class RouterService
{
    public static function bestProviderKey(): ?string
    {
        $providers = SourceManager::enabledProviders();
        if (!$providers) return null;

        // configs from sources.json
        $configs = SourceManager::allConfigs();
        $cfgByKey = [];
        foreach ($configs as $c) {
            $k = (string)($c['key'] ?? '');
            if ($k !== '') $cfgByKey[$k] = $c;
        }

        // metrics from storage (optional)
        $metricsPath = STORAGE_DIR . '/metrics.json';
        $metrics = [];
        if (is_file($metricsPath)) {
            $m = json_decode((string)file_get_contents($metricsPath), true);
            if (is_array($m)) $metrics = $m;
        }

        $bestKey = null;
        $bestScore = -999999999;

        foreach ($providers as $p) {
            $key = $p->key();

            $cfg = $cfgByKey[$key] ?? [];
            $m = $metrics[$key] ?? [];

            $priority = (int)($cfg['priority'] ?? 0);
            $lat = (int)($m['lat_ewma'] ?? 1200);
            $fail = (int)($m['fail'] ?? 0);
            $ok = (int)($m['ok'] ?? 0);

            $neverOk = ($ok === 0);

            // scoring: priority is strong, latency matters, failures punish a lot
            $score = 0;
            $score += $priority * 10;
            $score -= $lat;
            $score -= $fail * 200;
            if ($neverOk) $score -= 5000;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKey = $key;
            }
        }

        return $bestKey;
    }
}
