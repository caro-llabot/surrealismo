<?php
declare(strict_types=1);
ini_set('display_errors','1');
error_reporting(E_ALL);
session_start();

$ROOT_FS = dirname(__DIR__);
$BASE_URL = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($BASE_URL === '') $BASE_URL = '/';

// Cargar tu connect.php (mysqli $conexion)
foreach ([
  __DIR__ . '/connect.php',
  $ROOT_FS . '/connect.php',
  $ROOT_FS . '/includes/connect.php',
  $ROOT_FS . '/inc/connect.php',
] as $p) { if (is_file($p)) { require_once $p; break; } }

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  http_response_code(500);
  echo '<h1>Error</h1><p>No hay conexión a la base.</p>';
  exit;
}

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function markq($text, $q){
  $safe = h($text ?? '');
  $q = trim((string)$q);
  if ($q === '') return $safe;
  return preg_replace('/'.preg_quote($q,'/').'/i', '<mark>$0</mark>', $safe);
}

// Parámetro (GET o POST) — name="buscar"
$buscar = '';
if (isset($_POST['buscar'])) $buscar = trim((string)$_POST['buscar']);
if (isset($_GET['buscar']))  $buscar = trim((string)$_GET['buscar']);

$perPage = 12; // ajustá si querés
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Verificar existencia de tabla
$hasTable = false;
if ($res = $conexion->query("SHOW TABLES LIKE 'archivo_items'")) {
  $hasTable = $res->num_rows > 0; $res->free();
}

$total = 0;
$rows  = [];

if ($hasTable) {
  if ($buscar !== '') {
    // Conteo
    $sqlC = "SELECT COUNT(*) FROM archivo_items
             WHERE title LIKE CONCAT('%', ?, '%')
                OR author LIKE CONCAT('%', ?, '%')
                OR tags LIKE CONCAT('%', ?, '%')
                OR description LIKE CONCAT('%', ?, '%')";
    $st = $conexion->prepare($sqlC);
    $st->bind_param('ssss', $buscar, $buscar, $buscar, $buscar);
    $st->execute();
    $st->bind_result($total);
    $st->fetch(); $st->close();

    // Resultados (orden igual al tuyo)
    $sql = "SELECT id, title, author, year, type, description, url, thumb
            FROM archivo_items
            WHERE title LIKE CONCAT('%', ?, '%')
               OR author LIKE CONCAT('%', ?, '%')
               OR tags LIKE CONCAT('%', ?, '%')
               OR description LIKE CONCAT('%', ?, '%')
            ORDER BY year DESC, title ASC
            LIMIT ? OFFSET ?";
    $st = $conexion->prepare($sql);
    $st->bind_param('ssssii', $buscar, $buscar, $buscar, $buscar, $perPage, $offset);
  } else {
    if ($rc = $conexion->query("SELECT COUNT(*) AS c FROM archivo_items")) {
      $row = $rc->fetch_assoc(); $total = (int)($row['c'] ?? 0); $rc->free();
    }
    $st = $conexion->prepare("SELECT id, title, author, year, type, description, url, thumb
                              FROM archivo_items
                              ORDER BY year DESC, title ASC
                              LIMIT ? OFFSET ?");
    $st->bind_param('ii', $perPage, $offset);
  }

  $st->execute();
  $rs = $st->get_result();
  while ($it = $rs->fetch_assoc()) $rows[] = $it;
  $st->close();
}

$totalPages = max(1, (int)ceil($total / $perPage));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Archivo — Resultados<?= $buscar ? ' — '.h($buscar) : '' ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@300;400;600&family=Oswald:wght@400;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= h($BASE_URL) ?>/styles.css">
  <script src="<?= h($BASE_URL) ?>/app.js" defer></script>
</head>
<body class="page-archivo is-results">
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

<main id="main" class="arc">
  <section class="arc-hero">
    <div class="wrap">
      <h1 class="arc-h1">Resultados de búsqueda</h1>
      <form class="arc-search" action="./resultados_buscar.php" method="get" role="search" aria-label="Buscar en el archivo">
        <label for="arcQuery" class="visually-hidden">Buscar</label>
        <input
          id="arcQuery"
          name="buscar"
          type="search"
          placeholder="Buscar en el archivo..."
          value="<?= h($buscar) ?>"
          autocomplete="off"
          required
        >
      <button type="button" id="arcClear" aria-label="Limpiar búsqueda"></button>
      <button type="submit" class="btn-primary" aria-label="Buscar">Buscar</button>
        <a class="link" href="<?= h($BASE_URL) ?>/subpaginas/archivo.php" style="margin-left:.75rem;">Volver al Archivo</a>
      </form>

      <?php if ($buscar === ''): ?>
        <p class="arc-lead">Escribí un término y presioná “Buscar”.</p>
      <?php else: ?>
        <p class="arc-lead">Tu consulta: <em><?= h($buscar) ?></em></p>
      <?php endif; ?>
    </div>
  </section>

  <section class="arc-list">
    <div class="wrap">
      <?php if (!$hasTable): ?>
        <div class="form-msg" style="color:#b02222;">La tabla <code>archivo_items</code> no existe.</div>
      <?php else: ?>
        <p><strong>Cantidad de resultados:</strong> <?= (int)$total ?></p>

        <?php if ($total < 1): ?>
          <div id="arcEmpty" class="muted" style="margin-top:1rem;">No hay resultados para esa búsqueda.</div>
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

                  <h3 class="arc-t"><?= markq($title, $buscar) ?></h3>

                  <?php if ($meta): ?>
                    <p class="arc-meta"><?= markq($meta, $buscar) ?></p>
                  <?php endif; ?>

                  <?php if ($desc): ?>
                    <p class="arc-desc"><?= markq($desc, $buscar) ?>...</p>
                  <?php endif; ?>

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

          <?php /* Paginación opcional — descomentar si querés
          <?php if ($total > $perPage):
            $base = './resultados_buscar.php?buscar=' . urlencode($buscar) . '&page=';
          ?>
            <nav class="pager" aria-label="Paginación" style="display:flex;gap:8px;flex-wrap:wrap;margin:16px 0">
              <?php for ($p=1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $page): ?>
                  <span style="padding:8px 10px;border:1px solid #111;border-radius:8px;background:#111;color:#fff"><?= $p ?></span>
                <?php else: ?>
                  <a href="<?= $base.$p ?>" style="padding:8px 10px;border:1px solid #ddd;border-radius:8px;text-decoration:none"><?= $p ?></a>
                <?php endif; ?>
              <?php endfor; ?>
            </nav>
          <?php endif; ?>
          */ ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>
</main>

<footer class="site-footer glass" role="contentinfo">
  <div class="footer-inner two-col">
    <!-- tu footer -->
  </div>
</footer>
</body>
</html>
