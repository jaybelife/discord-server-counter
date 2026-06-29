<?php
function discordApiRequest(string $url, string $token): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bot $token",
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($curlError) {
        return [null, "cURL Fehler: $curlError"];
    }

    if ($httpCode >= 400) {
        return [null, "HTTP Fehler: $httpCode"];
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [null, 'JSON Fehler'];
    }

    return [$data, null];
}

/**
 * Perform parallel requests in batches using curl_multi
 */
function multiFetch(array $urls, string $token, int $batchSize = 6): array
{
    $out = [];
    $chunks = array_chunk($urls, $batchSize, true);

    foreach ($chunks as $chunk) {
        $multi = curl_multi_init();
        $handles = [];

        foreach ($chunk as $key => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bot $token",
                    "Content-Type: application/json"
                ],
                CURLOPT_TIMEOUT => 10,
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[(int)$ch] = ['handle' => $ch, 'key' => $key];
        }

        $running = null;
        do {
            curl_multi_exec($multi, $running);
            curl_multi_select($multi, 0.5);
        } while ($running > 0);

        foreach ($handles as $h) {
            $ch = $h['handle'];
            $key = $h['key'];
            $content = curl_multi_getcontent($ch);
            $err = curl_error($ch);
            if ($err) {
                $out[$key] = ['error' => $err, 'body' => null];
            } else {
                $out[$key] = ['error' => null, 'body' => $content];
            }
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);
    }

    return $out;
}

function getBotAvatarUrl(array $bot): string
{
    if (!empty($bot['avatar'])) {
        return 'https://cdn.discordapp.com/avatars/' . $bot['id'] . '/' . $bot['avatar'] . '.png';
    }

    return 'https://cdn.discordapp.com/embed/avatars/0.png';
}

function getGuildIconUrl(array $guild): string
{
    if (!empty($guild['icon'])) {
        return 'https://cdn.discordapp.com/icons/' . $guild['id'] . '/' . $guild['icon'] . '.png';
    }

    return 'https://cdn.discordapp.com/embed/avatars/0.png';
}

function h($value = ''): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
