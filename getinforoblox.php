<?php
header('Content-Type: application/json');

/**
 * Hàm gửi yêu cầu đến API của Roblox
 */
function fetchFromRobloxAPI($url, $method = 'GET', $payload = null) {
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => $method
        ]
    ];
    if ($payload) {
        $options['http']['content'] = json_encode($payload);
    }
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        error_log("Failed to fetch from Roblox API: $url");
        return null;
    }
    return json_decode($response, true);
}

/**
 * Hàm lấy ID người dùng Roblox từ tên
 */
function getUserIdByUsername($username) {
    $url = "https://users.roblox.com/v1/usernames/users";
    $payload = [
        "usernames" => [$username],
        "excludeBannedUsers" => true
    ];
    $response = fetchFromRobloxAPI($url, 'POST', $payload);
    return $response['data'][0] ?? null;
}

/**
 * Hàm lấy thông tin người dùng
 */
function getUserDetails($userId) {
    $details = [];

    // 1. Thông tin cơ bản
    $details['basicInfo'] = fetchFromRobloxAPI("https://users.roblox.com/v1/users/$userId") ?? null;

    // 2. Ngày tham gia
    $details['accountCreationDate'] = $details['basicInfo']['created'] ?? null;

    // 3. Avatar
    $avatar = fetchFromRobloxAPI("https://thumbnails.roblox.com/v1/users/avatar-headshot?userIds=$userId&size=150x150&format=Png&isCircular=false");
    $details['avatar'] = $avatar['data'][0]['imageUrl'] ?? null;

    // 4. Số lượng bạn bè
    $friends = fetchFromRobloxAPI("https://friends.roblox.com/v1/users/$userId/friends");
    $details['friendCount'] = isset($friends) ? count($friends) : 0;

    // 5. Số lượng người theo dõi
    $followers = fetchFromRobloxAPI("https://friends.roblox.com/v1/users/$userId/followers/count");
    $details['followersCount'] = $followers['count'] ?? 0;

    // 6. Trạng thái Premium
    $premiumInfo = fetchFromRobloxAPI("https://premiumfeatures.roblox.com/v1/users/$userId/memberships");
    $details['isPremium'] = isset($premiumInfo['premiumMembership']) && $premiumInfo['premiumMembership'] === true;

    // 7. Trạng thái hoạt động
    $presence = fetchFromRobloxAPI("https://presence.roblox.com/v1/presence/users", 'POST', [
        "userIds" => [$userId]
    ]);
    $details['presence'] = $presence['userPresences'][0] ?? null;

    // 8. Lịch sử đổi tên
    $usernameHistory = fetchFromRobloxAPI("https://users.roblox.com/v1/users/$userId/username-history");
    $details['usernameHistory'] = $usernameHistory['data'] ?? [];

    // 9. Thông tin nhóm (Groups)
    $groups = fetchFromRobloxAPI("https://groups.roblox.com/v1/users/$userId/groups/roles");
    $details['groups'] = $groups['data'] ?? [];

    // 10. Huy hiệu (Badges)
    $badges = fetchFromRobloxAPI("https://badges.roblox.com/v1/users/$userId/badges?sortOrder=Desc&limit=10");
    $details['badges'] = $badges['data'] ?? [];

    // 11. Game yêu thích
    $favorites = fetchFromRobloxAPI("https://games.roblox.com/v1/users/$userId/favorite/games?limit=10");
    $details['favoriteGames'] = $favorites['data'] ?? [];

    // 12. Danh sách game đã tạo, sắp xếp theo số người chơi
    $games = fetchFromRobloxAPI("https://games.roblox.com/v2/users/$userId/games?sortOrder=Asc&limit=100");
    if (isset($games['data'])) {
        $gameDetails = [];
        foreach ($games['data'] as $game) {
            $placeId = $game['rootPlaceId'];
            $gameInfo = fetchFromRobloxAPI("https://games.roblox.com/v1/games?universeIds=" . $game['universeId']);
            if ($gameInfo && isset($gameInfo['data'][0])) {
                $gameDetails[] = [
                    'name' => $gameInfo['data'][0]['name'] ?? "Unknown",
                    'placeId' => $placeId,
                    'currentPlayers' => $gameInfo['data'][0]['playing'] ?? 0,
                    'totalVisits' => $gameInfo['data'][0]['visits'] ?? 0,
                    'isPrivate' => $gameInfo['data'][0]['private'] ?? false
                ];
            }
        }
        usort($gameDetails, function ($a, $b) {
            return $b['currentPlayers'] - $a['currentPlayers'];
        });
        $details['topGames'] = $gameDetails;
    } else {
        $details['topGames'] = [];
    }

    return $details;
}

/**
 * Xử lý yêu cầu API
 */
if (isset($_GET['username'])) {
    $username = htmlspecialchars($_GET['username']);
    $userInfo = getUserIdByUsername($username);

    if ($userInfo) {
        $userId = $userInfo['id'];
        $details = getUserDetails($userId);

        echo json_encode([
            "status" => "success",
            "data" => $details,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Không tìm thấy người dùng Roblox này.",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Vui lòng cung cấp username.",
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
