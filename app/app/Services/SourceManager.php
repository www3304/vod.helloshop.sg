<?php
declare(strict_types=1);

require_once APP_ROOT . '/app/Providers/AppleCmsProvider.php';

final class SourceManager
{
    private static function sourcesPath(): string
    {
        // Your actual location
        return STORAGE_DIR . '/sources.json';
    }

    /** Raw rows from sources.json */
    public static function allConfigs(): array
    {
        $path = self::sourcesPath();
        if (!is_file($path)) return [];

        $rows = json_decode((string)file_get_contents($path), true);
        return is_array($rows) ? $rows : [];
    }

    /** Enabled provider instances */
    public static function enabledProviders(): array
    {
        $rows = self::allConfigs();

        $providers = [];
        foreach ($rows as $row) {
            if (empty($row['enabled'])) continue;

            $providers[] = new AppleCmsProvider([
                'key'     => $row['key'] ?? '',
                'api'     => $row['api_base'] ?? '',
                'enabled' => (bool)($row['enabled'] ?? true),
            ]);
        }
        return $providers;
    }
	
	public static function configByKey(string $key): ?array
	{
		foreach (self::allConfigs() as $c) {
			if (($c['key'] ?? '') === $key) return $c;
		}
		return null;
	}

	public static function nameByKey(string $key): string
	{
		$c = self::configByKey($key);
		return (string)($c['name'] ?? $key);
	}

}
