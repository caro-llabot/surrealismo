<?php
declare(strict_types=1);
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

session_start();

$BASE_URL = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($BASE_URL === '') $BASE_URL = '/';

require_once __DIR__ . '/connect.php';

$errors = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $pass2 = $_POST['password2'] ?? '';

  if ($name === '') $errors[] = 'Ingresá tu nombre.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
  if (strlen($pass) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
  if ($pass !== $pass2) $errors[] = 'Las contraseñas no coinciden.';

  if (!$errors) {

    $stmt = mysqli_prepare($conexion, "SELECT id FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $exists = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($exists) {
      $errors[] = 'Ese email ya está registrado.';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = mysqli_prepare($conexion, "INSERT INTO users (full_name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
      if (!$stmt) {
        $errors[] = 'Error de servidor (DB).';
      } else {
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hash);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {

          $_SESSION['user_id']    = (int)mysqli_insert_id($conexion);
          $_SESSION['user_name']  = $name;
          $_SESSION['user_email'] = $email;
          mysqli_close($conexion);
          header('Location: ' . rtrim($BASE_URL, '/') . '/index.html?ok=register');
          exit;
        } else {
          $errors[] = 'No se pudo crear la cuenta.';
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Surrealismo — Registro</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600;700&family=Oswald:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars($BASE_URL) ?>/styles.css">
  <script src="<?= htmlspecialchars($BASE_URL) ?>/app.js" defer></script>
</head>
<body>
<header class="hdr glass fixed" role="banner">
  <a href="<?= htmlspecialchars($BASE_URL) ?>/index.html#hero" class="brand">Surrealismo</a>
  <nav class="nav" aria-label="Secciones">
    <a href="<?= htmlspecialchars($BASE_URL) ?>/subpaginas/origenes.html">Orígenes</a>
    <a href="<?= htmlspecialchars($BASE_URL) ?>/subpaginas/figuras.html">Figuras</a>
    <a href="<?= htmlspecialchars($BASE_URL) ?>/subpaginas/obras.html">Obras</a>
    <a href="<?= htmlspecialchars($BASE_URL) ?>/subpaginas/archivo.html">Archivo</a>
    <a class="nav-when-guest" href="<?= htmlspecialchars($BASE_URL) ?>/subpaginas/login.php">Ingresar</a>
    <a class="nav-when-logged" href="<?= htmlspecialchars($BASE_URL) ?>/subpaginas/logout.php" style="display:none">Salir</a>
  </nav>
</header>

<main class="auth">
  <section class="auth-card">
    <h1 class="auth-title">Crear cuenta</h1>

    <?php if ($errors): ?>
      <div class="form-msg" style="color:#b02222;"><?= htmlspecialchars(implode(' · ', $errors)) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-row"><label for="name">Nombre completo</label><input id="name" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"></div>
      <div class="form-row"><label for="email">Email</label><input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></div>
      <div class="form-row"><label for="password">Contraseña</label><input id="password" name="password" type="password" minlength="8" required></div>
      <div class="form-row"><label for="password2">Repetir contraseña</label><input id="password2" name="password2" type="password" minlength="8" required></div>
      <button class="btn-primary" type="submit">Crear cuenta</button>
      <p class="form-note">¿Ya tenés cuenta? <a class="link" href="<?= htmlspecialchars($BASE_URL) ?>/subpaginas/login.php">Ingresar</a></p>
    </form>
  </section>
</main>
</body>
</html>
