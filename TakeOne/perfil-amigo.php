<?php

/**
 * perfil-amigo.php
 * Vista del perfil de otro usuario.
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) {
  header('Location: login.php');
  exit;
}
$yo = (int) $usuario['id'];

require_once 'includes/conexion.php';

// ── Obtener el usuario objetivo ───────────────────────────────────────────────
$idVisto = (int) ($_GET['id'] ?? 0);
if (!$idVisto || $idVisto === $yo) {
  header('Location: perfil.php');
  exit;
}

$stmtU = $pdo->prepare("
    SELECT id_usuario, username, avatar, biografia, localidad
    FROM usuarios
    WHERE id_usuario = ? AND activo = 1 AND rol <> 'admin'
");
$stmtU->execute([$idVisto]);
$amigo = $stmtU->fetch(PDO::FETCH_ASSOC);

if (!$amigo) {
  header('Location: 404.php');
  exit;
}

$inicialAmigo = strtoupper(substr($amigo['username'], 0, 1));

// ── Estado de amistad ─────────────────────────────────────────────────────────
$stmtRel = $pdo->prepare("
    SELECT estado, id_emisor FROM amistades
    WHERE (id_emisor = :a AND id_receptor = :b)
       OR (id_emisor = :b2 AND id_receptor = :a2)
");
$stmtRel->execute([':a' => $yo, ':b' => $idVisto, ':b2' => $idVisto, ':a2' => $yo]);
$relacion = $stmtRel->fetch(PDO::FETCH_ASSOC);

$estadoAmistad = $relacion['estado'] ?? null;          // null | pendiente | aceptada | rechazada
$soyEmisor     = $relacion ? ((int)$relacion['id_emisor'] === $yo) : false;
$sonAmigos     = $estadoAmistad === 'aceptada';

// ── Películas favoritas del perfil ────────────────────────────────────────────
$stmtFavs = $pdo->prepare("
    SELECT p.id_pelicula, p.titulo, p.poster
    FROM usuarios_favoritas_perfil ufp
    JOIN peliculas p ON p.id_pelicula = ufp.id_pelicula
    WHERE ufp.id_usuario = :id
    ORDER BY ufp.orden ASC
    LIMIT 5
");
$stmtFavs->execute([':id' => $idVisto]);
$favoritas = $stmtFavs->fetchAll(PDO::FETCH_ASSOC);

// ── Géneros favoritos ─────────────────────────────────────────────────────────
$stmtGen = $pdo->prepare("
    SELECT g.nombre
    FROM usuarios_generos_favoritos ugf
    JOIN generos g ON g.id_genero = ugf.id_genero
    WHERE ugf.id_usuario = :id
    ORDER BY g.nombre ASC
");
$stmtGen->execute([':id' => $idVisto]);
$generos = $stmtGen->fetchAll(PDO::FETCH_COLUMN);

// ── Amigos en común / lista de amigos del usuario visto ─────────────────────
$stmtAmigosVisto = $pdo->prepare("
    SELECT u.id_usuario, u.username, u.avatar
    FROM amistades a
    JOIN usuarios u ON u.id_usuario = IF(a.id_emisor = :id, a.id_receptor, a.id_emisor)
    WHERE a.estado = 'aceptada'
      AND (a.id_emisor = :id2 OR a.id_receptor = :id3)
      AND u.activo = 1
    ORDER BY u.username ASC
");
$stmtAmigosVisto->execute([':id' => $idVisto, ':id2' => $idVisto, ':id3' => $idVisto]);
$amigosDelVisto = $stmtAmigosVisto->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <title><?= htmlspecialchars($amigo['username']) ?> · TakeOne</title>
  <?php require_once 'includes/head.php'; ?>
</head>

<body>

  <?php include 'includes/header.php'; ?>

  <main class="py-4">
    <div class="container" style="max-width:var(--container-max);">
      <div class="perfil-hero">

        <!-- ── Cabecera de perfil ──────────────────────────────────────────── -->
        <div class="perfil-header">
          <div class="perfil-avatar-wrapper">
            <?php if (!empty($amigo['avatar'])): ?>
              <img src="<?= htmlspecialchars($amigo['avatar']) ?>" alt="avatar"
                class="perfil-avatar-large">
            <?php else: ?>
              <div class="perfil-avatar-large" style="display:flex;align-items:center;justify-content:center;background:var(--accent-light);font-size:3rem;font-weight:700;color:#fff;">
                <?= $inicialAmigo ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="perfil-info">
            <h1 class="perfil-username"><?= htmlspecialchars($amigo['username']) ?></h1>
            <p class="perfil-tagline">
              <?= !empty($amigo['biografia'])
                ? htmlspecialchars($amigo['biografia'])
                : 'Este usuario todavía no ha escrito su biografía.' ?>
            </p>
            <?php if (!empty($amigo['localidad'])): ?>
              <p class="perfil-location">
                <span class="material-icons-outlined">location_on</span>
                <?= htmlspecialchars($amigo['localidad']) ?>
              </p>
            <?php endif; ?>
          </div>

          <!-- Acciones de amistad -->
          <div class="perfil-acciones" id="perfil-acciones">
            <?php if ($sonAmigos): ?>
              <a href="chat-privado.php?id=<?= $idVisto ?>" class="btn-accion btn-chat">
                <span class="material-icons-outlined">chat</span>
                Abrir chat
              </a>
              <button class="btn-accion btn-eliminar-amigo" id="btnEliminarAmigo"
                data-id="<?= $idVisto ?>">
                <span class="material-icons-outlined">person_remove</span>
                Eliminar amigo
              </button>
            <?php elseif ($estadoAmistad === 'pendiente' && $soyEmisor): ?>
              <span class="btn-accion btn-estado">
                <span class="material-icons-outlined">schedule</span>
                Solicitud enviada
              </span>
            <?php elseif ($estadoAmistad === 'pendiente' && !$soyEmisor): ?>
              <button class="btn-accion btn-chat" id="btnAceptar">
                <span class="material-icons-outlined">person_add</span>
                Aceptar solicitud
              </button>
              <button class="btn-accion btn-eliminar-amigo" id="btnRechazar">
                Rechazar
              </button>
            <?php else: ?>
              <button class="btn-accion btn-chat" id="btnEnviar">
                <span class="material-icons-outlined">person_add</span>
                Añadir amigo
              </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- ── Películas favoritas ────────────────────────────────────────── -->
        <section class="perfil-section">
          <h2 class="perfil-section-title">Películas favoritas</h2>
          <div class="perfil-movies-grid">
            <?php if (!empty($favoritas)):
              foreach ($favoritas as $peli): ?>
                <a href="detalle-pelicula.php?id=<?= (int)$peli['id_pelicula'] ?>" class="perfil-movie-card">
                  <img src="<?= htmlspecialchars($peli['poster'] ?: 'img/placeholder-movie.jpg') ?>"
                    alt="<?= htmlspecialchars($peli['titulo']) ?>"
                    onerror="this.src='img/placeholder-movie.jpg'"
                    loading="lazy">
                </a>
              <?php endforeach;
              for ($i = count($favoritas); $i < 5; $i++): ?>
                <div class="perfil-movie-card perfil-movie-card-empty">
                  <span class="material-icons-outlined">movie_creation</span>
                </div>
              <?php endfor;
            else:
              for ($i = 0; $i < 5; $i++): ?>
                <div class="perfil-movie-card perfil-movie-card-empty">
                  <span class="material-icons-outlined">movie_creation</span>
                </div>
            <?php endfor;
            endif; ?>
          </div>
        </section>

        <!-- ── Géneros favoritos ──────────────────────────────────────────── -->
        <section class="perfil-section">
          <h2 class="perfil-section-title">Géneros favoritos</h2>
          <div class="perfil-genres">
            <?php if (!empty($generos)):
              foreach ($generos as $g): ?>
                <span class="perfil-genre-tag"><?= htmlspecialchars($g) ?></span>
              <?php endforeach;
            else: ?>
              <p class="perfil-empty-text">Este usuario todavía no ha elegido géneros favoritos.</p>
            <?php endif; ?>
          </div>
        </section>

        <!-- ── Amigos ─────────────────────────────────────────────────────── -->
        <?php if (!empty($amigosDelVisto)): ?>
          <section class="perfil-section">
            <h2 class="perfil-section-title">Amigos</h2>
            <div class="perfil-amigos-grid">
              <?php foreach ($amigosDelVisto as $a):
                $avatarA  = !empty($a['avatar']) ? $a['avatar'] : null;
                $inicialA = strtoupper(substr($a['username'], 0, 1)); ?>
                <a href="perfil-amigo.php?id=<?= (int)$a['id_usuario'] ?>" class="perfil-amigo-card">
                  <div class="perfil-amigo-avatar">
                    <?php if ($avatarA): ?>
                      <img src="<?= htmlspecialchars($avatarA) ?>"
                        alt="<?= htmlspecialchars($a['username']) ?>">
                    <?php else: ?>
                      <div class="perfil-amigo-inicial"><?= $inicialA ?></div>
                    <?php endif; ?>
                  </div>
                  <span class="perfil-amigo-username"><?= htmlspecialchars($a['username']) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

      </div><!-- /.perfil-hero -->
    </div><!-- /.container -->
  </main>

  <!-- MODAL ELIMINAR AMIGO -->
  <div class="modal-overlay" id="modalEliminar" hidden>
    <div class="modal-box">
      <p>¿Eliminar a <strong id="modalNombreEliminar"><?= htmlspecialchars($amigo['username']) ?></strong> de tus amigos?</p>
      <div class="modal-actions">
        <button class="btn-secondary" id="modalCancelar">Cancelar</button>
        <button class="btn-danger" id="modalConfirmar">Eliminar</button>
      </div>
    </div>
  </div>

  <script>
    const API = 'api/amigos-api.php';

    async function apiPost(accion, body = {}) {
      const r = await fetch(API, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          accion,
          ...body
        }),
      });
      return r.json();
    }

    // ── Botón Añadir amigo ────────────────────────────────────────────────────
    document.getElementById('btnEnviar')?.addEventListener('click', async function() {
      this.disabled = true;
      const res = await apiPost('enviar', {
        id_usuario: <?= $idVisto ?>
      });
      if (res.ok) {
        this.outerHTML = `
      <span class="btn-accion btn-estado">
        <span class="material-icons-outlined">schedule</span>
        Solicitud enviada
      </span>`;
      } else {
        alert(res.msg);
        this.disabled = false;
      }
    });

    // ── Botón Aceptar solicitud ───────────────────────────────────────────────
    document.getElementById('btnAceptar')?.addEventListener('click', async function() {
      this.disabled = true;
      const res = await apiPost('aceptar', {
        id_usuario: <?= $idVisto ?>
      });
      if (res.ok) {
        location.reload();
      } else {
        alert(res.msg);
        this.disabled = false;
      }
    });

    // ── Botón Rechazar solicitud ──────────────────────────────────────────────
    document.getElementById('btnRechazar')?.addEventListener('click', async function() {
      this.disabled = true;
      const res = await apiPost('rechazar', {
        id_usuario: <?= $idVisto ?>
      });
      if (res.ok) {
        location.reload();
      } else {
        alert(res.msg);
        this.disabled = false;
      }
    });

    // ── Modal Eliminar amigo ──────────────────────────────────────────────────
    document.getElementById('btnEliminarAmigo')?.addEventListener('click', () => {
      document.getElementById('modalEliminar').hidden = false;
    });

    document.getElementById('modalCancelar')?.addEventListener('click', () => {
      document.getElementById('modalEliminar').hidden = true;
    });

    document.getElementById('modalConfirmar')?.addEventListener('click', async () => {
      const res = await apiPost('eliminar', {
        id_usuario: <?= $idVisto ?>
      });
      if (res.ok) {
        window.location.href = 'amigos.php';
      } else {
        alert(res.msg);
        document.getElementById('modalEliminar').hidden = true;
      }
    });

    document.getElementById('modalEliminar')?.addEventListener('click', e => {
      if (e.target === document.getElementById('modalEliminar'))
        document.getElementById('modalEliminar').hidden = true;
    });
  </script>

  <?php include 'includes/footer.php'; ?>

</body>

</html>