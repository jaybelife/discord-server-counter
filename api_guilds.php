<?php
ob_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

$token = $_SESSION['bot_token'] ?? null;
$botId = $_SESSION['bot_id'] ?? null;

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Kein Bot-Token in der Session. Bitte einloggen.']);
    exit;
}

$limit = isset($_GET['limit']) ? min(100, (int)$_GET['limit']) : 25;
$after = $_GET['after'] ?? null;

$url = $appConfig['discord_api_base'] . '/users/@me/guilds?limit=' . $limit;
if ($after) {
    $url .= '&after=' . $after;
}

[$guilds, $err] = discordApiRequest($url, $token);

if ($err) {
    http_response_code(502);
    echo json_encode(['error' => $err]);
    exit;
}

$result = [];

if (is_array($guilds)) {
    // prepare urls
    $detailUrls = [];
    $memberUrls = [];

    // If it's the first page, we also fetch the application info for the total count
    if (!$after) {
        $detailUrls['__app_info'] = $appConfig['discord_api_base'] . '/applications/@me';
    }

    foreach ($guilds as $g) {
        $id = $g['id'] ?? null;
        if (!$id) continue;
        $detailUrls[$id] = $appConfig['discord_api_base'] . '/guilds/' . $id . '?with_counts=true';
        if ($botId) {
            $memberUrls[$id] = $appConfig['discord_api_base'] . '/guilds/' . $id . '/members/' . $botId;
        }
    }

    // fetch details in batches
    $detailsRes = multiFetch($detailUrls, $token, 6);

    // fetch member entries in batches (if botId)
    $membersRes = $botId ? multiFetch($memberUrls, $token, 6) : [];

    foreach ($guilds as $g) {
        $id = $g['id'] ?? null;
        if (!$id) continue;

        $entry = [
            'id' => $id,
            'name' => $g['name'] ?? null,
            'icon' => $g['icon'] ?? null,
            'member_count' => null,
            'joined_at' => null,
        ];

        if (isset($detailsRes[$id]) && $detailsRes[$id]['error'] === null) {
            $decoded = json_decode($detailsRes[$id]['body'], true);
            if (is_array($decoded)) {
                $entry['member_count'] = $decoded['approximate_member_count'] ?? $decoded['member_count'] ?? null;
            }
        }

        if ($botId && isset($membersRes[$id]) && $membersRes[$id]['error'] === null) {
            $decoded = json_decode($membersRes[$id]['body'], true);
            if (is_array($decoded) && !empty($decoded['joined_at'])) {
                $entry['joined_at'] = $decoded['joined_at'];
            }
        }

        $result[] = $entry;
    }
}

// sort by joined_at descending (newest first). Nulls go last.
usort($result, function ($a, $b) {
    $ta = !empty($a['joined_at']) ? strtotime($a['joined_at']) : 0;
    $tb = !empty($b['joined_at']) ? strtotime($b['joined_at']) : 0;
    
    if ($ta === false) $ta = 0;
    if ($tb === false) $tb = 0;

    return $tb <=> $ta;
});

$discordLastId = (is_array($guilds) && !empty($guilds)) ? end($guilds)['id'] : null;
$hasMore = (is_array($guilds) && count($guilds) === $limit);

$totalCount = $_SESSION['bot_guild_count'] ?? null;
if (isset($detailsRes['__app_info']) && $detailsRes['__app_info']['error'] === null) {
    $decoded = json_decode($detailsRes['__app_info']['body'], true);
    if (is_array($decoded) && isset($decoded['approximate_guild_count'])) {
        $totalCount = $decoded['approximate_guild_count'];
        $_SESSION['bot_guild_count'] = $totalCount;
    }
}

if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'guilds' => $result,
    'lastId' => $discordLastId,
    'hasMore' => $hasMore,
    'totalCount' => $totalCount
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
