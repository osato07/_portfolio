<?php

declare(strict_types=1);

// === Debug (disable in production if you prefer) ===
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
  exit;
}

$name = trim((string)($data['name'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$message = trim((string)($data['message'] ?? ''));
$page = trim((string)($data['page'] ?? ''));
$ua = trim((string)($data['ua'] ?? ''));

// バリデーション
if ($name === '' || $email === '' || $message === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => '必須項目が未入力です。']);
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'メール形式が正しくありません。']);
  exit;
}

// PHPMailer (assets/api/vendor/autoload.php)
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
  $mail = new PHPMailer(true);
  $mail->CharSet = 'UTF-8';

  // SMTP設定（Gmail例：App Password 推奨）
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'YOUR_GMAIL_ADDRESS@gmail.com';
  $mail->Password = 'YOUR_GMAIL_APP_PASSWORD';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;

  // 送信元（From は自分のGmail推奨 / Reply-To にユーザー）
  $mail->setFrom('YOUR_GMAIL_ADDRESS@gmail.com', 'Portfolio Contact');
  $mail->addReplyTo($email, $name);

  // 宛先
  $mail->addAddress('nakamotosato4@gmail.com');

  $subject = '【制作/取材お問い合わせ】' . $name;
  $body =
"制作・取材のお問い合わせです。\n\n" .
"■お名前\n{$name}\n\n" .
"■メール\n{$email}\n\n" .
"■内容\n{$message}\n\n" .
"■送信元ページ\n{$page}\n\n" .
"■UserAgent\n{$ua}\n";

  $mail->Subject = $subject;
  $mail->Body = $body;

  $mail->send();

  echo json_encode(['ok' => true]);
} catch (Exception $e) {
  // PHPMailer provides useful diagnostics via $mail->ErrorInfo
  $detail = $e->getMessage();
  if (isset($mail) && property_exists($mail, 'ErrorInfo') && $mail->ErrorInfo) {
    $detail .= ' / ' . $mail->ErrorInfo;
  }

  error_log('[contact_send] mail failed: ' . $detail);

  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => '送信に失敗しました。',
    'detail' => $detail,
  ], JSON_UNESCAPED_UNICODE);
}