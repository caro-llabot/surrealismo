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
  <a href="./origenes.html">Orígenes</a>
<a href="./figuras.html">Figuras</a>
<a href="./obras.html">Obras</a>
<a href="./archivo.php">Archivo</a>
<a class="nav-when-guest"  href="./login.php">Ingresar</a>
<a class="nav-when-guest"  href="./register.php">Crear cuenta</a>
<a class="nav-when-logged" href="./mi-cuenta.php" style="display:none">Mi cuenta</a>
<a class="nav-when-logged" href="./logout.php" style="display:none">Salir</a>

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
<footer class="site-footer glass" role="contentinfo">
  <div class="footer-inner two-col">

    <div class="footer-left">
      <a href="/surrealismo/index.html" class="brandmark" aria-label="Surrealismo - volver al inicio">
        Surrealismo
      </a>

      <nav class="social" aria-label="Redes sociales">

        <a href="#" aria-label="LinkedIn" class="social-link" rel="noopener">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4.98 3.5A2.5 2.5 0 1 1 5 8.5a2.5 2.5 0 0 1-.02-5zM3 9h4v12H3zM9 9h3.8v1.9h.1c.5-.9 1.8-2 3.7-2 4 0 4.8 2.6 4.8 6v6h-4v-5.3c0-1.3 0-3-1.9-3s-2.2 1.4-2.2 2.9V21H9z"/>
          </svg>
        </a>

        <a href="#" aria-label="Instagram" class="social-link" rel="noopener">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm6.5-3A3.5 3.5 0 0 1 22 7.5v9A3.5 3.5 0 0 1 18.5 20h-13A3.5 3.5 0 0 1 2 16.5v-9A3.5 3.5 0 0 1 5.5 4h13zM12 9.4A2.6 2.6 0 1 1 9.4 12 2.6 2.6 0 0 1 12 9.4Zm5.1-2a.9.9 0 1 0 .9.9.9.9 0 0 0-.9-.9Z"/>
          </svg>
        </a>

        <a href="mailto:carolallabot@gmail.com" aria-label="Gmail" class="social-link">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M22 6.3V18a2 2 0 0 1-2 2h-2V9.8L12 14 6 9.8V20H4a2 2 0 0 1-2-2V6.3a2 2 0 0 1 3.2-1.6L6 6.6 12 11l6-4.4.8-.6A2 2 0 0 1 22 6.3z"/>
          </svg>
        </a>

        <a href="#" aria-label="Behance" class="social-link" rel="noopener">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M13.8 8.2h4V9h-4v-.8zm-8.9.1h4.4c1.7 0 2.8.8 2.8 2.1 0 .9-.5 1.5-1.2 1.8.9.3 1.6 1 1.6 2.2 0 1.6-1.3 2.6-3.2 2.6H4.9V8.3zm3.9 3.3c.9 0 1.4-.4 1.4-1s-.5-1-1.4-1H6.9v2h1.9zm.2 4c1 0 1.6-.4 1.6-1.2s-.6-1.2-1.6-1.2H6.9v2.4h2.1zM18 12.5c-1 0-1.7.8-1.8 1.9h3.5c-.1-1.1-.7-1.9-1.7-1.9zm0-2.5c2.4 0 3.9 1.7 3.9 4.4v.3h-6c.1 1.2.9 2 2.2 2 .9 0 1.6-.4 2-1.1l1.5.9c-.7 1.2-2 2-3.7 2-2.3 0-4-1.6-4-4.2 0-2.6 1.6-4.2 4.1-4.2z"/>
          </svg>
        </a>
      </nav>

    </div>

    <div class="footer-right footer-columns" aria-label="Mapa del sitio">
      <div class="col">
        <h3>Secciones</h3>
        <ul>
          <li><a href="../index.html#hero">Home</a></li>
          <li><a href="./origenes.html">Orígenes</a></li>
          <li><a href="./figuras.html">Figuras</a></li>
          <li><a href="./obras.html">Obras</a></li>
          <li><a href="./archivo.php">Archivo</a></li>
        </ul>
      </div>
      <div class="col">
        <h3>Contacto</h3>
        <address class="contact">
          <a href="mailto:carolallabot@gmail.com">carolallabot@gmail.com</a><br>
          <a href="tel:+5493510000000">+54 9 351 000 0000</a><br>
          Córdoba, Argentina
        </address>
      </div>
      <div class="col">
        <h3>Legal</h3>
        <ul>
          <li><a href="/surrealismo/terminos.html">Términos de uso</a></li>
          <li><a href="/surrealismo/privacidad.html">Política de privacidad</a></li>
          <li><a href="/surrealismo/creditos.html">Créditos</a></li>
        </ul>
      </div>
    </div>
  </div>

  <p class="footer-copy">© <span id="year"></span> Surrealismo — Proyecto académico.</p>
</footer>
</body>
</html>
