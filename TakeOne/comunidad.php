<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.html");
    exit;
}

require_once 'includes/conexion.php';

$id_usuario = $_SESSION['usuario']['id'];

// Filtros recibidos por GET
$filtro_genero = isset($_GET['genero']) ? (int)$_GET['genero'] : 0;
$filtro_tipo   = isset($_GET['tipo'])   ? trim($_GET['tipo'])   : '';
$busqueda      = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$vista         = isset($_GET['vista'])  ? $_GET['vista']        : 'explorar';

// ── Query dinámica ────────────────────────────────────────────
$where  = [];
$params = [];

if ($vista === 'mis-grupos') {
    $where[]  = "gu.id_usuario = :uid";
    $params['uid'] = $id_usuario;
}

if ($filtro_genero > 0) {
    $where[]  = "g.id_genero = :genero";
    $params['genero'] = $filtro_genero;
}

if ($filtro_tipo !== '') {
    $where[]  = "g.tipo = :tipo";
    $params['tipo'] = $filtro_tipo;
}

if ($busqueda !== '') {
    $where[]  = "g.nombre LIKE :buscar";
    $params['buscar'] = "%$busqueda%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT g.*,
           ge.nombre AS nombre_genero,
           COUNT(DISTINCT gu2.id_usuario) AS num_miembros,
           MAX(CASE WHEN gu3.id_usuario = :uid2 THEN 1 ELSE 0 END) AS unido,
           u.username AS creador_username
    FROM grupos g
    LEFT JOIN generos ge ON g.id_genero = ge.id_genero
    LEFT JOIN grupos_usuarios gu ON g.id_grupo = gu.id_grupo
    LEFT JOIN grupos_usuarios gu2 ON g.id_grupo = gu2.id_grupo
    LEFT JOIN grupos_usuarios gu3 ON g.id_grupo = gu3.id_grupo AND gu3.id_usuario = :uid3
    LEFT JOIN usuarios u ON g.id_usuario = u.id_usuario
    $whereSQL
    GROUP BY g.id_grupo
    ORDER BY num_miembros DESC
";

$params['uid2'] = $id_usuario;
$params['uid3'] = $id_usuario;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Géneros para el sidebar ───────────────────────────────────
$generosFiltro = $pdo->query("
    SELECT id_genero, nombre FROM generos
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Penalizaciones vigentes del usuario (todos los grupos) ────
// Una sola consulta para no hacer N queries dentro del foreach.
$stmtPen = $pdo->prepare("
    SELECT id_grupo, motivo, duracion_dias, fecha_fin
    FROM penalizaciones_grupo
    WHERE id_usuario = ?
      AND (fecha_fin IS NULL OR fecha_fin > NOW())
");
$stmtPen->execute([$id_usuario]);
$penalizacionesRaw = $stmtPen->fetchAll(PDO::FETCH_ASSOC);

// Indexamos por id_grupo para acceso O(1) en el foreach
$penalizaciones = [];
foreach ($penalizacionesRaw as $p) {
    $penalizaciones[$p['id_grupo']] = $p;
}

// ── Etiquetas de motivo legibles para el usuario ──────────────
$motivos_usuario = [
    'mala_conducta'         => 'mala conducta',
    'insultos'              => 'insultos o lenguaje ofensivo',
    'spam'                  => 'spam',
    'contenido_inapropiado' => 'publicación de contenido inapropiado',
    'acoso'                 => 'acoso a otros miembros',
    'otro'                  => 'incumplimiento de las normas del grupo',
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <title>Comunidad - TakeOne</title>
  <?php require_once 'includes/head.php'; ?>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container" style="max-width:var(--container-max);">

            <!-- Hero -->
            <div class="community-hero">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
                    <div>
                        <h1>Comunidad</h1>
                        <p>Participa, crea grupos de chat con otros cinéfilos y comparte opiniones.</p>
                    </div>
                    <button class="create-group-btn" id="btnCrearGrupo">
                        <span class="material-icons-outlined">add_circle_outline</span>
                        Crear nuevo grupo
                    </button>
                </div>

                <div class="community-tabs">
                    <a href="comunidad.php?vista=explorar"
                        class="tab-btn <?php echo $vista === 'explorar' ? 'active' : ''; ?>">
                        Explorar grupos
                    </a>
                    <a href="comunidad.php?vista=mis-grupos"
                        class="tab-btn <?php echo $vista === 'mis-grupos' ? 'active' : ''; ?>">
                        Mis grupos
                    </a>
                </div>
            </div>

            <!-- Content -->
            <div class="community-content">

                <!-- Sidebar -->
                <aside class="sidebar">
                    <form method="GET" action="comunidad.php" id="filtroForm">
                        <input type="hidden" name="vista" value="<?php echo htmlspecialchars($vista); ?>">

                        <div class="search-box-gradient-wrap">
                            <div class="search-box">
                                <span class="material-icons-outlined">search</span>
                                <input type="text" id="buscadorGrupos" placeholder="Buscar grupos" autocomplete="off">
                            </div>
                        </div>

                        <div class="filters">
                            <h3>Filtros</h3>

                            <!-- Género -->
                            <div class="filter-section">
                                <h4>Género cinematográfico</h4>
                                <div class="filter-tags">
                                    <button type="submit" name="genero" value="0"
                                        class="filter-tag <?php echo $filtro_genero === 0 ? 'active' : ''; ?>">
                                        Todos
                                    </button>
                                    <?php foreach ($generosFiltro as $g): ?>
                                        <button type="submit" name="genero" value="<?php echo $g['id_genero']; ?>"
                                            class="filter-tag <?php echo $filtro_genero === (int)$g['id_genero'] ? 'active' : ''; ?>">
                                            <?php echo htmlspecialchars($g['nombre']); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Tipo -->
                            <div class="filter-section">
                                <h4>Tipo de actividad</h4>
                                <div class="filter-tags">
                                    <?php
                                    $tipos = ['' => 'Todos', 'debates' => 'Debates', 'recomendaciones' => 'Recomendaciones', 'reseñas' => 'Reseñas', 'club-cine' => 'Club de cine'];
                                    foreach ($tipos as $val => $label): ?>
                                        <button type="submit" name="tipo" value="<?php echo $val; ?>"
                                            class="filter-tag <?php echo $filtro_tipo === $val ? 'active' : ''; ?>">
                                            <?php echo $label; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </aside>

                <!-- Grid de grupos -->
                <div class="groups-grid">
                    <div class="groups-header">
                        <h2>
                            <?php if ($vista === 'mis-grupos'): ?>
                                Mis grupos
                            <?php elseif ($busqueda): ?>
                                Resultados para "<?php echo htmlspecialchars($busqueda); ?>"
                            <?php else: ?>
                                Grupos populares
                            <?php endif; ?>
                        </h2>
                    </div>

                    <div class="groups-container">
                        <?php if (empty($grupos)): ?>
                            <p class="px-3" style="color:white;">
                                <?php echo $vista === 'mis-grupos' ? 'Todavía no te has unido a ningún grupo.' : 'No se encontraron grupos.'; ?>
                            </p>
                        <?php else: ?>
                            <?php foreach ($grupos as $grupo):
                                // ── Penalización vigente para este grupo ──────────
                                $pen       = $penalizaciones[$grupo['id_grupo']] ?? null;
                                $permanente = false;
                                $fecha_fin_fmt = '';

                                if ($pen) {
                                    $permanente    = ($pen['duracion_dias'] == 0 || $pen['fecha_fin'] === null);
                                    $motivo_txt    = $motivos_usuario[$pen['motivo']] ?? 'incumplimiento de las normas';
                                    if (!$permanente) {
                                        $fecha_fin_fmt = date('d/m/Y \a \l\a\s H:i', strtotime($pen['fecha_fin']));
                                    }
                                }
                            ?>
                                <div class="group-card" data-id="<?php echo $grupo['id_grupo']; ?>">
                                    <img src="<?php echo htmlspecialchars($grupo['imagen'] ?? 'img/logo gato sin fondo.png'); ?>"
                                        alt="<?php echo htmlspecialchars($grupo['nombre']); ?>"
                                        class="group-image">
                                    <div class="group-info">
                                        <div class="group-info-top">
                                            <h3 class="group-name"><?php echo htmlspecialchars($grupo['nombre']); ?></h3>
                                            <?php if ($grupo['nombre_genero']): ?>
                                                <span class="group-tag"><?php echo htmlspecialchars($grupo['nombre_genero']); ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <p class="group-description"><?php echo htmlspecialchars($grupo['descripcion']); ?></p>

                                        <div class="group-footer">
                                            <div class="group-meta">
                                                <span class="group-meta-item">
                                                    <img src="img/grupo.png" alt="miembros" class="icono-miembros">
                                                    <?php echo number_format($grupo['num_miembros']); ?> miembros
                                                </span>
                                                <?php if ($grupo['creador_username']): ?>
                                                    <span class="group-meta-item group-creador">
                                                        <span class="material-icons-outlined" style="font-size:0.95rem;">person</span>
                                                        <?php echo htmlspecialchars($grupo['creador_username']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($pen): ?>
                                                <div class="pen-aviso">
                                                    <span class="pen-aviso__icono">
                                                        <img src="img/candado.png" alt="icono candado">
                                                    </span>
                                                    <p>
                                                        <?php if ($permanente): ?>
                                                            Expulsado <strong>permanentemente</strong> por
                                                            <strong><?php echo htmlspecialchars($motivo_txt); ?></strong>.
                                                        <?php else: ?>
                                                            Expulsado temporalmente por
                                                            <strong><?php echo htmlspecialchars($motivo_txt); ?></strong>.
                                                            <span class="pen-aviso__fecha">
                                                                Puedes volver el <?php echo $fecha_fin_fmt; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            <?php elseif ($grupo['unido']): ?>
                                                <button class="join-btn joined" data-id="<?php echo $grupo['id_grupo']; ?>">
                                                    Unido al grupo
                                                </button>
                                            <?php else: ?>
                                                <button class="join-btn" data-id="<?php echo $grupo['id_grupo']; ?>">
                                                    Unirse al grupo
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Crear Grupo -->
        <div class="modal-overlay-mis-listas" id="modalCrearGrupo" style="display:none;">
            <div class="modal-content-mis-listas">
                <button class="modal-close-mis-listas" id="modalCloseGrupo">
                    <span class="material-icons-outlined">close</span>
                </button>
                <h2>Crear nuevo grupo</h2>

                <div id="modal-grupo-mensaje" class="alert d-none mb-3"></div>

                <div class="form-group">
                    <label for="grupoNombre">Nombre del grupo *</label>
                    <input type="text" id="grupoNombre" placeholder="Ej: Amantes del cine negro" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="grupoDescripcion">Descripción</label>
                    <textarea id="grupoDescripcion" rows="3" placeholder="¿De qué trata este grupo?" maxlength="500"></textarea>
                </div>

                <div class="form-group">
                    <label for="grupoGenero">Género cinematográfico</label>
                    <select id="grupoGenero" size="1" style="max-height:120px; overflow-y:auto;">
                        <option value="">— Sin género específico —</option>
                        <?php foreach ($generosFiltro as $g): ?>
                            <option value="<?php echo $g['id_genero']; ?>">
                                <?php echo htmlspecialchars($g['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="grupoTipo">Tipo de actividad *</label>
                    <select id="grupoTipo" required>
                        <option value="">— Selecciona un tipo —</option>
                        <option value="debates">Debates</option>
                        <option value="recomendaciones">Recomendaciones</option>
                        <option value="reseñas">Reseñas</option>
                        <option value="club-cine">Club de cine</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Imagen del grupo <small style="color:#6c757d;">(opcional, máx. 2 MB)</small></label>
                    <div class="portada-upload" id="portadaGrupoUpload">
                        <img id="portadaGrupoPreviewImg" src="" alt=""
                            style="display:none; width:100%; height:100%; object-fit:cover; border-radius:8px;">
                        <div id="portadaGrupoPlaceholder" style="display:flex; flex-direction:column; align-items:center; gap:0.5rem;">
                            <span class="material-icons-outlined" style="font-size:2rem; color:#aaa;">add_photo_alternate</span>
                            <span style="color:#aaa; font-size:0.9rem;">Haz clic para seleccionar una imagen</span>
                        </div>
                    </div>
                    <input type="file" id="grupoPortada" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancelar" id="cancelarGrupoBtn">Cancelar</button>
                    <button type="button" class="btn-guardar" id="guardarGrupoBtn">Crear grupo</button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/comunidad.js"></script>
</body>

</html>