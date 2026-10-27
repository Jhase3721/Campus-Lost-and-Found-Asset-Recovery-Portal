<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/bootstrap.php';
requireAdmin();

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

$assetIdInput = $_GET['id'] ?? '';
$assetId = is_string($assetIdInput)
    ? filter_var($assetIdInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;

if ($assetId === false) {
    http_response_code(400);
    exit('Invalid asset ID.');
}

try {
    $statement = db()->prepare(
        'SELECT qr_token FROM campus_assets WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $assetId]);
    $token = $statement->fetchColumn();
} catch (PDOException $exception) {
    error_log('Campus portal could not load an asset QR code.');
    http_response_code(500);
    exit('The asset QR code could not be generated.');
}

if (!is_string($token)) {
    http_response_code(404);
    exit('Asset not found.');
}

$host = $_SERVER['HTTP_HOST'] ?? '';
if (!is_string($host) || !preg_match('/^[A-Za-z0-9.-]+(?::[0-9]{1,5})?$/', $host)) {
    http_response_code(400);
    exit('The current host cannot be used to create asset QR links.');
}
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$assetUrl = $scheme . '://' . $host . ($basePath !== '' ? $basePath : '')
    . '/asset.php?code=' . rawurlencode($token);

try {
    $qrCode = QrCode::create($assetUrl)
        ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
        ->setSize(240)
        ->setMargin(12)
        ->setRoundBlockSizeMode(RoundBlockSizeMode::None);
    $result = (new SvgWriter())->write($qrCode);
} catch (Throwable $exception) {
    error_log('Campus portal could not generate an asset QR code.');
    http_response_code(500);
    exit('The asset QR code could not be generated.');
}

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
echo $result->getString();
