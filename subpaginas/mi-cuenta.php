<?php
declare(strict_types=1);
session_start();

if (empty($_SESSION['user_id'])) {
  header('Location: ./login.php?err=Iniciá%20sesión%20para%20ver%20tu%20cuenta');
  exit;
}

$ROOT_FS = dirname(__DIR__);
foreach ([__DIR__.'/connect.php', $ROOT_FS.'/connect.php', $ROOT_FS.'/includes/connect.php', $ROOT_FS.'/inc/connect.php'] as $p) {
  if (is_file($p)) { require_once $p; break; }
}

$user_id = (int)$_SESSION['user_id'];

$sql = "
  SELECT f.item_id, f.created_at,
         i.title, i.author, i.year, i.type, i.description, i.url, i.thumb
  FROM archivo_favs f
  JOIN archivo_items i ON i.id = f.item_id
  WHERE f.user_id = ?
  ORDER BY f.created_at DESC
";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$favs = [];
while ($row = mysqli_fetch_assoc($res)) $favs[] = $row;
mysqli_stmt_close($stmt);
mysqli_close($conexion);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mi cuenta — Favoritos</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@300;400;600&family=Oswald:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars(str_repeat('../',1)) ?>styles.css">
  <script src="<?= htmlspecialchars(str_repeat('../',1)) ?>app.js" defer></script>
</head>
<body>
<header class="hdr glass fixed" role="banner">
  <a href="../index.html#hero" class="brand">Surrealismo</a>
  <nav class="nav" aria-label="Secciones">
    <a href="./origenes.html">Orígenes</a>
    <a href="./figuras.html">Figuras</a>
    <a href="./obras.html">Obras</a>
    <a href="./archivo.php">Archivo</a>
    <a class="nav-when-guest"  href="./login.php">Ingresar</a>
    <a class="nav-when-guest"  href="./register.php">Crear cuenta</a>
    <a class="nav-when-logged" href="./mi-cuenta.php" style="display:none">Mi cuenta</a>
    <a class="nav-when-logged" href="./logout.php"     style="display:none">Salir</a>
  </nav>
</header>

<main class="acct" style="padding-top:7rem;">
  <div class="wrap">
    <h1 class="arc-h1">Hola, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></h1>
    <p class="arc-lead">Tus favoritos guardados</p>

    <?php if (empty($favs)): ?>
      <p class="muted">Todavía no guardaste nada. Andá al <a href="./archivo.php">Archivo</a> y sumá tus recursos favoritos.</p>
    <?php else: ?>
      <ul class="arc-grid">
        <?php foreach ($favs as $it): ?>
          <li class="arc-item">
            <div class="arc-card">
              <?php if (!empty($it['thumb'])): ?>
                <img class="arc-img" src="<?= htmlspecialchars($it['thumb']) ?>" alt="">
              <?php endif; ?>
              <h3 class="arc-t"><?= htmlspecialchars($it['title']) ?></h3>
              <p class="arc-meta">
                <?= htmlspecialchars(implode(' · ', array_filter([
                  $it['author'] ?? null,
                  $it['year'] ?? null,
                  isset($it['type']) ? ucfirst(strtolower($it['type'])) : null
                ]))) ?>
              </p>
              <?php if (!empty($it['description'])): ?>
                <p class="arc-desc"><?= htmlspecialchars(mb_substr($it['description'], 0, 140)) ?>...</p>
              <?php endif; ?>
              <?php if (!empty($it['url'])): ?>
                <a class="arc-link" href="<?= htmlspecialchars($it['url']) ?>" target="_blank" rel="noopener">Ver recurso →</a>
              <?php endif; ?>

              <form class="fav-form" method="post" action="./favoritos_quitar.php" style="margin-top:.5rem">
                <input type="hidden" name="item_id" value="<?= (int)$it['item_id'] ?>">
                <button class="btn-secondary" type="submit">− Quitar</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</main>

<footer class="site-footer glass" role="contentinfo">
  <div class="footer-inner two-col">
    <!-- (tu footer igual que en el resto) -->
    <p class="footer-copy">© <span id="year"></span> Surrealismo — Proyecto académico.</p>
  </div>
</footer>
<script>document.getElementById('year').textContent = new Date().getFullYear();</script>
</body>
</html>

