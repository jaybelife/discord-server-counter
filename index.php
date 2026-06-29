<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$guilds = [];
$bot = null;
$error = null;
$token = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'CSRF Fehler';
    } else {
        $token = trim($_POST['token'] ?? '');

        if ($token === '') {
            $error = 'Bitte Token eingeben';
        } else {
            // Fetch bot info and application info in parallel to save time
            $results = multiFetch([
                'bot' => $appConfig['discord_api_base'] . '/users/@me',
                'app' => $appConfig['discord_api_base'] . '/applications/@me'
            ], $token);

            $botRes = $results['bot'] ?? null;
            $appRes = $results['app'] ?? null;

            if ($botRes && !$botRes['error']) {
                $bot = json_decode($botRes['body'], true);
                if ($bot && isset($bot['id'])) {
                    $_SESSION['bot_token'] = $token;
                    $_SESSION['bot_id'] = $bot['id'];

                    if ($appRes && !$appRes['error']) {
                        $app = json_decode($appRes['body'], true);
                        if (isset($app['approximate_guild_count'])) {
                            $_SESSION['bot_guild_count'] = $app['approximate_guild_count'];
                        }
                    }
                } else {
                    $error = 'Ungültige Antwort von Discord';
                }
            } else {
                $error = $botRes['error'] ?? 'Login fehlgeschlagen';
            }
        }
    }
}

$guildCount = $_SESSION['bot_guild_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <title><?= h($appConfig['site_title']) ?></title>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="<?= h($appConfig['theme_color']) ?>">

    <link rel="apple-touch-icon" sizes="180x180" href="<?= h($appConfig['favicon_url']) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= h($appConfig['favicon_url']) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= h($appConfig['favicon_url']) ?>">
    <link rel="icon" href="<?= h($appConfig['favicon_url']) ?>" type="image/png">

    <meta property="og:locale" content="de_DE">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= h($appConfig['social']['og_title']) ?>">
    <meta property="og:description" content="<?= h($appConfig['social']['og_description']) ?>">
    <meta property="og:image" content="<?= h($appConfig['favicon_url']) ?>">
    <meta property="og:url" content="">
    <meta property="og:site_name" content="<?= h($appConfig['social']['site_name']) ?>">

    <meta name="twitter:title" content="<?= h($appConfig['social']['twitter_title']) ?>">
    <meta name="twitter:description" content="<?= h($appConfig['social']['twitter_description']) ?>">
    <meta name="twitter:image" content="<?= h($appConfig['favicon_url']) ?>">
    <meta name="twitter:site" content="<?= h($appConfig['social']['twitter_site']) ?>">
    <meta name="twitter:creator" content="<?= h($appConfig['social']['twitter_creator']) ?>">

    <link href="<?= h($appConfig['fontawesome_css']) ?>" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <div class="panel">
            <div class="section">
                <h1><?= h($appConfig['site_title']) ?></h1>

                <form method="POST">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                    <input type="password" name="token" placeholder="Bot Token eingeben..." required>
                    <span class="info">Dein Token wird nicht gespeichert und nur für die Dauer der Sitzung verwendet.</span>
                    <button type="submit">Login</button>
                </form>
            </div>

            <?php if ($error): ?>
                <div class="error"><?= h($error) ?></div>
            <?php endif; ?>

            <?php if ($bot): ?>
                <div class="section">
                    <h2>Angemeldet als:</h2>

                    <div class="item">
                        <img src="<?= h(getBotAvatarUrl($bot)) ?>" alt="Bot Avatar">
                        <div class="meta">
                            <span><?= h($bot['username'] ?? 'Unbekannt') ?></span>
                            <code><?= h($bot['id'] ?? '') ?></code>
                        </div>
                        <form method="POST" action="logout.php" style="margin-left:10px;">
                            <button type="submit" class="copy-btn">Logout</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="section">
                <div class="section-header">
                    <span class="count" id="guild-count"><?= h($guildCount) ?> Server</span>
                    <div class="sort-buttons">
                        <button type="button" class="sort-btn" data-sort-type="name" title="Nach Servername sortieren">
                            <i class="fa-solid fa-up-down"></i> Servername
                        </button>
                        <button type="button" class="sort-btn" data-sort-type="members" title="Nach Mitgliederzahl sortieren">
                            <i class="fa-solid fa-up-down"></i> Mitglieder
                        </button>
                        <button type="button" class="sort-btn" data-sort-type="joined" title="Nach Beitrittsdatum sortieren">
                            <i class="fa-solid fa-up-down"></i> Hinzugefügt
                        </button>
                    </div>
                </div>

                <div id="guilds-section">
                    <div id="guilds-spinner" class="spinner" aria-hidden="false"></div>
                    <div class="item-list" id="guilds-list"></div>
                    <div id="pagination-container" style="text-align: center; margin-top: 20px; display: none;">
                        <button id="load-more-btn" class="copy-btn" style="width: auto; padding: 10px 20px;">Mehr laden</button>
                    </div>
                </div>
            </div>

            <div class="footer">
                <div class="footer-inner">
                    <div class="footer-title">
                        Made by <a href="https://jaybelife.de" target="_blank">.jaybelife</a> with <i class="fas fa-heart"></i>
                    </div>

                    <div class="footer-text">
                        Kein offizielles Discord Produkt
                    </div>

                    <div class="footer-text">
                        <a href="https://github.com/jaybelife" target="_blank">Open Source</a> for You!
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        window.__loggedIn = <?= isset($_SESSION['bot_token']) ? 'true' : 'false' ?>;
    </script>
</body>
</html>
