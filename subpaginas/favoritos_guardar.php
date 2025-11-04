<?php
declare(strict_types=1);

// Mostrar errores mientras probamos (podés apagar luego)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

// Si no hay sesión → al login con mensaje
if (empty($_SESSION['user_id'])) {
  header('Location: ./login.php?err=Iniciá%20sesión%20para%20guardar');
  exit;
}

// Conexión
$ROOT_FS = dirname(__DIR__);
foreach ([__DIR__.'/connect.php', $ROOT_FS.'/connect.php', $ROOT_FS.'/includes/connect.php', $ROOT_FS.'/inc/connect.php'] as $p) {
  if (is_file($p)) { require_once $p; break; }
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  header('Location: ./archivo.php?err=Error%20de%20conexi%C3%B3n');
  exit;
}

// Datos
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($item_id < 1 || $user_id < 1) {
  $back = $_SERVER['HTTP_REFERER'] ?? './archivo.php';
  $sep  = (strpos($back,'?')===false) ? '?' : '&';
  header('Location: ' . $back . $sep . 'err=ID%20inv%C3%A1lido');
  exit;
}

// Inserción (evita duplicados con UNIQUE)
$sql  = "INSERT IGNORE INTO archivo_favs (user_id, item_id, created_at) VALUES (?, ?, NOW())";
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
  $back = $_SERVER['HTTP_REFERER'] ?? './archivo.php';
  $sep  = (strpos($back,'?')===false) ? '?' : '&';
  header('Location: ' . $back . $sep . 'err=Error%20DB');
  exit;
}
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $item_id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Volver a la página desde donde viniste, con toast
$back = $_SERVER['HTTP_REFERER'] ?? './archivo.php';
$sep  = (strpos($back,'?')===false) ? '?' : '&';
header('Location: ' . $back . $sep . ($ok ? 'ok=fav' : 'err=No%20se%20pudo%20guardar'));
