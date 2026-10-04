<?php
$settings = [];
foreach (file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (preg_match('/^([A-Z_]+)=(.*)$/', $line, $match)) {
        $settings[$match[1]] = trim($match[2], "\"'");
    }
}
$ca = $argv[2] ?? ($settings['DB_SSL_CA'] ?? '');
if (!is_file($ca)) {
    fwrite(STDERR, "Aiven CA certificate file is missing.\n");
    exit(1);
}
$sslCa = defined('Pdo\\Mysql::ATTR_SSL_CA') ? constant('Pdo\\Mysql::ATTR_SSL_CA') : constant('PDO::MYSQL_ATTR_SSL_CA');
$sslVerify = defined('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') ? constant('Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') : constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT');
try {
    $db = new PDO(
        'mysql:host=' . $settings['DB_HOST'] . ';port=' . $settings['DB_PORT'] . ';dbname=' . $settings['DB_DATABASE'] . ';charset=utf8mb4',
        $settings['DB_USERNAME'] ?: ($settings['DB_USER'] ?? ''),
        $settings['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, $sslCa => $ca, $sslVerify => true]
    );
    echo "Aiven connection verified with TLS.\n";
    if (in_array('--prepare-lab6', $argv, true)) {
        $db->exec('CREATE DATABASE IF NOT EXISTS lab6db CHARACTER SET utf8mb4');
        echo "Dedicated lab6db database is ready.\n";
    }
    foreach ($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        echo $table . ': ';
        $columns = $db->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_COLUMN);
        echo implode(', ', $columns) . PHP_EOL;
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
