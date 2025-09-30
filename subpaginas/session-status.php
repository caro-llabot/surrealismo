<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo json_encode([
  'logged' => isset($_SESSION['user_id']),
  'name'   => $_SESSION['user_name'] ?? null,
], JSON_UNESCAPED_UNICODE);
