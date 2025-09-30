<?php
declare(strict_types=1);
session_start();

$BASE_URL = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($BASE_URL === '') $BASE_URL = '/';

$_SESSION = [];
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

header('Location: ' . rtrim($BASE_URL, '/') . '/index.html?ok=logout');
exit;
