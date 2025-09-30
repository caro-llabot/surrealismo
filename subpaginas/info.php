
<?php
declare(strict_types=1);
session_start();
?><!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Estado</title></head>
<body>
<h1>Estado de sesión</h1>
<pre><?php var_dump($_SESSION); ?></pre>
</body>
</html>
