<?php

class MigrationCommand
{
    public static $command = 'migration';
    public static $description = 'Manage database migrations';
    public static $arguments = ['[action]' => 'run, create-migration, rollback, rollback-all, refresh, status, seed-admin', '[name]' => 'Migration name'];

    public function handle($action = null, array $flags = [])
    {
        $routes = [
            'run' => 'migrate', 'create-migration' => 'create-migration',
            'rollback' => 'rollback', 'rollback-all' => 'rollback-all',
            'refresh' => 'refresh', 'status' => 'status', 'seed-admin' => 'seed-admin',
        ];
        $action = $action ?? 'run';
        if (!isset($routes[$action])) {
            fwrite(STDERR, "Unknown migration action.\n");
            exit(1);
        }
        $route = $routes[$action];
        if ($action === 'create-migration') {
            $name = $flags['name'] ?? ($GLOBALS['argv'][3] ?? '');
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                fwrite(STDERR, "Example: php lava migration create-migration create_products_table\n");
                exit(1);
            }
            $route .= '/' . $name;
        }
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(PUBLIC_DIR . 'index.php') . ' ' . escapeshellarg($route), $code);
        exit($code);
    }
}
