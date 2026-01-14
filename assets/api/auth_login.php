<?php
session_start();

// TODO: Google Cloud Console で作成した認証情報の入力
// 1. https://console.cloud.google.com/apis/credentials にアクセス
// 2. OAuth クライアント ID を作成
// 3. 承認済みのリダイレクト URI に "http://localhost:your_port/assets/api/auth_callback.php" (本番環境合わせたURL) を追加

require_once __DIR__ . '/secrets.php';
// $GOOGLE_CLIENT_ID is defined in secrets.php
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$REDIRECT_URI = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/assets/api/auth_callback.php';

// ステートの生成（CSRF対策）
$_SESSION['oauth_state'] = bin2hex(random_bytes(16));

// Google 認証ページへのリダイレクトURL構築
$params = [
    'response_type' => 'code',
    'client_id' => $GOOGLE_CLIENT_ID,
    'redirect_uri' => $REDIRECT_URI,
    'scope' => 'openid email profile',
    'state' => $_SESSION['oauth_state'],
    'access_type' => 'offline',
    'prompt' => 'select_account'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

header('Location: ' . $authUrl);
exit;
