<?php
// assets/api/upload-work-image.php
header('Content-Type: application/json; charset=utf-8');

$TOKEN = "cms_token_change_me_2025";

// Simple token check
$hdr = $_SERVER['HTTP_X_CMS_TOKEN'] ?? '';
if ($hdr !== $TOKEN) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
  exit;
}

$id = trim($_POST['id'] ?? '');
if ($id === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Missing id']);
  exit;
}

// id をファイル名に使うので最低限のサニタイズ（英数-_ のみ）
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid id']);
  exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Missing file']);
  exit;
}

$tmp = $_FILES['file']['tmp_name'];

// 画像か最低限チェック（完全ではないが軽量）
$info = @getimagesize($tmp);
if ($info === false) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'File is not an image']);
  exit;
}

// 保存先：assets/image/work/{id}.jpg
$root = dirname(__DIR__, 2); // assets/api から 2階層上 (= project root想定)
$destDir = $root . '/assets/image/work';
if (!is_dir($destDir)) {
  @mkdir($destDir, 0755, true);
}

$dest = $destDir . '/' . $id . '.jpg';

// JPGに統一したいなら、厳密には GD/Imagick で再エンコード推奨。
// ここでは「そのまま jpg として保存」する（入力がpngでも拡張子はjpgになる点に注意）。
if (!move_uploaded_file($tmp, $dest)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Failed to save file']);
  exit;
}

echo json_encode(['ok' => true, 'path' => 'assets/image/work/' . $id . '.jpg']);