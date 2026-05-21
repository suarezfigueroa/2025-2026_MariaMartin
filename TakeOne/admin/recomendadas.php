<?php
require_once '_guard.php';
require_once '../includes/conexion.php';

$msg_ok    = $_SESSION['msg_ok']    ?? null;
$msg_error = $_SESSION['msg_error'] ?? null;
unset($_SESSION['msg_ok'], $_SESSION['msg_error']);

// ── Acciones POST ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Añadir película recomendada
    if ($_POST['action'] === 'agregar') {
        $id_pelicula = (int)$_POST['id_pelicula'];

        // Comprobar que no esté ya en la lista
        $existe = $pdo->prepare("SELECT COUNT(*) FROM recomendadas_semana WHERE id_pelicula = ?");
        $existe->execute([$id_pelicula]);
        if ($existe->fetchColumn() > 0) {
            $_SESSION['msg_error'] = 'Esa película ya está en la lista de recomendadas.';
        } elseif ((int)$pdo->query("SELECT COUNT(*) FROM recomendadas_semana")->fetchColumn() >= 10) {
            $_SESSION['msg_error'] = 'Solo puedes tener un máximo de 10 películas recomendadas. Elimina alguna antes de añadir otra.';
        } else {
            $orden = (int)$pdo->query("SELECT COUNT(*) FROM recomendadas_semana")->fetchColumn();
            $pdo->prepare("INSERT INTO recomendadas_semana (id_pelicula, orden, fecha_asignacion) VALUES (?, ?, CURDATE())")
                ->execute([$id_pelicula, $orden]);
            $_SESSION['msg_ok'] = 'Película añadida a las recomendadas.';
        }
        header('Location: recomendadas.php');
        exit;
    }

    // Eliminar película recomendada
    if ($_POST['action'] === 'eliminar') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM recomendadas_semana WHERE id = ?")->execute([$id]);
        $_SESSION['msg_ok'] = 'Película eliminada de las recomendadas.';
        header('Location: recomendadas.php');
        exit;
    }

    // Vaciar toda la lista y reemplazar (botón "Guardar selección")
    if ($_POST['action'] === 'reemplazar') {
        $ids = $_POST['ids'] ?? [];
        $pdo->exec("DELETE FROM recomendadas_semana");
        foreach ($ids as $i => $id_pelicula) {
            $pdo->prepare("INSERT INTO recomendadas_semana (id_pelicula, orden, fecha_asignacion) VALUES (?, ?, CURDATE())")
                ->execute([(int)$id_pelicula, $i]);
        }
        $_SESSION['msg_ok'] = 'Lista de recomendadas actualizada correctamente.';
        header('Location: recomendadas.php');
        exit;
    }
}

