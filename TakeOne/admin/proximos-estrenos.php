<?php
require_once '_guard.php';
require_once '../includes/conexion.php';

$msg_ok    = $_SESSION['msg_ok']    ?? null;
$msg_error = $_SESSION['msg_error'] ?? null;
unset($_SESSION['msg_ok'], $_SESSION['msg_error']);

// ── Acciones POST ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Eliminar estreno
    if ($_POST['action'] === 'eliminar') {
        $id = (int)$_POST['id_estreno'];
        $pdo->prepare("DELETE FROM proximos_estrenos WHERE id_estreno = ?")->execute([$id]);
        $_SESSION['msg_ok'] = 'Estreno eliminado correctamente.';
        header('Location: proximos-estrenos.php');
        exit;
    }

    // Añadir estreno
    if ($_POST['action'] === 'agregar') {
        $id_pelicula   = !empty($_POST['id_pelicula']) ? (int)$_POST['id_pelicula'] : null;
        $fecha_estreno = $_POST['fecha_estreno'];

        // Obtener título y poster de la película vinculada
        $titulo = '';
        $poster = null;
        if ($id_pelicula) {
            $row = $pdo->prepare("SELECT titulo, poster FROM peliculas WHERE id_pelicula = ?");
            $row->execute([$id_pelicula]);
            $row = $row->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $titulo = $row['titulo'];
                $poster = $row['poster'] ?: null;
            }
        }

        if ($titulo === '' || $fecha_estreno === '') {
            $_SESSION['msg_error'] = 'Debes seleccionar una película y una fecha.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO proximos_estrenos (id_pelicula, titulo, poster, fecha_estreno)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$id_pelicula, $titulo, $poster, $fecha_estreno]);
            $_SESSION['msg_ok'] = 'Estreno añadido correctamente.';
        }
        header('Location: proximos-estrenos.php');
        exit;
    }

    // Editar estreno
    if ($_POST['action'] === 'editar') {
        $id            = (int)$_POST['id_estreno'];
        $id_pelicula   = !empty($_POST['id_pelicula']) ? (int)$_POST['id_pelicula'] : null;
        $fecha_estreno = $_POST['fecha_estreno'];

        // Obtener título y poster de la película vinculada
        $titulo = '';
        $poster = null;
        if ($id_pelicula) {
            $row = $pdo->prepare("SELECT titulo, poster FROM peliculas WHERE id_pelicula = ?");
            $row->execute([$id_pelicula]);
            $row = $row->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $titulo = $row['titulo'];
                $poster = $row['poster'] ?: null;
            }
        }

        if ($titulo === '' || $fecha_estreno === '') {
            $_SESSION['msg_error'] = 'Debes seleccionar una película y una fecha.';
        } else {
            $stmt = $pdo->prepare("
                UPDATE proximos_estrenos
                SET id_pelicula = ?, titulo = ?, poster = ?, fecha_estreno = ?
                WHERE id_estreno = ?
            ");
            $stmt->execute([$id_pelicula, $titulo, $poster, $fecha_estreno, $id]);
            $_SESSION['msg_ok'] = 'Estreno actualizado correctamente.';
        }
        header('Location: proximos-estrenos.php');
        exit;
    }
}

// ── Datos ────────────────────────────────────────────

