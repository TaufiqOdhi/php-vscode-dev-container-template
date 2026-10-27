<?php

declare(strict_types=1);

function environment(string $name, string $default): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $default : $value;
}

$databaseVersion = null;
$phpMyAdminPort = filter_var(
    environment('PHPMYADMIN_PORT', '8081'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 65535]],
) ?: 8081;

try {
    $host = environment('DB_HOST', 'db');
    $port = environment('DB_PORT', '3306');
    $database = environment('DB_DATABASE', 'app');
    $username = environment('DB_USERNAME', 'app');
    $password = environment('DB_PASSWORD', 'app_password');

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3],
    );
    $databaseVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP development environment</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172b4d; background: #f3f6fb; }
        body { margin: 0; }
        main { max-width: 760px; margin: 10vh auto; padding: 0 24px 40px; }
        .eyebrow { color: #536fba; font-size: .8rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
        h1 { font-size: clamp(2rem, 6vw, 3.3rem); line-height: 1.1; margin: 12px 0; }
        p { line-height: 1.6; }
        .lead { color: #52627d; font-size: 1.1rem; }
        .card { background: #fff; border: 1px solid #dce4f0; border-radius: 12px; margin-top: 24px; padding: 20px 24px; }
        .status { color: #12633d; font-weight: 700; }
        .error { color: #a3342b; }
        code { background: #edf1f8; border-radius: 4px; padding: .15em .35em; }
        a { color: #3159a8; }
    </style>
</head>
<body>
<main>
    <div class="eyebrow">Docker &middot; PHP &middot; VS Code</div>
    <h1>Your PHP workspace is ready.</h1>
    <p class="lead">Apache is serving this page from <code>public/index.php</code>. Edit it and refresh your browser to get started.</p>

    <section class="card" aria-labelledby="status-heading">
        <h2 id="status-heading">Environment</h2>
        <p>PHP <strong><?= escape(PHP_VERSION) ?></strong></p>
        <?php if ($databaseVersion !== null): ?>
            <p class="status">Database connected &middot; <?= escape($databaseVersion) ?></p>
        <?php else: ?>
            <p class="status error">Database connection unavailable</p>
            <p>Check the application and database logs with <code>docker compose logs app db</code>.</p>
        <?php endif; ?>
    </section>

    <p>Open phpMyAdmin at <a href="http://localhost:<?= $phpMyAdminPort ?>">localhost:<?= $phpMyAdminPort ?></a> using the database credentials in <code>.env</code>.</p>
    <p>See <code>README.md</code> for setup, database access, Composer, and debugging.</p>
</main>
</body>
</html>
