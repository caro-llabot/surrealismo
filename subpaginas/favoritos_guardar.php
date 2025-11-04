<?php
declare(strict_types=1);
session_start();
header('Content-Type: text/html; charset=utf-8');

if (empty($_SESSION['user_id'])) {
  header('Location: ./login.php?err=Iniciá%20sesión%20para%20guardar'); exit;
}

$ROOT_FS = dirname(__DIR__);
foreach ([__DIR__.'/connect.php',$ROOT_FS.'/connect.php',$ROOT_FS.'/includes/connect.php',$ROOT_FS.'/inc/connect.php'] as $p) {
  if (is_file($p)) { require_once $p; break; }
}

$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
if ($item_id < 1) {
  header('Location: ./archivo.php?err=ID%20inv%C3%A1lido'); exit;
}

$sql = "INSERT IGNORE INTO archivo_favs (user_id, item_id, created_at) VALUES (?, ?, NOW())";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $_SESSION['user_id'], $item_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$back = $_SERVER['HTTP_REFERER'] ?? './archivo.php';
$sep  = (strpos($back,'?')===false) ? '?' : '&';
header('Location: ' . $back . $sep . ($ok ? 'ok=fav' : 'err=No%20se%20pudo%20guardar'));
