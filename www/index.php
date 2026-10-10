<?php

// Smoke test for the clamp environment.
// Edit this file and reload http://localhost:8080 to see the changes.

// ?info=1 prints the full phpinfo() page — handy to explore this PHP install.
if (isset($_GET['info'])) {
    phpinfo();
    exit;
}

// Values set for the "web" service in compose.yml (which reads them from .env).
$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');
$adminerPort = getenv('ADMINER_PORT') ?: '8081';

// Connect to MariaDB with PDO (driver installed in php/Dockerfile).
$dbStatus = 'error';
$dbVersion = 'not connected';
try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $dbVersion = $pdo->query('SELECT VERSION()')->fetchColumn();
    $dbStatus = 'ok';
} catch (PDOException $e) {
    $dbVersion = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>clamp — LAMP study environment</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem auto; max-width: 42rem; line-height: 1.5; }
        code { background: #f0f0f0; padding: 0.1rem 0.3rem; border-radius: 4px; }
        .ok { color: #0a0; font-weight: bold; }
        .error { color: #a00; font-weight: bold; }
    </style>
</head>
<body>
    <h1>clamp is up</h1>
    <p>PHP <code><?= htmlspecialchars(PHP_VERSION) ?></code> on Apache, serving
       files from <code>./www</code> through a bind mount.</p>

    <h2>Database</h2>
    <?php if ($dbStatus === 'ok'): ?>
        <p class="ok">Connected to MariaDB <?= htmlspecialchars($dbVersion) ?></p>
        <p>Database <code><?= htmlspecialchars($dbName) ?></code>,
           user <code><?= htmlspecialchars($dbUser) ?></code>,
           host <code><?= htmlspecialchars($dbHost) ?></code>
           (all from <code>.env</code>).</p>
    <?php else: ?>
        <p class="error">Database connection failed</p>
        <p><code><?= htmlspecialchars((string) $dbVersion) ?></code></p>
    <?php endif; ?>

    <p>Browse it with Adminer:
       <a href="http://localhost:<?= htmlspecialchars($adminerPort) ?>">http://localhost:<?= htmlspecialchars($adminerPort) ?></a>
       (server <code>db</code>, same credentials).</p>

    <h2>Keep exploring</h2>
    <ul>
        <li><a href="?info=1">Full PHP configuration (phpinfo)</a></li>
        <li>Create <code>www/hello.php</code>, write some PHP, open <a href="/hello.php">/hello.php</a></li>
    </ul>
</body>
</html>
