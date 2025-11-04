<?php
declare(strict_types=1);
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: ./login.php?err=Iniciá%20sesión'); exit;
}

$ROOT_FS = dirname(__DIR__);
foreach ([__DIR__.'/connect.php', $ROOT_FS.'/connect.php', $ROOT_FS.'/includes/connect.php', $ROOT_FS.'/inc/connect.php'] as $p) {
  if (is_file($p)) { require_once $p; break; }
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$item_id = (int)($_POST['item_id'] ?? 0);

if ($user_id < 1 || $item_id < 1) {
  header('Location: ./mi-cuenta.php?err=Solicitud%20inv%C3%A1lida'); exit;
}

$sql  = "DELETE FROM archivo_favs WHERE user_id = ? AND item_id = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $item_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header('Location: ./mi-cuenta.php?ok=removed');
