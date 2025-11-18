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
  <label for="arcQuery" class="sr-only">Buscar en el archivo</label>

  <input
    id="arcQuery"
    name="buscar"
    type="search"
    placeholder="Buscar por título, autor, año o etiqueta…"
    autocomplete="off"
    required
  >

  <button type="button" id="arcClear" aria-label="Borrar búsqueda"></button>

  <button type="submit" class="arc-btn-search" aria-label="Buscar">Buscar</button>


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
                    <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                    <button class="btn-secondary" type="submit">＋ Guardar</button>
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
