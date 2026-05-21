<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.html");
    exit;
}

require_once 'includes/conexion.php';

$id_usuario = $_SESSION['usuario']['id'];
$id_grupo   = (int)($_GET['id'] ?? 0);

if (!$id_grupo) {
    header("Location: comunidad.php");
    exit;
}

// Verificar que el grupo existe
$stmtGrupo = $pdo->prepare("
    SELECT g.*, ge.nombre AS nombre_genero,
           COUNT(gu.id_usuario) AS num_miembros
    FROM grupos g
    LEFT JOIN generos ge ON g.id_genero = ge.id_genero
    LEFT JOIN grupos_usuarios gu ON g.id_grupo = gu.id_grupo
    WHERE g.id_grupo = :id
    GROUP BY g.id_grupo
");
$stmtGrupo->execute([':id' => $id_grupo]);
$grupo = $stmtGrupo->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header("Location: comunidad.php");
    exit;
}

// Verificar si el usuario es miembro
$stmtMiembro = $pdo->prepare("SELECT 1 FROM grupos_usuarios WHERE id_grupo = :g AND id_usuario = :u");
$stmtMiembro->execute([':g' => $id_grupo, ':u' => $id_usuario]);
$es_miembro = (bool)$stmtMiembro->fetch();

// Miembros del grupo (para la barra lateral)
$stmtMiembros = $pdo->prepare("
    SELECT u.id_usuario, u.username, u.avatar
    FROM grupos_usuarios gu
    JOIN usuarios u ON gu.id_usuario = u.id_usuario
    WHERE gu.id_grupo = :g
    ORDER BY u.username ASC
");
$stmtMiembros->execute([':g' => $id_grupo]);
$miembros = $stmtMiembros->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($grupo['nombre']); ?> - TakeOne</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container" style="max-width:var(--container-max);">

            <?php if (!$es_miembro): ?>
                <!-- Usuario no miembro -->
                <div class="chat-no-miembro">
                    <img src="<?php echo htmlspecialchars($grupo['imagen'] ?? 'img/logo gato sin fondo.png'); ?>"
                        alt="<?php echo htmlspecialchars($grupo['nombre']); ?>">
                    <h2><?php echo htmlspecialchars($grupo['nombre']); ?></h2>
                    <p><?php echo htmlspecialchars($grupo['descripcion']); ?></p>
                    <p>👥 <?php echo number_format($grupo['num_miembros']); ?> miembros</p>
                    <button class="create-group-btn" id="btnUnirseDesdeChat" data-id="<?php echo $id_grupo; ?>">
                        Unirse al grupo para chatear
                    </button>
                </div>

            <?php else: ?>
                <!-- Chat principal -->
                <div class="chat-wrapper">

                    <!-- Panel izquierdo: info del grupo + miembros -->
                    <aside class="chat-sidebar">
                        <div class="chat-grupo-info">
                            <img src="<?php echo htmlspecialchars($grupo['imagen'] ?? 'img/logo gato sin fondo.png'); ?>"
                                alt="<?php echo htmlspecialchars($grupo['nombre']); ?>"
                                class="chat-grupo-img">
                            <h3><?php echo htmlspecialchars($grupo['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars($grupo['descripcion']); ?></p>
                            <span class="chat-grupo-meta">
                                👥 <?php echo number_format($grupo['num_miembros']); ?> miembros
                                <?php if ($grupo['nombre_genero']): ?>
                                    · <?php echo htmlspecialchars($grupo['nombre_genero']); ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="chat-miembros">
                            <h4>Miembros</h4>
                            <ul>
                                <?php foreach ($miembros as $m): ?>
                                    <li class="chat-miembro-item">
                                        <?php if ($m['avatar']): ?>
                                            <img src="<?php echo htmlspecialchars($m['avatar']); ?>"
                                                alt="<?php echo htmlspecialchars($m['username']); ?>"
                                                class="chat-miembro-avatar">
                                        <?php else: ?>
                                            <div class="chat-miembro-avatar chat-miembro-inicial">
                                                <?php echo strtoupper(substr($m['username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>

                                        <span><?php echo htmlspecialchars($m['username']); ?></span>

                                        <?php if ($m['id_usuario'] == $grupo['id_usuario']): ?>
                                            <span class="chat-badge-admin">Admin</span>
                                        <?php endif; ?>

                                        <!-- Botón expulsar: solo visible para el creador y sobre otros miembros -->
                                        <?php if ($id_usuario == $grupo['id_usuario'] && $m['id_usuario'] != $id_usuario): ?>
                                            <button class="btn-expulsar-miembro"
                                                data-id="<?php echo $m['id_usuario']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($m['username']); ?>"
                                                title="Expulsar a <?php echo htmlspecialchars($m['username']); ?>">
                                                <span class="material-icons-outlined" style="font-size:16px;">person_remove</span>
                                            </button>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <?php if ($id_usuario == $grupo['id_usuario']): ?>
                            <button class="btn-editar-grupo" id="btnEditarGrupo">
                                <span class="material-icons-outlined">edit</span>
                                Editar grupo
                            </button>
                        <?php endif; ?>

                        <button class="btn-salir-grupo" id="btnSalirGrupo" data-id="<?php echo $id_grupo; ?>">
                            <span class="material-icons-outlined">logout</span>
                            Salir del grupo
                        </button>
                    </aside>

                    <!-- Panel derecho: chat -->
                    <div class="chat-main">
                        <div class="chat-header">
                            <a href="comunidad.php" class="btn-volver-chat">
                                <span class="material-icons-outlined">arrow_back</span>
                            </a>
                            <img src="<?php echo htmlspecialchars($grupo['imagen'] ?? 'img/logo gato sin fondo.png'); ?>"
                                alt="" class="chat-header-img">
                            <div>
                                <h4><?php echo htmlspecialchars($grupo['nombre']); ?></h4>
                                <small><?php echo number_format($grupo['num_miembros']); ?> miembros</small>
                            </div>
                        </div>

                        <div class="chat-mensajes" id="chatMensajes">
                            <p class="chat-cargando">Cargando mensajes...</p>
                        </div>

                        <div class="chat-input-area">
                            <input type="text" id="chatInput" placeholder="Escribe un mensaje..."
                                maxlength="1000" autocomplete="off">
                            <button id="chatEnviar">
                                <span class="material-icons-outlined">send</span>
                            </button>
                        </div>
                    </div>

                </div>
            <?php endif; ?>

        </div>

        <?php if ($id_usuario == $grupo['id_usuario']): ?>
            <div class="modal-overlay-mis-listas" id="modalEditarGrupo" style="display:none;">
                <div class="modal-content-mis-listas">
                    <button class="modal-close-mis-listas" id="modalCloseEditar">
                        <span class="material-icons-outlined">close</span>
                    </button>
                    <h2>Editar grupo</h2>

                    <div id="modal-editar-mensaje" class="alert d-none mb-3"></div>

                    <div class="form-group">
                        <label for="editNombre">Nombre del grupo *</label>
                        <input type="text" id="editNombre"
                            value="<?php echo htmlspecialchars($grupo['nombre']); ?>"
                            maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label for="editDescripcion">Descripción</label>
                        <textarea id="editDescripcion" rows="3"
                            maxlength="500"><?php echo htmlspecialchars($grupo['descripcion']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="editGenero">Género cinematográfico</label>
                        <select id="editGenero">
                            <option value="">— Sin género específico —</option>
                            <?php
                            $generosEdit = $pdo->query("SELECT id_genero, nombre FROM generos ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($generosEdit as $g):
                            ?>
                                <option value="<?php echo $g['id_genero']; ?>"
                                    <?php echo $g['id_genero'] == $grupo['id_genero'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($g['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="editTipo">Tipo de actividad *</label>
                        <select id="editTipo" required>
                            <?php
                            $tipos = ['debates' => 'Debates', 'recomendaciones' => 'Recomendaciones', 'reseñas' => 'Reseñas', 'club-cine' => 'Club de cine'];
                            foreach ($tipos as $val => $label):
                            ?>
                                <option value="<?php echo $val; ?>"
                                    <?php echo $val === $grupo['tipo'] ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Imagen del grupo <small style="color:#6c757d;">(opcional, máx. 2 MB)</small></label>
                        <div class="portada-upload" id="portadaEditUpload">
                            <img id="portadaEditPreviewImg"
                                src="<?php echo htmlspecialchars($grupo['imagen'] ?? ''); ?>"
                                alt=""
                                style="<?php echo $grupo['imagen'] ? 'display:block;' : 'display:none;'; ?> width:100%; height:100%; object-fit:cover; border-radius:8px;">
                            <div id="portadaEditPlaceholder" style="<?php echo $grupo['imagen'] ? 'display:none;' : 'display:flex;'; ?> flex-direction:column; align-items:center; gap:0.5rem;">
                                <span class="material-icons-outlined" style="font-size:2rem; color:#aaa;">add_photo_alternate</span>
                                <span style="color:#aaa; font-size:0.9rem;">Haz clic para cambiar la imagen</span>
                            </div>
                        </div>
                        <input type="file" id="editPortada" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancelar" id="cancelarEditarBtn">Cancelar</button>
                        <button type="button" class="btn-guardar" id="guardarEditarBtn">Guardar cambios</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="modal-overlay-mis-listas" id="modalEditarMensaje" style="display:none;">
            <div class="modal-content-mis-listas" style="max-width:500px;">
                <button class="modal-close-mis-listas" id="cerrarModalMensaje">
                    <span class="material-icons-outlined">close</span>
                </button>
                <h2>Editar mensaje</h2>
                <div id="modal-mensaje-editar-aviso" class="alert d-none mb-3"></div>
                <div class="form-group">
                    <textarea id="editarMensajeTexto" rows="4" maxlength="1000"
                        style="width:100%; background:rgba(255,255,255,0.08); border:2px solid rgba(255,255,255,0.1); border-radius:1rem; padding:0.75rem 1rem; color:black; font-size:0.95rem; outline:none; resize:vertical;"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancelar" id="cancelarEditarMensaje">Cancelar</button>
                    <button type="button" class="btn-guardar" id="guardarEditarMensaje">Guardar</button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        const ID_GRUPO = <?php echo $id_grupo; ?>;
        const ID_USUARIO = <?php echo $id_usuario; ?>;
        const ES_MIEMBRO = <?php echo $es_miembro ? 'true' : 'false'; ?>;
    </script>
    <script src="js/chat-grupo.js"></script>
</body>

</html>