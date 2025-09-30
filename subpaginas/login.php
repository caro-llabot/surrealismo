<?php
declare(strict_types=1);
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

session_start();

$ROOT_FS = dirname(__DIR__);

$BASE_URL = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($BASE_URL === '') $BASE_URL = '/';

$candidates_connect = [
  __DIR__ . '/connect.php',
  $ROOT_FS . '/connect.php',
  $ROOT_FS . '/includes/connect.php',
  $ROOT_FS . '/inc/connect.php',
];
foreach ($candidates_connect as $p) { if (is_file($p)) { require_once $p; break; } }

$candidates_db = [
  $ROOT_FS . '/includes/db.php',
  $ROOT_FS . '/inc/db.php',
];
foreach ($candidates_db as $p) { if (is_file($p)) { require_once $p; break; } }

$candidates_auth = [
  $ROOT_FS . '/includes/auth.php',
  $ROOT_FS . '/inc/auth.php',
];
foreach ($candidates_auth as $p) { if (is_file($p)) { require_once $p; break; } }

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  http_response_code(500);
  echo '<h1>Error de configuración</h1><p>No se pudo cargar <code>connect.php</code> / <code>db.php</code>. Verificá su ubicación.</p>';
  exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
  if ($pass === '') $errors[] = 'Ingresá tu contraseña.';

  if (!$errors) {
    $stmt = mysqli_prepare($conexion, "SELECT id, full_name, email, password_hash FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
      $errors[] = 'Error de servidor (DB).';
    } else {
      mysqli_stmt_bind_param($stmt, "s", $email);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      $u   = mysqli_fetch_assoc($res);

      if (!$u || !password_verify($pass, $u['password_hash'])) {
        $errors[] = 'Credenciales incorrectas.';
      } else {
        $_SESSION['user_id']    = (int)$u['id'];
        $_SESSION['user_name']  = $u['full_name'];
        $_SESSION['user_email'] = $u['email'];

        mysqli_stmt_close($stmt);
        mysqli_close($conexion);

        header('Location: ' . rtrim($BASE_URL, '/') . '/index.html?ok=login');
        exit;
      }
      mysqli_stmt_close($stmt);
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Surrealismo — Ingresar</title>

  <!-- Fuentes -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=Oswald:wght@200..700&display=swap" rel="stylesheet">

  <!-- Assets del proyecto (desde la raíz del sitio) -->
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

    <?php if (!empty($_SESSION['user_id'])): ?>
      <a href="<?= htmlspecialchars($BASE_URL) ?>/subpaginas/logout.php">Salir</a>
    <?php else: ?>
      <a href="<?= htmlspecialchars($BASE_URL) ?>/subpaginas/register.php">Crear cuenta</a>
    <?php endif; ?>
  </nav>
</header>

<main class="auth">
  <section class="auth-card">
    <h1 class="auth-title">Ingresar</h1>

    <?php if ($errors): ?>
      <div class="form-msg" style="color:#b02222;">
        <?= htmlspecialchars(implode(' · ', $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-row">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-row">
        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" minlength="8" required>
      </div>
      <button class="btn-primary" type="submit">Ingresar</button>
      <p class="form-note">
        ¿No tenés cuenta?
        <a class="link" href="<?= htmlspecialchars($BASE_URL) ?>/subpaginas/register.php">Crear cuenta</a>
      </p>
    </form>
  </section>
</main>

<?php if (isset($_GET['bye'])): ?>
  <div class="toast is-visible">Cerraste sesión correctamente.</div>
  <script>setTimeout(()=>document.querySelector('.toast')?.classList.add('is-hidden'), 2500);</script>
<?php endif; ?>
</body>
</html>
