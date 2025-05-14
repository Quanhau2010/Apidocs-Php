<?php
header('Content-Type: application/json');

$url = isset($_GET['url']) ? $_GET['url'] : null;

if (!$url || !preg_match('/^https:\/\/open\.spotify\.com\/(album|track|playlist)\/[a-zA-Z0-9]+/', $url)) {
    http_response_code(400);
    echo json_encode([
        "status" => false,
        "message" => "Vui lòng nhập một liên kết Spotify hợp lệ."
    ]);
    exit;
}

function fetchApi($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    if (curl_errno($ch)) return null;
    curl_close($ch);
    return json_decode($result, true);
}

try {
    $encoded = urlencode($url);
    $headers = [
        "accept: application/json, text/plain, */*",
        "referer: https://spotifydownload.org/"
    ];

    $meta = fetchApi("https://api.fabdl.com/spotify/get?url={$encoded}", $headers);
    if (!$meta || !isset($meta['result']['gid']) || !isset($meta['result']['id'])) {
        throw new Exception("Không thể lấy thông tin bài hát.");
    }

    $gid = $meta['result']['gid'];
    $id = $meta['result']['id'];
    $convert = fetchApi("https://api.fabdl.com/spotify/mp3-convert-task/$gid/$id", $headers);
    if (!$convert || !isset($convert['result']['download_url'])) {
        throw new Exception("Không thể tạo liên kết tải.");
    }

    echo json_encode([
        "author" => "Quanhau2010",
        "title" => $meta['result']['name'],
        "artist" => $meta['result']['artists'],
        "thumbnail" => $meta['result']['image'],
        "url" => "https://api.fabdl.com" . $convert['result']['download_url']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Đã xảy ra lỗi.",
        "error" => $e->getMessage()
    ]);
}
