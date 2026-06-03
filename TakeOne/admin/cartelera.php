<?php
require_once '_guard.php';
require_once '../includes/conexion.php';

// ── Mensajes de feedback ─────────────────────────────
$msg_ok    = $_SESSION['msg_ok']    ?? null;
$msg_error = $_SESSION['msg_error'] ?? null;
unset($_SESSION['msg_ok'], $_SESSION['msg_error']);

// ── Acción: quitar de cartelera ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'quitar') {
        $id = (int)$_POST['id_pelicula'];
        $stmt = $pdo->prepare("DELETE FROM peliculas_en_cartelera WHERE id_pelicula = ?");
        $stmt->execute([$id]);
        $_SESSION['msg_ok'] = 'Película retirada de cartelera.';
        header('Location: cartelera.php');
        exit;
    }

    if ($_POST['action'] === 'agregar') {
        $id_pelicula  = (int)$_POST['id_pelicula'];
        $fecha_inicio = $_POST['fecha_inicio'];

        // Evitar duplicados
        $check = $pdo->prepare("SELECT 1 FROM peliculas_en_cartelera WHERE id_pelicula = ?");
        $check->execute([$id_pelicula]);

        if ($check->fetchColumn()) {
            $_SESSION['msg_error'] = 'Esa película ya está en cartelera.';
        } elseif ((int)$pdo->query("SELECT COUNT(*) FROM peliculas_en_cartelera")->fetchColumn() >= 10) {
            $_SESSION['msg_error'] = 'Solo puedes tener un máximo de 10 películas en cartelera. Retira alguna antes de añadir otra.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO peliculas_en_cartelera (id_pelicula, fecha_inicio) VALUES (?, ?)");
            $stmt->execute([$id_pelicula, $fecha_inicio]);
            $_SESSION['msg_ok'] = 'Película añadida a cartelera.';
        }
        header('Location: cartelera.php');
        exit;
    }

    if ($_POST['action'] === 'editar_fecha') {
        $id           = (int)$_POST['id_pelicula'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $stmt = $pdo->prepare("UPDATE peliculas_en_cartelera SET fecha_inicio = ? WHERE id_pelicula = ?");
        $stmt->execute([$fecha_inicio, $id]);
        $_SESSION['msg_ok'] = 'Fecha actualizada.';
        header('Location: cartelera.php');
        exit;
    }
}

