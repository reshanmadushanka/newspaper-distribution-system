<?php
try {
    $pdo = new PDO('pgsql:host=127.0.0.1;port=5368;dbname=new_papers', 'postgres', '');
    echo 'Connected successfully' . PHP_EOL;
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage() . PHP_EOL;
}
