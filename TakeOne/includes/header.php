<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$usuario = $_SESSION['usuario'] ?? null;
$avatar   = $usuario['avatar']   ?? null;
$username = $usuario['username'] ?? '';
$inicial  = strtoupper(substr($username, 0, 1));

// Detectar la página actual para marcar el nav activo
$paginaActual = basename($_SERVER['PHP_SELF']);

// Contadores de notificaciones para el badge del dropdown
$numPendientesHeader = 0;
$numMensajesHeader   = 0;

if ($usuario) {
  require_once 'includes/conexion.php';

  // Solicitudes de amistad pendientes
  $stmtBadge = $pdo->prepare("
        SELECT COUNT(*) FROM amistades
        WHERE id_receptor = :yo AND estado = 'pendiente'
    ");
  $stmtBadge->execute([':yo' => (int) $usuario['id']]);
  $numPendientesHeader = (int) $stmtBadge->fetchColumn();

  // Mensajes privados no leídos
  $stmtMsg = $pdo->prepare("
        SELECT COUNT(*) FROM mensajes_privados
        WHERE id_receptor = :yo AND leido = 0 AND estado = 'activo'
    ");
  $stmtMsg->execute([':yo' => (int) $usuario['id']]);
  $numMensajesHeader = (int) $stmtMsg->fetchColumn();
}

$totalBadge = $numPendientesHeader + $numMensajesHeader;
?>

<style>
  .avatar-link {
    position: relative;
    display: inline-flex;
    align-items: center;
  }

  .avatar-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 22px;
    height: 22px;
    background: #14b8a6;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
    z-index: 999;
    line-height: 1;
  }


  .dropdown-item--with-badge {
    position: relative;
    padding-right: 3rem !important;
  }

  .dropdown-badge {
    position: absolute;
    top: 50%;
    right: 1.1rem;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    background: #14b8a6;
    color: #fff;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 800;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
    line-height: 1;
    z-index: 2;
  }
</style>


<header class="site-header">
  <div class="container" style="max-width:var(--container-max);">
    <nav class="site-nav">
      <!-- Izquierda: logo y nombre -->
      <div class="nav-left">
        <a href="seccionPrincipal.php" class="home-link">
          <img src="img/logo gato sin fondo.png" alt="TakeOne Logo" class="brand-logo">
          <span class="site-name">TakeOne</span>
        </a>
      </div>

      <!-- Menú links -->
      <ul class="nav-center">
        <li><a href="peliculas.php" class="<?= $paginaActual === 'peliculas.php'  ? 'active' : '' ?>">Películas</a></li>
        <li><a href="listas.php" class="<?= $paginaActual === 'listas.php'     ? 'active' : '' ?>">Listas</a></li>
        <li><a href="sugerir.php" class="<?= $paginaActual === 'sugerir.php'    ? 'active' : '' ?>">Sugerir</a></li>
        <li><a href="comunidad.php" class="<?= $paginaActual === 'comunidad.php'  ? 'active' : '' ?>">Comunidad</a></li>
        <li><a href="noticias.php" class="<?= $paginaActual === 'noticias.php'   ? 'active' : '' ?>">Noticias</a></li>
      </ul>

      <!-- Derecha: avatar + dropdown -->
      <div class="nav-right">
        <div class="avatar-dropdown">
          <a class="avatar-link" id="avatarToggle">
            <div class="avatar">
              <?php if (!empty($avatar)): ?>
                <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar">
              <?php else: ?>
                <?= $inicial ?>
              <?php endif; ?>
            </div>
            <span class="avatar-badge"
              data-pendientes="<?= $numPendientesHeader ?>"
              <?php if ($totalBadge === 0): ?>style="display:none" <?php endif; ?>>
              <?= $totalBadge ?: '' ?>
            </span>
          </a>

          <div class="dropdown-menu" id="dropdownMenu">

            <a href="seccionPrincipal.php"
              class="dropdown-item <?= $paginaActual === 'seccionPrincipal.php' ? 'active' : '' ?>">
              <span class="material-icons-outlined">home</span>
              <span>Inicio</span>
            </a>

            <a href="perfil.php"
              class="dropdown-item <?= $paginaActual === 'perfil.php' ? 'active' : '' ?>">
              <span class="material-icons-outlined">person</span>
              <span>Mi perfil</span>
            </a>

            <a href="amigos.php"
              class="dropdown-item dropdown-item--with-badge <?= $paginaActual === 'amigos.php' ? 'active' : '' ?>">
              <span class="material-icons-outlined">group</span>
              <span>Amigos</span>

              <?php if ($totalBadge > 0): ?>
                <span class="dropdown-badge"
                  title="<?= $totalBadge ?> notificación(es) pendiente(s)">
                  !
                </span>
              <?php endif; ?>
            </a>

            <a href="pendientes.php"
              class="dropdown-item <?= $paginaActual === 'pendientes.php' ? 'active' : '' ?>">
              <span class="material-icons-outlined">schedule</span>
              <span>Pendientes</span>
            </a>

            <a href="favoritas.php"
              class="dropdown-item <?= $paginaActual === 'favoritas.php' ? 'active' : '' ?>">
              <span class="material-icons-outlined">favorite</span>
              <span>Favoritas</span>
            </a>

            <a href="mis-listas.php"
              class="dropdown-item <?= $paginaActual === 'mis-listas.php' ? 'active' : '' ?>">
              <span class="material-icons-outlined">list_alt</span>
              <span>Mis listas</span>
            </a>

            <a href="actividad.php"
              class="dropdown-item <?= $paginaActual === 'actividad.php' ? 'active' : '' ?>">
              <span class="material-icons-outlined">event_note</span>
              <span>Actividad</span>
            </a>

            <div class="dropdown-divider"></div>

            <a href="logout.php" class="dropdown-item logout" id="logoutBtn">
              <span class="material-icons-outlined">logout</span>
              <span>Cerrar sesión</span>
            </a>

          </div>
        </div>

        <!-- botón hamburguesa -->
        <button class="hamburger">☰</button>
      </div>
    </nav>
  </div>
</header>