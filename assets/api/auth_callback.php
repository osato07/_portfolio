<?php
session_start();

// TODO: 設定 (auth_login.php と合わせる)
require_once __DIR__ . '/secrets.php';
// $GOOGLE_CLIENT_ID and $GOOGLE_CLIENT_SECRET are defined in secrets.php
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$REDIRECT_URI = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/assets/api/auth_callback.php';

// エラーチェック
if (isset($_GET['error'])) {
    die('Login Error: ' . htmlspecialchars($_GET['error']));
}

// ステートチェック (CSRF対策)
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Invalid state');
}

// 認可コード取得
$code = $_GET['code'];

// トークン交換
$tokenUrl = 'https://oauth2.googleapis.com/token';
$postData = [
    'code' => $code,
    'client_id' => $GOOGLE_CLIENT_ID,
    'client_secret' => $GOOGLE_CLIENT_SECRET,
    'redirect_uri' => $REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);

if (!isset($tokenData['id_token'])) {
    die('Token verification failed');
}

// IDトークンの検証（簡易的なデコード。本番環境ではライブラリ使用推奨だが、ここでは簡易実装）
// JWTは header.payload.signature の形式
$jwtParts = explode('.', $tokenData['id_token']);
$payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $jwtParts[1])), true);

if (!$payload) {
    die('Invalid ID Value');
}

// 許可するメールアドレスかどうかチェック
$allowed_email = 'nakamotosato4@gmail.com';
if ($payload['email'] !== $allowed_email) {
    // 許可されていないユーザー
    header('HTTP/1.1 403 Forbidden');
    echo "Access Denied: You are not authorized to access this CMS.";
    exit;
}

// ログイン成功
$_SESSION['user'] = [
    'email' => $payload['email'],
    'name' => $payload['name'] ?? 'User',
    'picture' => $payload['picture'] ?? ''
];

// CMSへリダイレクト
header('Location: ../../cms.php');
exit;
