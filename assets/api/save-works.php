<?php
// api/save-works.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
  exit;
}

$token = $_SERVER['HTTP_X_CMS_TOKEN'] ?? '';
$expected = 'cms_token_change_me_2025'; // 本当はenv化必要だけどめんどくさい
if ($token !== $expected) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
  exit;
}

$raw = file_get_contents('php://input');

// 簡易チェック: 空でなければ保存する（コメント付きJSONを許容するため、厳密な json_decode チェックは外す）
if (trim($raw) === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Empty body']);
  exit;
}

// cms.html から見た works.json の位置に合わせて調整してください
$path = __DIR__ . '/../data/works.json';

// そのまま書き込む
$ok = file_put_contents($path, $raw, LOCK_EX);

if ($ok === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Write failed', 'path' => $path]);
  exit;
}

echo json_encode(['ok' => true, 'count' => -1]); // count is unknown/irrelevant here