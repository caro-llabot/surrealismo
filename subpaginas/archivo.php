<?php
declare(strict_types=1);
session_start();
ini_set('display_errors','1'); error_reporting(E_ALL);

$ROOT_FS = dirname(__DIR__);
$BASE_URL = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); if ($BASE_URL==='') $BASE_URL='/';

foreach ([__DIR__.'/connect.php',$ROOT_FS.'/connect.php',$ROOT_FS.'/includes/connect.php',$ROOT_FS.'/inc/connect.php'] as $p) {
  if (is_file($p)) { require_once $p; break; }
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  http_response_code(500);
  echo '<h1>Error</h1><p>No hay conexión a la base.</p>'; exit;
}

// Traemos 24 items para la grilla principal (ajustable)
$rows = [];
$sql  = "SELECT id, title, author, year, type, description, url, thumb
         FROM archivo_items
         ORDER BY year DESC, title ASC
         LIMIT 24";
if ($rs = $conexion->query($sql)) {
  while ($it = $rs->fetch_assoc()) $rows[] = $it;
  $rs->free();
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Surrealismo — Archivo</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,100..700&family=Oswald:wght@200..700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= h($BASE_URL) ?>/styles.css">
  <script src="../app.js" defer></script>

</head>
<body>
<header class="hdr glass fixed" role="banner">
  <a href="<?= h($BASE_URL) ?>/index.html#hero" class="brand">Surrealismo</a>
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

<main id="main" tabindex="-1" class="arc">
  <section class="arc-hero">
    <div class="wrap">
      <h1 class="arc-h1">Archivo</h1>
      <p class="arc-lead">Material académico, fuentes primarias y enlaces de investigación.</p>

      <form class="arc-search" action="./resultados_buscar.php" method="get" role="search" aria-label="Buscar en el archivo">
        <label for="arcQuery" class="visually-hidden">Buscar</label>
        <input
          id="arcQuery"
          name="buscar"
          type="search"
          placeholder="Buscar por título, autor, año o etiqueta…"
          autocomplete="off"
          required
        >
        <button type="submit" class="btn-primary" aria-label="Buscar">Buscar</button>
      </form>
    </div>
  </section>

  <section class="arc-list">
    <div class="wrap">
      <?php if (!$rows): ?>
        <p class="muted">No hay entradas en el archivo todavía.</p>
      <?php else: ?>
        <ul class="arc-grid" id="arcList">
          <?php foreach ($rows as $it):
            $id    = (int)($it['id'] ?? 0);
            $title = h($it['title'] ?? '');
            $author= h($it['author'] ?? '');
            $year  = h((string)($it['year'] ?? ''));
            $type  = h($it['type'] ?? '');
            $desc  = h(mb_substr((string)($it['description'] ?? ''), 0, 160));
            $url   = h($it['url'] ?? '');
            $thumb = h($it['thumb'] ?? '');
            $metaParts = array_filter([$author, $year, ucfirst(strtolower($type))]);
            $meta = h(implode(' · ', $metaParts));
          ?>
          <li class="arc-item" data-id="<?= $id ?>">
            <div class="arc-card">
              <?php if ($thumb): ?>
                <img class="arc-img" src="<?= $thumb ?>" alt="<?= $title ?>" loading="lazy">
              <?php endif; ?>
              <h3 class="arc-t"><?= $title ?></h3>
              <?php if ($meta): ?><p class="arc-meta"><?= $meta ?></p><?php endif; ?>
              <?php if ($desc): ?><p class="arc-desc"><?= $desc ?>...</p><?php endif; ?>
              <div class="arc-actions">
                <?php if ($url): ?>
                  <a class="arc-link" href="<?= $url ?>" target="_blank" rel="noopener">Ver recurso →</a>
                <?php endif; ?>
                <?php if (!empty($_SESSION['user_id'])): ?>
  <form class="fav-form" method="post" action="./favoritos_guardar.php" style="display:inline">
    <input type="hidden" name="item_id" value="<?= (int)$id ?>">
    <button class="btn-secondary">＋ Guardar</button>
  </form>
<?php endif; ?>

              </div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </section>
</main>

<footer class="site-footer glass" role="contentinfo">
  <div class="footer-inner two-col">
    <!-- tu footer de siempre -->
  </div>
</footer>
</body>
</html>
