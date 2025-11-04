<?php
declare(strict_types=1);

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'surrealismo'; 

$conexion = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conexion) {
  http_response_code(500);
  echo '<h1>Error de conexión a la base de datos</h1>';
  echo '<p>' . htmlspecialchars(mysqli_connect_error()) . '</p>';
  exit;
}

mysqli_set_charset($conexion, 'utf8mb4');
