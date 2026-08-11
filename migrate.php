<?php
// GO-FITNESS migration runner CLI: php migrate.php
require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/config.php';
$db = $config['db'];
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}", $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(180) NOT NULL UNIQUE, executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$done = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
foreach (glob(__DIR__.'/database/migrations/*.sql') as $file) {
    $name = basename($file, '.sql');
    if (in_array($name, $done, true)) { echo "SKIP $name\n"; continue; }
    $sql = file_get_contents($file);
    $pdo->exec($sql);
    $st = $pdo->prepare('INSERT IGNORE INTO migrations(migration) VALUES(?)');
    $st->execute([$name]);
    echo "OK $name\n";
}