// ── Datos ─────────────────────────────────────────────
$recomendadas = $pdo->query("
    SELECT r.id, r.orden, r.fecha_asignacion, p.id_pelicula, p.titulo, p.poster, p.anio, p.imdb
    FROM recomendadas_semana r
    JOIN peliculas p ON p.id_pelicula = r.id_pelicula
    ORDER BY r.orden ASC
")->fetchAll(PDO::FETCH_ASSOC);

$peliculas = $pdo->query("
    SELECT id_pelicula, titulo, anio FROM peliculas ORDER BY titulo ASC
")->fetchAll(PDO::FETCH_ASSOC);

$mensajes_sin_leer = $pdo->query("SELECT COUNT(*) FROM contacto WHERE leido = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recomendadas de la semana · TakeOne Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .rec-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
            padding: 8px 0 16px;
        }

        .rec-card {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            background: #f5f4f1;
            border: 1px solid rgba(0, 0, 0, 0.07);
            transition: box-shadow 0.2s;
        }

        .rec-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .rec-card img {
            width: 100%;
            aspect-ratio: 2/3;
            object-fit: cover;
            display: block;
        }

        .rec-card__info {
            padding: 8px 10px;
        }

        .rec-card__title {
            font-size: 12px;
            font-weight: 600;
            color: #1a1a18;
            line-height: 1.3;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .rec-card__meta {
            font-size: 11px;
            color: #888780;
        }

        .rec-card__remove {
            position: absolute;
            top: 6px;
            right: 6px;
            background: rgba(0, 0, 0, 0.65);
            color: white;
            border: none;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            line-height: 1;
        }

        .rec-card:hover .rec-card__remove {
            opacity: 1;
        }

        .rec-card__order {
            position: absolute;
            top: 6px;
            left: 6px;
            background: rgba(0, 0, 0, 0.65);
            color: white;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
            padding: 2px 5px;
        }

        .rec-empty {
            text-align: center;
            padding: 3rem 1rem;
            color: #888780;
            font-size: 14px;
        }

        .rec-empty svg {
            margin-bottom: 12px;
            opacity: 0.35;
        }

        .rec-count {
            font-size: 12px;
            color: #888780;
            margin-left: 8px;
            font-weight: 400;
        }
    </style>
</head>

<body>
    <div class="admin-layout">

        <?php include '_sidebar.php'; ?>

        <main class="admin-main">
            <?php
            $topbar_title = 'Recomendadas de la semana';
            $topbar_sub   = 'Elige qué películas aparecen en el slider de la página principal (MÁXIMO 10)';
            include '_topbar.php';
            ?>

            <div class="admin-content">

                <!-- Feedback -->
                <?php if ($msg_ok): ?>
                    <div class="admin-alert admin-alert--ok"><?= htmlspecialchars($msg_ok) ?></div>
                <?php endif; ?>
                <?php if ($msg_error): ?>
                    <div class="admin-alert admin-alert--error"><?= htmlspecialchars($msg_error) ?></div>
                <?php endif; ?>

                <!-- ── Lista actual ───────────────────────────── -->
                <div class="admin-panel">
                    <div class="admin-panel__header" style="display:flex; align-items:center; justify-content:space-between;">
                        <span class="admin-panel__title">
                            Selección actual
                            <span class="rec-count"><?= count($recomendadas) ?> película<?= count($recomendadas) !== 1 ? 's' : '' ?></span>
                        </span>
                        <button class="admin-btn admin-btn--primary" onclick="abrirModal()">
                            + Añadir película
                        </button>
                    </div>

                    <?php if (empty($recomendadas)): ?>
                        <div class="rec-empty">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                                <rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" stroke-width="1.5" />
                                <path d="M7 4v16M17 4v16M2 9h5M17 9h5M2 15h5M17 15h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                            <p>No hay películas recomendadas todavía.<br>Añade algunas para que aparezcan en el slider.</p>
                        </div>
                    <?php else: ?>
                        <div class="rec-grid">
                            <?php foreach ($recomendadas as $i => $r): ?>
                                <div class="rec-card">
                                    <span class="rec-card__order"><?= $i + 1 ?></span>
                                    <img src="<?= htmlspecialchars($r['poster'] ?? '') ?>"
                                        alt="<?= htmlspecialchars($r['titulo']) ?>"
                                        onerror="this.src='../img/poster-placeholder.jpg'">
                                    <form method="post">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="rec-card__remove" title="Quitar de recomendadas"
                                            onclick="return confirm('¿Quitar «<?= htmlspecialchars(addslashes($r['titulo'])) ?>» de las recomendadas?')">
                                            ✕
                                        </button>
                                    </form>
                                    <div class="rec-card__info">
                                        <div class="rec-card__title"><?= htmlspecialchars($r['titulo']) ?></div>
                                        <div class="rec-card__meta"><?= $r['anio'] ?> · <span style="color:#f5b731;">★</span> <?= $r['imdb'] ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Fecha de la última actualización -->
                        <?php $ultima = $recomendadas[0]['fecha_asignacion'] ?? null; ?>
                        <?php if ($ultima): ?>
                            <p style="font-size:12px; color:#aaa; padding: 0 0 12px; margin:0;">
                                Última actualización: <?= date('d/m/Y', strtotime($ultima)) ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <!-- ── Modal añadir película ──────────────────────────── -->
    <div id="modal-rec" class="cartelera-modal-overlay" style="display:none;">
        <div class="cartelera-modal">
            <div class="cartelera-modal__header">
                <span class="cartelera-modal__title">Añadir película recomendada</span>
                <button class="cartelera-modal__close" onclick="cerrarModal()">✕</button>
            </div>
            <form method="post" class="cartelera-modal__body" id="modal-form">
                <input type="hidden" name="action" value="agregar">
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

                <div class="form-actions" style="padding:0;border:none;margin-top:4px;">
                    <button type="submit" class="admin-btn admin-btn--primary">Añadir a recomendadas</button>
                    <button type="button" class="admin-btn admin-btn--ghost" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const todasLasPeliculas = <?php echo json_encode(array_values($peliculas)); ?>;

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

            const filtradas = todasLasPeliculas.filter(p => p.titulo.toLowerCase().includes(q));
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

        inputBuscador.addEventListener('blur', () => setTimeout(() => lista.style.display = 'none', 150));

        function abrirModal() {
            inputBuscador.value = '';
            inputId.value = '';
            lista.style.display = 'none';
            labelSeleccion.style.display = 'none';
            document.getElementById('modal-rec').style.display = 'flex';
            setTimeout(() => inputBuscador.focus(), 50);
        }

        function cerrarModal() {
            document.getElementById('modal-rec').style.display = 'none';
        }

        document.getElementById('modal-rec').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });

        document.getElementById('modal-form').addEventListener('submit', function(e) {
            if (!inputId.value) {
                e.preventDefault();
                inputBuscador.style.borderColor = '#e5533d';
                inputBuscador.focus();
                setTimeout(() => inputBuscador.style.borderColor = '', 2000);
            }
        });
    </script>
</body>

</html>