// Próximos estrenos ordenados por fecha
$estrenos = $pdo->query("
    SELECT e.id_estreno, e.id_pelicula, e.titulo, e.poster, e.fecha_estreno,
           p.titulo AS titulo_pelicula
    FROM proximos_estrenos e
    LEFT JOIN peliculas p ON p.id_pelicula = e.id_pelicula
    ORDER BY e.fecha_estreno ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Películas disponibles para vincular (para el select del modal)
$peliculas = $pdo->query("
    SELECT id_pelicula, titulo, anio FROM peliculas ORDER BY titulo ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Separar en próximos y pasados (por si hay alguno ya estrenado)
$hoy      = date('Y-m-d');
$proximos = array_filter($estrenos, fn($e) => $e['fecha_estreno'] >= $hoy);
$pasados  = array_filter($estrenos, fn($e) => $e['fecha_estreno'] <  $hoy);

// Mensajes sin leer (sidebar)
$mensajes_sin_leer = $pdo->query("SELECT COUNT(*) FROM contacto WHERE leido = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Próximos estrenos · TakeOne Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>
    <div class="admin-layout">

        <?php include '_sidebar.php'; ?>

        <main class="admin-main">

            <!-- Topbar -->
            <?php
            $topbar_title = 'Próximos estrenos';
            $topbar_sub   = count($proximos) . ' estreno' . (count($proximos) !== 1 ? 's' : '') . ' pendiente' . (count($proximos) !== 1 ? 's' : '');
            include '_topbar.php';
            ?>

            <div class="admin-content">
                <div style="display:flex; justify-content:flex-end; margin-bottom:8px;">
                    <button class="admin-btn admin-btn--primary" onclick="abrirModal()">
                        + Nuevo estreno
                    </button>
                </div>
                <!-- Feedback -->
                <?php if ($msg_ok): ?>
                    <div class="admin-alert admin-alert--ok"><?= htmlspecialchars($msg_ok) ?></div>
                <?php endif; ?>
                <?php if ($msg_error): ?>
                    <div class="admin-alert admin-alert--error"><?= htmlspecialchars($msg_error) ?></div>
                <?php endif; ?>

                <!-- ── Próximos ───────────────────────────────── -->
                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <span class="admin-panel__title">Pendientes de estreno</span>
                    </div>

                    <?php if (empty($proximos)): ?>
                        <div class="admin-empty">No hay estrenos registrados próximamente.</div>
                    <?php else: ?>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Película</th>
                                        <th>Vinculada en BD</th>
                                        <th>Fecha de estreno</th>
                                        <th>Días restantes</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proximos as $e):
                                        $dias = (int)ceil((strtotime($e['fecha_estreno']) - strtotime($hoy)) / 86400);
                                    ?>
                                        <tr>
                                            <!-- Poster + título -->
                                            <td>
                                                <div class="peli-poster-col">
                                                    <div class="peli-poster-sm">
                                                        <?php if ($e['poster']): ?>
                                                            <img src="<?= htmlspecialchars($e['poster']) ?>" alt="">
                                                        <?php endif; ?>
                                                    </div>
                                                    <span><?= htmlspecialchars($e['titulo']) ?></span>
                                                </div>
                                            </td>

                                            <!-- Película vinculada -->
                                            <td>
                                                <?php if ($e['titulo_pelicula']): ?>
                                                    <span class="admin-badge admin-badge--purple"><?= htmlspecialchars($e['titulo_pelicula']) ?></span>
                                                <?php else: ?>
                                                    <span class="admin-badge admin-badge--gray">Sin vincular</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Fecha -->
                                            <td class="admin-table__muted">
                                                <?= date('d/m/Y', strtotime($e['fecha_estreno'])) ?>
                                            </td>

                                            <!-- Días restantes -->
                                            <td>
                                                <?php if ($dias === 0): ?>
                                                    <span class="admin-badge admin-badge--green">¡Hoy!</span>
                                                <?php elseif ($dias <= 30): ?>
                                                    <span class="admin-badge admin-badge--amber"><?= $dias ?> día<?= $dias !== 1 ? 's' : '' ?></span>
                                                <?php else: ?>
                                                    <span class="admin-badge admin-badge--gray"><?= $dias ?> días</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Acciones -->
                                            <td>
                                                <div class="admin-row__actions">
                                                    <button class="admin-btn-xs admin-btn-xs-editar"
                                                        onclick="abrirEditar(
                                                <?= $e['id_estreno'] ?>,
                                                <?= $e['id_pelicula'] ?? 'null' ?>,
                                                '<?= htmlspecialchars(addslashes($e['titulo'])) ?>',
                                                '<?= htmlspecialchars(addslashes($e['poster'] ?? '')) ?>',
                                                '<?= $e['fecha_estreno'] ?>'
                                            )">Editar</button>
                                                    <form method="post"
                                                        onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($e['titulo'])) ?>»?')">
                                                        <input type="hidden" name="action" value="eliminar">
                                                        <input type="hidden" name="id_estreno" value="<?= $e['id_estreno'] ?>">
                                                        <button type="submit" class="admin-btn-xs admin-btn-xs--danger">Eliminar</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ── Ya estrenados ──────────────────────────── -->
                <?php if (!empty($pasados)): ?>
                    <div class="admin-panel">
                        <div class="admin-panel__header">
                            <span class="admin-panel__title ya-estrenados">Ya estrenados</span>
                            <span class="admin-panel__link" style="color:#888780;font-weight:400;font-size:11px;">
                                <?= count($pasados) ?> registro<?= count($pasados) !== 1 ? 's' : '' ?>
                            </span>
                        </div>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Película</th>
                                        <th>Vinculada en BD</th>
                                        <th>Fecha de estreno</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_reverse(array_values($pasados)) as $e): ?>
                                        <tr style="opacity:.65;">
                                            <td>
                                                <div class="peli-poster-col">
                                                    <div class="peli-poster-sm">
                                                        <?php if ($e['poster']): ?>
                                                            <img src="<?= htmlspecialchars($e['poster']) ?>" alt="">
                                                        <?php endif; ?>
                                                    </div>
                                                    <span><?= htmlspecialchars($e['titulo']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($e['titulo_pelicula']): ?>
                                                    <span class="admin-badge admin-badge--cian"><?= htmlspecialchars($e['titulo_pelicula']) ?></span>
                                                <?php else: ?>
                                                    <span class="admin-badge admin-badge--gray">Sin vincular</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="admin-table__muted">
                                                <?= date('d/m/Y', strtotime($e['fecha_estreno'])) ?>
                                            </td>
                                            <td>
                                                <form method="post"
                                                    onsubmit="return confirm('¿Eliminar «<?= htmlspecialchars(addslashes($e['titulo'])) ?>»?')">
                                                    <input type="hidden" name="action" value="eliminar">
                                                    <input type="hidden" name="id_estreno" value="<?= $e['id_estreno'] ?>">
                                                    <button type="submit" class="admin-btn-xs admin-btn-xs--danger">Eliminar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div><!-- /content -->
        </main>
    </div>

    <!-- ── Modal añadir / editar ─────────────────────────── -->
    <div id="modal-estreno" class="cartelera-modal-overlay" style="display:none;">
        <div class="cartelera-modal">
            <div class="cartelera-modal__header">
                <span class="cartelera-modal__title" id="modal-titulo-label">Nuevo estreno</span>
                <button class="cartelera-modal__close" onclick="cerrarModal()">✕</button>
            </div>

            <form method="post" class="cartelera-modal__body" id="modal-form">
                <input type="hidden" name="action" value="agregar" id="modal-action">
                <input type="hidden" name="id_estreno" id="modal-id-estreno">
                <input type="hidden" name="id_pelicula" id="modal-id-pelicula">

                <div class="form-group">
                    <label class="form-label">Película <span style="color:#a32d2d">*</span></label>
                    <div style="position:relative;">
                        <input type="text"
                            id="buscador-pelicula"
                            class="form-input"
                            placeholder="Escribe para buscar..."
                            autocomplete="off">
                        <ul id="buscador-resultados" style="
                        display:none; position:absolute; top:100%; left:0; right:0;
                        background:#fff; border:0.5px solid rgba(0,0,0,0.15);
                        border-radius:6px; margin-top:3px; max-height:200px;
                        overflow-y:auto; z-index:200; list-style:none; padding:4px 0;
                        box-shadow:0 4px 12px rgba(0,0,0,0.08);
                    "></ul>
                    </div>
                    <p id="buscador-seleccionada" style="display:none; font-size:12px; color:#534AB7; margin-top:4px; font-weight:500;"></p>
                </div>

                <div class="form-group">
                    <label class="form-label">Fecha de estreno <span style="color:#a32d2d">*</span></label>
                    <input type="date" name="fecha_estreno" id="modal-fecha" class="form-input" required>
                </div>

                <div class="form-actions" style="padding:0;border:none;margin-top:4px;">
                    <button type="submit" class="admin-btn admin-btn--primary" id="modal-submit-btn">Añadir estreno</button>
                    <button type="button" class="admin-btn admin-btn--ghost" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Datos de todas las películas disponibles
        const todasLasPeliculas = <?php echo json_encode(array_values($peliculas)); ?>;

        // ── Buscador ─────────────────────────────────────────
        const inputBuscador = document.getElementById('buscador-pelicula');
        const inputId = document.getElementById('modal-id-pelicula');
        const lista = document.getElementById('buscador-resultados');
        const labelSeleccion = document.getElementById('buscador-seleccionada');

        inputBuscador.addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            lista.innerHTML = '';
            inputId.value = '';
            labelSeleccion.style.display = 'none';

            if (q.length < 1) {
                lista.style.display = 'none';
                return;
            }

            const filtradas = todasLasPeliculas.filter(p =>
                p.titulo.toLowerCase().includes(q)
            );

            if (filtradas.length === 0) {
                lista.style.display = 'none';
                return;
            }

            filtradas.forEach(p => {
                const li = document.createElement('li');
                li.textContent = p.titulo + (p.anio ? ' (' + p.anio + ')' : '');
                li.style.cssText = 'padding:8px 12px; font-size:13px; cursor:pointer; color:#1a1a18;';
                li.addEventListener('mouseenter', () => li.style.background = '#f5f4f1');
                li.addEventListener('mouseleave', () => li.style.background = '');
                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    inputId.value = p.id_pelicula;
                    inputBuscador.value = p.titulo + (p.anio ? ' (' + p.anio + ')' : '');
                    labelSeleccion.textContent = '✓ Seleccionada';
                    labelSeleccion.style.display = 'block';
                    lista.style.display = 'none';
                });
                lista.appendChild(li);
            });

            lista.style.display = 'block';
        });

        inputBuscador.addEventListener('blur', () => {
            setTimeout(() => lista.style.display = 'none', 150);
        });

        // ── Modal ─────────────────────────────────────────────
        function resetBuscador() {
            inputBuscador.value = '';
            inputId.value = '';
            lista.style.display = 'none';
            labelSeleccion.style.display = 'none';
            inputBuscador.style.borderColor = '';
        }

        function abrirModal() {
            document.getElementById('modal-titulo-label').textContent = 'Nuevo estreno';
            document.getElementById('modal-submit-btn').textContent = 'Añadir estreno';
            document.getElementById('modal-action').value = 'agregar';
            document.getElementById('modal-id-estreno').value = '';
            document.getElementById('modal-fecha').value = '';
            resetBuscador();
            document.getElementById('modal-estreno').style.display = 'flex';
            setTimeout(() => inputBuscador.focus(), 50);
        }

        function abrirEditar(id, idPelicula, titulo, poster, fecha) {
            document.getElementById('modal-titulo-label').textContent = 'Editar estreno';
            document.getElementById('modal-submit-btn').textContent = 'Guardar cambios';
            document.getElementById('modal-action').value = 'editar';
            document.getElementById('modal-id-estreno').value = id;
            document.getElementById('modal-fecha').value = fecha;

            resetBuscador();
            if (idPelicula) {
                const found = todasLasPeliculas.find(p => p.id_pelicula == idPelicula);
                if (found) {
                    inputId.value = found.id_pelicula;
                    inputBuscador.value = found.titulo + (found.anio ? ' (' + found.anio + ')' : '');
                    labelSeleccion.textContent = '✓ Seleccionada';
                    labelSeleccion.style.display = 'block';
                } else {
                    inputBuscador.value = titulo;
                    inputId.value = idPelicula;
                    labelSeleccion.textContent = '✓ Seleccionada';
                    labelSeleccion.style.display = 'block';
                }
            }

            document.getElementById('modal-estreno').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('modal-estreno').style.display = 'none';
        }

        // Validar que se haya seleccionado película antes de enviar
        document.getElementById('modal-form').addEventListener('submit', function(e) {
            if (!inputId.value) {
                e.preventDefault();
                inputBuscador.style.borderColor = '#e5533d';
                inputBuscador.focus();
                setTimeout(() => inputBuscador.style.borderColor = '', 2000);
            }
        });

        // Cerrar modal al clicar el overlay
        document.getElementById('modal-estreno').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });
    </script>

</body>

</html>