// ── Películas actualmente en cartelera ───────────────
$en_cartelera = $pdo->query("
    SELECT p.id_pelicula, p.titulo, p.poster, p.director, p.anio,
           c.fecha_inicio
    FROM peliculas_en_cartelera c
    JOIN peliculas p ON p.id_pelicula = c.id_pelicula
    ORDER BY c.fecha_inicio DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Películas disponibles para agregar (no en cartelera) ──
$disponibles = $pdo->query("
    SELECT p.id_pelicula, p.titulo, p.anio
    FROM peliculas p
    WHERE p.id_pelicula NOT IN (SELECT id_pelicula FROM peliculas_en_cartelera)
    ORDER BY p.titulo ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Mensajes sin leer (sidebar) ──────────────────────
$mensajes_sin_leer = $pdo->query("SELECT COUNT(*) FROM contacto WHERE leido = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartelera · TakeOne Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>
    <div class="admin-layout">

        <?php include '_sidebar.php'; ?>

        <main class="admin-main">

            <!-- Topbar -->
            <?php
            $topbar_title = 'Cartelera';
            $topbar_sub   = count($en_cartelera) . ' película' . (count($en_cartelera) !== 1 ? 's' : '') . ' en cartelera ahora';
            include '_topbar.php';
            ?>

            <div class="admin-content">
                <div style="display:flex; justify-content:flex-end; margin-bottom:8px;">
                    <button class="admin-btn admin-btn--primary"
                        onclick="document.getElementById('modal-agregar').style.display='flex'">
                        + Añadir película
                    </button>
                </div>
                <!-- Feedback -->
                <?php if ($msg_ok): ?>
                    <div class="admin-alert admin-alert--ok"><?= htmlspecialchars($msg_ok) ?></div>
                <?php endif; ?>
                <?php if ($msg_error): ?>
                    <div class="admin-alert admin-alert--error"><?= htmlspecialchars($msg_error) ?></div>
                <?php endif; ?>

                <!-- Tabla cartelera -->
                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <span class="admin-panel__title">Películas en cartelera</span>
                        <span class="admin-panel__link"><?= date('d/m/Y') ?></span>
                    </div>

                    <?php if (empty($en_cartelera)): ?>
                        <div class="admin-empty">No hay ninguna película en cartelera.</div>
                    <?php else: ?>
                        <div class="admin-table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Película</th>
                                        <th>Director</th>
                                        <th>Año</th>
                                        <th>En cartelera desde</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($en_cartelera as $p): ?>
                                        <tr>
                                            <!-- Poster + título -->
                                            <td>
                                                <div class="peli-poster-col">
                                                    <div class="peli-poster-sm">
                                                        <?php if ($p['poster']): ?>
                                                            <img src="<?= htmlspecialchars($p['poster']) ?>" alt="">
                                                        <?php endif; ?>
                                                    </div>
                                                    <span><?= htmlspecialchars($p['titulo']) ?></span>
                                                </div>
                                            </td>
                                            <td class="admin-table__muted"><?= htmlspecialchars($p['director'] ?? '—') ?></td>
                                            <td class="admin-table__muted"><?= $p['anio'] ?></td>

                                            <!-- Fecha editable inline -->
                                            <td>
                                                <form method="post" style="display:flex;align-items:center;gap:6px;">
                                                    <input type="hidden" name="action" value="editar_fecha">
                                                    <input type="hidden" name="id_pelicula" value="<?= $p['id_pelicula'] ?>">
                                                    <input type="date"
                                                        name="fecha_inicio"
                                                        value="<?= $p['fecha_inicio'] ?>"
                                                        class="admin-filters__select"
                                                        style="height:28px;font-size:12px;">
                                                    <button type="submit" class="admin-btn-xs admin-btn-xs-guardar">Guardar</button>
                                                </form>
                                            </td>

                                            <!-- Quitar -->
                                            <td>
                                                <form method="post"
                                                    onsubmit="return confirm('¿Retirar «<?= htmlspecialchars(addslashes($p['titulo'])) ?>» de cartelera?')">
                                                    <input type="hidden" name="action" value="quitar">
                                                    <input type="hidden" name="id_pelicula" value="<?= $p['id_pelicula'] ?>">
                                                    <button type="submit" class="admin-btn-xs admin-btn-xs--danger">Quitar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div><!-- /panel -->

            </div><!-- /content -->
        </main>
    </div>

    <!-- ── Modal: añadir película a cartelera ────────────── -->
    <div id="modal-agregar" class="cartelera-modal-overlay" style="display:none;">
        <div class="cartelera-modal">
            <div class="cartelera-modal__header">
                <span class="cartelera-modal__title">Añadir película a cartelera</span>
                <button class="cartelera-modal__close" onclick="document.getElementById('modal-agregar').style.display='none'">✕</button>
            </div>

            <form method="post" class="cartelera-modal__body">
                <input type="hidden" name="action" value="agregar">

                <div class="form-group">
                    <label class="form-label">Película</label>
                    <input type="hidden" name="id_pelicula" id="input-id-pelicula">
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
                    <label class="form-label">Fecha de inicio en cartelera</label>
                    <input type="date" name="fecha_inicio" class="form-input"
                        value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-actions" style="padding:0;border:none;margin-top:4px;">
                    <button type="submit" class="admin-btn admin-btn--primary">Añadir a cartelera</button>
                    <button type="button" class="admin-btn admin-btn--ghost"
                        onclick="document.getElementById('modal-agregar').style.display='none'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Datos de películas disponibles
        const peliculasDisponibles = <?php echo json_encode(array_values($disponibles)); ?>;

        // Buscador
        const inputBuscador = document.getElementById('buscador-pelicula');
        const inputId = document.getElementById('input-id-pelicula');
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

            const filtradas = peliculasDisponibles.filter(p =>
                p.titulo.toLowerCase().includes(q)
            );

            if (filtradas.length === 0) {
                lista.style.display = 'none';
                return;
            }

            filtradas.forEach(p => {
                const li = document.createElement('li');
                li.textContent = p.titulo + ' (' + p.anio + ')';
                li.style.cssText = 'padding:8px 12px; font-size:13px; cursor:pointer; color:#1a1a18;';
                li.addEventListener('mouseenter', () => li.style.background = '#f5f4f1');
                li.addEventListener('mouseleave', () => li.style.background = '');
                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    inputId.value = p.id_pelicula;
                    inputBuscador.value = p.titulo + ' (' + p.anio + ')';
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

        // Validar que se haya seleccionado una película antes de enviar
        document.querySelector('.cartelera-modal__body').addEventListener('submit', function(e) {
            if (!inputId.value) {
                e.preventDefault();
                inputBuscador.style.borderColor = '#e5533d';
                inputBuscador.focus();
                setTimeout(() => inputBuscador.style.borderColor = '', 2000);
            }
        });

        // Limpiar buscador al abrir el modal
        document.querySelector('[onclick*="modal-agregar"]').addEventListener('click', function() {
            inputBuscador.value = '';
            inputId.value = '';
            lista.style.display = 'none';
            labelSeleccion.style.display = 'none';
            inputBuscador.style.borderColor = '';
        });

        // Cerrar modal al clicar fuera
        document.getElementById('modal-agregar').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    </script>

</body>

</html>