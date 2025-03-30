<?php
// Đặt header cho phép truy cập từ mọi nguồn (CORS) và kiểu trả về JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Kiểm tra tham số 'bienso' có tồn tại không
if (!isset($_GET['bienso']) || empty($_GET['bienso'])) {
    echo json_encode([
        "success" => false,
        "message" => "Thiếu tham số 'bienso'."
    ]);
    exit;
}

// Lấy biển số từ tham số GET
$bienso = htmlspecialchars($_GET['bienso']);

// Cấu hình headers cho yêu cầu POST
$headers = [
    'authority: api.checkphatnguoi.vn',
    'accept: */*',
    'accept-language: vi-VN,vi;q=0.9',
    'content-type: application/json',
    'origin: https://checkphatnguoi.vn',
    'referer: https://checkphatnguoi.vn/',
    'sec-ch-ua: "Not A(Brand";v="8", "Chromium";v="132"',
    'sec-ch-ua-mobile: ?1',
    'sec-ch-ua-platform: "Android"',
    'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Mobile Safari/537.36',
];

// Cấu hình dữ liệu JSON gửi đi
$data = json_encode([
    "bienso" => $bienso,
]);

// Khởi tạo cURL
$ch = curl_init('https://api.checkphatnguoi.vn/phatnguoi');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

// Gửi yêu cầu và nhận phản hồi
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Xử lý kết quả
if ($httpCode == 200) {
    echo $response; // Trả về kết quả từ API gốc
} else {
    echo json_encode([
        "success" => false,
        "message" => "Không thể kết nối tới API gốc.",
        "http_code" => $httpCode,
    ]);
}
