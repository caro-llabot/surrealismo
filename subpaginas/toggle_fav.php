<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'err'=>'not_logged']);
  exit;
}

$ROOT_FS = dirname(__DIR__);
foreach ([__DIR__.'/connect.php',$ROOT_FS.'/connect.php',$ROOT_FS.'/includes/connect.php',$ROOT_FS.'/inc/connect.php'] as $p) {
  if (is_file($p)) { require_once $p; break; }
}
if (!isset($conexion) || !($conexion instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'err'=>'db']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$itemId = isset($input['item_id']) ? (int)$input['item_id'] : 0;
if ($itemId < 1) { echo json_encode(['ok'=>false,'err'=>'bad_id']); exit; }

$userId = (int)$_SESSION['user_id'];

// ¿Existe ya?
$st = $conexion->prepare("SELECT 1 FROM user_favorites WHERE user_id=? AND item_id=?");
$st->bind_param('ii',$userId,$itemId); $st->execute(); $st->store_result();
$exists = $st->num_rows > 0; $st->close();

if ($exists) {
  $st = $conexion->prepare("DELETE FROM user_favorites WHERE user_id=? AND item_id=?");
  $st->bind_param('ii',$userId,$itemId); $st->execute(); $st->close();
  echo json_encode(['ok'=>true,'state'=>'removed']);
} else {
  $st = $conexion->prepare("INSERT INTO user_favorites (user_id,item_id) VALUES (?,?)");
  $st->bind_param('ii',$userId,$itemId); $st->execute(); $st->close();
  echo json_encode(['ok'=>true,'state'=>'added']);
}
