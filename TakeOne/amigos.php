<?php
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

// Conteo de solicitudes pendientes (para el badge)
$stmtP = $pdo->prepare("
    SELECT COUNT(*) FROM amistades
    WHERE id_receptor = :yo AND estado = 'pendiente'
");
$stmtP->execute([':yo' => $yo]);
$numPendientes = (int) $stmtP->fetchColumn();

// Mensajes privados no leídos agrupados por amigo
$mensajesNoLeidosPorAmigo = [];
$stmtMsgAmigos = $pdo->prepare("
    SELECT id_emisor, COUNT(*) AS total
    FROM mensajes_privados
    WHERE id_receptor = :yo
      AND leido = 0
      AND estado = 'activo'
    GROUP BY id_emisor
");
$stmtMsgAmigos->execute([':yo' => $yo]);
foreach ($stmtMsgAmigos->fetchAll(PDO::FETCH_ASSOC) as $row) {
  $mensajesNoLeidosPorAmigo[(int) $row['id_emisor']] = (int) $row['total'];
}

// Tab activa
$tab = $_GET['tab'] ?? 'mis_amigos';
if (!in_array($tab, ['mis_amigos', 'buscar', 'pendientes'])) {
  $tab = 'mis_amigos';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Amigos · TakeOne</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
  <style>
    .chat-btn-with-badge {
      position: relative;
    }

    .chat-icon-badge {
      position: absolute;
      top: -6px;
      right: -6px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: #ff3b30;
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 800;
      border: 2px solid #fff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
      line-height: 1;
      z-index: 5;
    }
  </style>
</head>

<body>

  <?php include 'includes/header.php'; ?>

  <main class="py-4">
    <div class="container" style="max-width:var(--container-max);">

      <div class="card-hero card-hero-amigos mb-4">

        <header class="amigos-header">
          <h1 class="titulo-amigos">Amigos</h1>
          <p class="amigos-subtitulo">Busca, añade y habla con tus amigos desde un único lugar, con chats privados y conexión en tiempo real.</p>
        </header>

        <!-- TABS ----------------------------------------------------------------->
        <nav class="amigos-tabs" role="tablist">
          <button class="tab-btn tab-btn-amigos <?= $tab === 'mis_amigos' ? 'active' : '' ?>"
            data-tab="mis_amigos" role="tab">
            Mis amigos
          </button>

          <button class="tab-btn tab-btn-amigos <?= $tab === 'buscar' ? 'active' : '' ?>"
            data-tab="buscar" role="tab">
            Añadir amigo
          </button>

          <button class="tab-btn tab-btn-amigos <?= $tab === 'pendientes' ? 'active' : '' ?>"
            data-tab="pendientes" role="tab" id="tabPendientes">
            Solicitudes
            <span class="badge-tab" id="badgeTab"
              <?= $numPendientes === 0 ? 'style="display:none"' : '' ?>>
              <?= $numPendientes ?>
            </span>
          </button>
        </nav>

        <!-- PANEL: MIS AMIGOS ---------------------------------------------------->
        <section class="tab-panel <?= $tab === 'mis_amigos' ? 'active' : '' ?>"
          id="panel-mis_amigos">

          <div class="search-bar-gradient-wrap">
              <div class="search-bar">
                  <span class="material-icons-outlined">search</span>
                  <input type="text" id="buscadorAmigos" placeholder="Busca entre tus amigos" autocomplete="off">
              </div>
          </div>

          <div class="amigos-grid" id="gridAmigos">
            <div class="spinner"></div>
          </div>
        </section>

        <!-- PANEL: BUSCAR / AÑADIR ----------------------------------------------->
        <section class="tab-panel <?= $tab === 'buscar' ? 'active' : '' ?>"
          id="panel-buscar">

          <div class="search-bar">
            <span class="material-icons-outlined">search</span>
            <input type="text" id="buscadorGlobal" placeholder="Buscar usuarios por nombre…"
              autocomplete="off">
          </div>

          <div class="amigos-grid" id="gridBuscar">
            <p class="empty-msg">Escribe un nombre para buscar usuarios.</p>
          </div>
        </section>

        <!-- PANEL: SOLICITUDES PENDIENTES ---------------------------------------->
        <section class="tab-panel <?= $tab === 'pendientes' ? 'active' : '' ?>"
          id="panel-pendientes">
          <div class="amigos-grid" id="gridPendientes">
            <div class="spinner"></div>
          </div>
        </section>

      </div><!-- /.card-hero-amigos -->
    </div><!-- /.container -->
  </main>

  <!-- MODAL CONFIRMAR ELIMINAR ------------------------------------------------->
  <div class="modal-overlay" id="modalEliminar" hidden>
    <div class="modal-box">
      <p>¿Eliminar a <strong id="modalNombre"></strong> de tus amigos?</p>
      <div class="modal-actions">
        <button class="btn-secondary" id="modalCancelar">Cancelar</button>
        <button class="btn-danger" id="modalConfirmar">Eliminar</button>
      </div>
    </div>
  </div>

  <script>
    // ─── Estado ────────────────────────────────────────────────────────────────
    const API = 'api/amigos-api.php';
    const mensajesNoLeidosPorAmigo = <?= json_encode($mensajesNoLeidosPorAmigo, JSON_UNESCAPED_UNICODE) ?>;
    let pendienteEliminar = null;

    // ─── Utilidades ────────────────────────────────────────────────────────────
    async function api(accion, body = {}) {
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

    function avatarHtml(u) {
      if (u.avatar) {
        return `<img src="${u.avatar}" alt="${u.username}" class="avatar-img">`;
      }
      return `<div class="avatar-inicial">${u.inicial}</div>`;
    }

    function actualizarBadge(n) {
      const badge = document.getElementById('badgeTab');
      if (n > 0) {
        badge.textContent = n;
        badge.style.display = '';
      } else {
        badge.style.display = 'none';
      }
    }

    // ─── Tabs ──────────────────────────────────────────────────────────────────
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('panel-' + btn.dataset.tab).classList.add('active');

        if (btn.dataset.tab === 'mis_amigos') cargarAmigos();
        if (btn.dataset.tab === 'pendientes') cargarPendientes();
      });
    });

    // ─── MIS AMIGOS ────────────────────────────────────────────────────────────
    async function cargarAmigos(q = '') {
      const grid = document.getElementById('gridAmigos');
      grid.innerHTML = '<div class="spinner"></div>';
      const res = await api('mis_amigos', {
        q
      });

      if (!res.ok) {
        grid.innerHTML = '<p class="empty-msg">Error al cargar amigos.</p>';
        return;
      }
      if (!res.amigos.length) {
        grid.innerHTML = q ?
          '<p class="empty-msg">Ningún amigo coincide con tu búsqueda.</p>' :
          '<p class="empty-msg">Aún no tienes amigos. ¡Usa la pestaña «Añadir amigo» para encontrarlos!</p>';
        return;
      }

      grid.innerHTML = res.amigos.map(u => `
    <div class="amigo-card" data-id="${u.id}">
      <a href="perfil-amigo.php?id=${u.id}" class="card-avatar">
        ${avatarHtml(u)}
      </a>
      <a href="perfil-amigo.php?id=${u.id}" class="card-username">${u.username}</a>
      <div class="card-actions">
        <a href="chat-privado.php?id=${u.id}" class="btn-icon-sm chat-btn-with-badge" title="Abrir chat">
          <span class="material-icons-outlined">chat</span>
          ${Number(mensajesNoLeidosPorAmigo[u.id] || 0) > 0 ? '<span class="chat-icon-badge">!</span>' : ''}
        </a>
        <a href="perfil-amigo.php?id=${u.id}" class="btn-icon-sm" title="Ver perfil">
          <span class="material-icons-outlined">person</span>
        </a>
        <button class="btn-icon-sm btn-danger-icon" title="Eliminar amigo"
                onclick="abrirModalEliminar(${u.id}, '${u.username}')">
          <span class="material-icons-outlined">person_remove</span>
        </button>
      </div>
    </div>
  `).join('');
    }

    let timerAmigos;
    document.getElementById('buscadorAmigos').addEventListener('input', e => {
      clearTimeout(timerAmigos);
      timerAmigos = setTimeout(() => cargarAmigos(e.target.value.trim()), 350);
    });

    // ─── BUSCAR / AÑADIR ───────────────────────────────────────────────────────
    async function buscarUsuarios(q) {
      const grid = document.getElementById('gridBuscar');
      if (q.length < 2) {
        grid.innerHTML = '<p class="empty-msg">Escribe al menos 2 caracteres para buscar.</p>';
        return;
      }
      grid.innerHTML = '<div class="spinner"></div>';
      const res = await api('buscar', {
        q
      });
      if (!res.ok) {
        grid.innerHTML = '<p class="empty-msg">Error en la búsqueda.</p>';
        return;
      }
      if (!res.usuarios.length) {
        grid.innerHTML = '<p class="empty-msg">No se encontraron usuarios.</p>';
        return;
      }

      grid.innerHTML = res.usuarios.map(u => {
        let accionHtml = '';
        if (u.estado === 'aceptada') {
          accionHtml = `<span class="badge-estado amigos">Ya sois amigos</span>`;
        } else if (u.estado === 'pendiente' && u.soy_emisor) {
          accionHtml = `<span class="badge-estado pendiente">Solicitud enviada</span>`;
        } else if (u.estado === 'pendiente' && !u.soy_emisor) {
          accionHtml = `
        <button class="btn-sm btn-primary" onclick="responderSolicitud(${u.id}, 'aceptar', this)">Aceptar</button>
        <button class="btn-sm btn-secondary" onclick="responderSolicitud(${u.id}, 'rechazar', this)">Rechazar</button>`;
        } else {
          accionHtml = `
        <button class="btn-sm btn-primary" id="enviar-${u.id}"
                onclick="enviarSolicitud(${u.id}, '${u.username}', this)">
          <span class="material-icons-outlined">person_add</span> Añadir
        </button>`;
        }
        return `
      <div class="amigo-card" id="card-buscar-${u.id}">
        <a href="perfil-amigo.php?id=${u.id}" class="card-avatar">${avatarHtml(u)}</a>
        <a href="perfil-amigo.php?id=${u.id}" class="card-username">${u.username}</a>
        <div class="card-actions">${accionHtml}</div>
      </div>`;
      }).join('');
    }

    async function enviarSolicitud(id, username, btn) {
      btn.disabled = true;
      const res = await api('enviar', {
        id_usuario: id
      });
      if (res.ok) {
        btn.closest('.card-actions').innerHTML =
          `<span class="badge-estado pendiente">Solicitud enviada</span>`;
      } else {
        btn.disabled = false;
        alert(res.msg);
      }
    }

    async function responderSolicitud(id, accion, btn) {
      btn.disabled = true;
      const res = await api(accion, {
        id_usuario: id
      });
      if (res.ok) {
        const card = document.getElementById(`card-buscar-${id}`);
        if (accion === 'aceptar') {
          card.querySelector('.card-actions').innerHTML =
            `<span class="badge-estado amigos">Ya sois amigos</span>`;
        } else {
          card.remove();
        }
        if (accion === 'aceptar') cargarPendientes();
      } else {
        btn.disabled = false;
        alert(res.msg);
      }
    }

    let timerBuscar;
    document.getElementById('buscadorGlobal').addEventListener('input', e => {
      clearTimeout(timerBuscar);
      timerBuscar = setTimeout(() => buscarUsuarios(e.target.value.trim()), 400);
    });

    // ─── SOLICITUDES PENDIENTES ────────────────────────────────────────────────
    async function cargarPendientes() {
      const grid = document.getElementById('gridPendientes');
      grid.innerHTML = '<div class="spinner"></div>';
      const res = await api('pendientes');
      actualizarBadge(res.total ?? 0);

      if (!res.ok) {
        grid.innerHTML = '<p class="empty-msg">Error al cargar solicitudes.</p>';
        return;
      }
      if (!res.pendientes.length) {
        grid.innerHTML = '<p class="empty-msg">No tienes solicitudes de amistad pendientes.</p>';
        return;
      }

      grid.innerHTML = res.pendientes.map(u => `
    <div class="amigo-card" id="card-pend-${u.id}">
      <a href="perfil-amigo.php?id=${u.id}" class="card-avatar">${avatarHtml(u)}</a>
      <a href="perfil-amigo.php?id=${u.id}" class="card-username">${u.username}</a>
      <div class="card-actions">
        <button class="btn-sm btn-primary"
                onclick="gestionarPendiente(${u.id}, 'aceptar')">Aceptar</button>
        <button class="btn-sm btn-secondary"
                onclick="gestionarPendiente(${u.id}, 'rechazar')">Rechazar</button>
      </div>
    </div>
  `).join('');
    }

    async function gestionarPendiente(id, accion) {
      const res = await api(accion, {
        id_usuario: id
      });
      if (res.ok) {
        document.getElementById(`card-pend-${id}`)?.remove();
        const nuevo = (document.querySelectorAll('#gridPendientes .amigo-card').length);
        actualizarBadge(nuevo);
        if (accion === 'aceptar') cargarAmigos();
        if (nuevo === 0) {
          document.getElementById('gridPendientes').innerHTML =
            '<p class="empty-msg">No tienes solicitudes de amistad pendientes.</p>';
        }
      } else {
        alert(res.msg);
      }
    }

    // ─── MODAL ELIMINAR ────────────────────────────────────────────────────────
    function abrirModalEliminar(id, nombre) {
      pendienteEliminar = id;
      document.getElementById('modalNombre').textContent = nombre;
      document.getElementById('modalEliminar').hidden = false;
    }

    document.getElementById('modalCancelar').addEventListener('click', () => {
      document.getElementById('modalEliminar').hidden = true;
    });

    document.getElementById('modalConfirmar').addEventListener('click', async () => {
      if (!pendienteEliminar) return;
      const res = await api('eliminar', {
        id_usuario: pendienteEliminar
      });
      document.getElementById('modalEliminar').hidden = true;
      if (res.ok) {
        document.querySelector(`.amigo-card[data-id="${pendienteEliminar}"]`)?.remove();
        const quedan = document.querySelectorAll('#gridAmigos .amigo-card').length;
        if (quedan === 0) {
          document.getElementById('gridAmigos').innerHTML =
            '<p class="empty-msg">Aún no tienes amigos. ¡Usa la pestaña «Añadir amigo» para encontrarlos!</p>';
        }
      } else {
        alert(res.msg);
      }
      pendienteEliminar = null;
    });

    // Cerrar modal al clicar fuera
    document.getElementById('modalEliminar').addEventListener('click', e => {
      if (e.target === document.getElementById('modalEliminar'))
        document.getElementById('modalEliminar').hidden = true;
    });

    // ─── INIT ──────────────────────────────────────────────────────────────────
    cargarAmigos();
    cargarPendientes(); // para actualizar el badge desde el inicio
  </script>

  <?php include 'includes/footer.php'; ?>

</body>

</html>