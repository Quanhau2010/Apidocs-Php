<?php
header('Content-Type: image/png');

// Get parameters from the query string
$instag = isset($_GET['instag']) ? $_GET['instag'] : null;
$ghub = isset($_GET['ghub']) ? $_GET['ghub'] : null;
$fb = isset($_GET['fb']) ? $_GET['fb'] : null;
$hours = isset($_GET['hours']) ? $_GET['hours'] : null;
$minutes = isset($_GET['minutes']) ? $_GET['minutes'] : null;
$seconds = isset($_GET['seconds']) ? $_GET['seconds'] : null;
$botname = isset($_GET['botname']) ? $_GET['botname'] : null;

// Validate required parameters
if (!$instag || !$ghub || !$fb || !$hours || !$minutes || !$seconds || !$botname) {
    http_response_code(400);
    echo json_encode(['error' => 'add ?instag=your_instagram_username&ghub=your_github_username&fb=your_facebook_username&hours=1&minutes=23&seconds=45&botname=your_bot_name']);
    exit;
}

// Set up paths for fonts and cache directory
$cacheDir = __DIR__ . '/tmp';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

$fonts = [
    ['url' => 'https://github.com/Quanhau2010/data/blob/main/UTM-Avo.ttf?raw=true', 'path' => $cacheDir . '/UTM-Avo.ttf'],
    ['url' => 'https://github.com/Quanhau2010/data/blob/main/phenomicon.ttf?raw=true', 'path' => $cacheDir . '/phenomicon.ttf'],
    ['url' => 'https://github.com/Quanhau2010/data/blob/main/CaviarDreams.ttf?raw=true', 'path' => $cacheDir . '/CaviarDreams.ttf']
];

// Download fonts if not exist
foreach ($fonts as $font) {
    if (!file_exists($font['path'])) {
        $fontData = file_get_contents($font['url']);
        file_put_contents($font['path'], $fontData);
    }
}

// Background image URL
$backgroundUrl = 'https://imgur.com/x5JpRYu.png';
$backgroundImage = imagecreatefrompng($backgroundUrl);

// Fetch anime image data from a JSON file
$animeData = json_decode(file_get_contents('https://raw.githubusercontent.com/Quanhau2010/data/main/dataimganime.json'), true);
$randomAnime = $animeData[array_rand($animeData)];
$animeImage = imagecreatefromjpeg($randomAnime['imgAnime']);

// Create a canvas
$canvasWidth = imagesx($backgroundImage);
$canvasHeight = imagesy($backgroundImage);
$canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

// Fill background with anime color
$bgColor = imagecolorallocate($canvas, hexdec(substr($randomAnime['colorBg'], 1, 2)), hexdec(substr($randomAnime['colorBg'], 3, 2)), hexdec(substr($randomAnime['colorBg'], 5, 2)));
imagefill($canvas, 0, 0, $bgColor);

// Draw the anime image and the background
imagecopy($canvas, $animeImage, -200, -200, 0, 0, imagesx($animeImage), imagesy($animeImage));
imagecopy($canvas, $backgroundImage, 0, 0, 0, 0, $canvasWidth, $canvasHeight);

// Register fonts (you would use GD functions or Imagick in PHP to render text)
$fontPath1 = $cacheDir . '/phenomicon.ttf';
$fontPath2 = $cacheDir . '/UTM-Avo.ttf';
$fontPath3 = $cacheDir . '/CaviarDreams.ttf';

// Add text to the canvas (you can adjust coordinates and styling)
$white = imagecolorallocate($canvas, 255, 255, 255);
$black = imagecolorallocate($canvas, 0, 0, 0);

$fontSizeBotname = 30;
$fontSizeTime = 20;
$fontSizeSocial = 18;

// Add botname
imagettftext($canvas, $fontSizeBotname, 0, 835, 340, $white, $fontPath1, $botname);

// Add time
imagettftext($canvas, $fontSizeTime, 0, 980, 440, $black, $fontPath2, "$hours : $minutes : $seconds");

// Add social usernames
imagettftext($canvas, $fontSizeSocial, 0, 930, 540, $white, $fontPath3, $instag);
imagettftext($canvas, $fontSizeSocial, 0, 930, 610, $white, $fontPath3, $ghub);
imagettftext($canvas, $fontSizeSocial, 0, 930, 690, $white, $fontPath3, $fb);

// Output the image
imagepng($canvas);

// Clean up
imagedestroy($canvas);
imagedestroy($backgroundImage);
imagedestroy($animeImage);

?>
