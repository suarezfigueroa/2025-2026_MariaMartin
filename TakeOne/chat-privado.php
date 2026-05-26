<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) {
    header('Location: login.php');
    exit;
}

require_once 'includes/conexion.php';

$yo       = (int) $usuario['id'];
$idOtro   = (int) ($_GET['id'] ?? 0);

if (!$idOtro || $idOtro === $yo) {
    header('Location: amigos.php');
    exit;
}

// Verificar que el otro usuario existe y es activo
$stmtOtro = $pdo->prepare("
    SELECT id_usuario, username, avatar
    FROM usuarios
    WHERE id_usuario = ? AND activo = 1 AND rol <> 'admin'
");
$stmtOtro->execute([$idOtro]);
$otro = $stmtOtro->fetch(PDO::FETCH_ASSOC);

if (!$otro) {
    header('Location: amigos.php');
    exit;
}

// Verificar que son amigos
$stmtAmigos = $pdo->prepare("
    SELECT id_amistad FROM amistades
    WHERE ((id_emisor = :a AND id_receptor = :b)
        OR (id_emisor = :b2 AND id_receptor = :a2))
      AND estado = 'aceptada'
");
$stmtAmigos->execute([':a' => $yo, ':b' => $idOtro, ':b2' => $idOtro, ':a2' => $yo]);
if (!$stmtAmigos->fetch()) {
    header('Location: amigos.php');
    exit;
}

// Marcar como leídos los mensajes recibidos de este usuario
$pdo->prepare("
    UPDATE mensajes_privados
    SET leido = 1
    WHERE id_emisor = :otro AND id_receptor = :yo AND leido = 0
")->execute([':otro' => $idOtro, ':yo' => $yo]);

$inicialOtro = strtoupper(substr($otro['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Chat con <?= htmlspecialchars($otro['username']) ?> · TakeOne</title>
    <?php require_once 'includes/head.php'; ?>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container" style="max-width:var(--container-max);">

            <div class="chat-privado-wrapper">

                <!-- Cabecera del chat -->
                <div class="chat-privado-header">
                    <a href="javascript:history.back()" class="btn-volver-chat">
                        <span class="material-icons-outlined">arrow_back</span>
                    </a>

                    <?php if (!empty($otro['avatar'])): ?>
                        <img src="<?= htmlspecialchars($otro['avatar']) ?>"
                            alt="<?= htmlspecialchars($otro['username']) ?>"
                            class="chat-privado-avatar">
                    <?php else: ?>
                        <div class="chat-privado-avatar chat-privado-inicial">
                            <?= $inicialOtro ?>
                        </div>
                    <?php endif; ?>

                    <div class="chat-privado-header-info">
                        <a href="perfil-amigo.php?id=<?= $idOtro ?>" class="chat-privado-username">
                            <?= htmlspecialchars($otro['username']) ?>
                        </a>
                    </div>

                    <a href="perfil-amigo.php?id=<?= $idOtro ?>" class="btn-volver-chat ms-auto" title="Ver perfil">
                        <span class="material-icons-outlined">person</span>
                    </a>
                </div>

                <!-- Área de mensajes -->
                <div class="chat-privado-mensajes" id="chatMensajes">
                    <p class="chat-cargando">Cargando mensajes...</p>
                </div>

                <!-- Input de envío -->
                <div class="chat-privado-input-area">
                    <input type="text" id="chatInput"
                        placeholder="Escribe un mensaje…"
                        maxlength="1000" autocomplete="off">
                    <button id="chatEnviar" title="Enviar">
                        <span class="material-icons-outlined">send</span>
                    </button>
                </div>

            </div>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        const ID_OTRO = <?= $idOtro ?>;
        const ID_YO = <?= $yo ?>;
        const API = 'api/chat-privado-api.php';

        let ultimoId = 0;
        let intervalo = null;

        // ── Helpers ───────────────────────────────────────────────────────────────
        function avatarHtml(username, avatar, size = 32) {
            const inicial = username.charAt(0).toUpperCase();
            if (avatar) {
                return `<img src="${avatar}" alt="${username}" class="msg-avatar" style="width:${size}px;height:${size}px;">`;
            }
            return `<div class="msg-avatar msg-avatar-inicial" style="width:${size}px;height:${size}px;">${inicial}</div>`;
        }

        function formatearFecha(fechaStr) {
            const d = new Date(fechaStr.replace(' ', 'T'));
            const ahora = new Date();
            const hoy = ahora.toDateString();
            const dStr = d.toDateString();
            const hora = d.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
            if (hoy === dStr) return hora;
            return d.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit'
            }) + ' ' + hora;
        }

        function crearBurbuja(msg) {
            const soyYo = (parseInt(msg.id_emisor) === ID_YO);
            const div = document.createElement('div');
            div.classList.add('msg-row', soyYo ? 'msg-row-yo' : 'msg-row-otro');
            div.dataset.id = msg.id_mensaje;

            const avatarStr = soyYo ? '' : avatarHtml(msg.username, msg.avatar);
            div.innerHTML = `
        ${avatarStr}
        <div class="msg-burbuja ${soyYo ? 'msg-burbuja-yo' : 'msg-burbuja-otro'}">
            <p>${escapeHtml(msg.mensaje)}</p>
            <span class="msg-hora">${formatearFecha(msg.fecha)}</span>
        </div>
    `;
            return div;
        }

        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function scrollAbajo(forzar = false) {
            const c = document.getElementById('chatMensajes');
            const cerca = c.scrollHeight - c.scrollTop - c.clientHeight < 120;
            if (forzar || cerca) c.scrollTop = c.scrollHeight;
        }

        // ── Cargar mensajes ───────────────────────────────────────────────────────
        async function cargarMensajes() {
            const res = await fetch(`${API}?accion=mensajes&id_otro=${ID_OTRO}&desde=${ultimoId}`);
            const data = await res.json();
            if (!data.ok || !data.mensajes.length) return;

            const contenedor = document.getElementById('chatMensajes');
            const cargando = contenedor.querySelector('.chat-cargando');
            if (cargando) cargando.remove();

            if (ultimoId === 0 && !data.mensajes.length) {
                contenedor.innerHTML = '<p class="chat-vacio">Aún no hay mensajes. ¡Di hola! 👋</p>';
                return;
            }

            data.mensajes.forEach(msg => {
                if (!document.querySelector(`.msg-row[data-id="${msg.id_mensaje}"]`)) {
                    contenedor.appendChild(crearBurbuja(msg));
                    ultimoId = Math.max(ultimoId, parseInt(msg.id_mensaje));
                }
            });

            scrollAbajo(ultimoId === 0);
        }

        // ── Enviar mensaje ────────────────────────────────────────────────────────
        async function enviarMensaje() {
            const input = document.getElementById('chatInput');
            const texto = input.value.trim();
            if (!texto) return;

            input.value = '';
            input.disabled = true;

            const res = await fetch(API, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'enviar',
                    id_receptor: ID_OTRO,
                    mensaje: texto
                }),
            });
            const data = await res.json();

            input.disabled = false;
            input.focus();

            if (data.ok) {
                const contenedor = document.getElementById('chatMensajes');
                const cargando = contenedor.querySelector('.chat-cargando, .chat-vacio');
                if (cargando) cargando.remove();
                contenedor.appendChild(crearBurbuja(data.mensaje));
                ultimoId = Math.max(ultimoId, parseInt(data.mensaje.id_mensaje));
                scrollAbajo(true);
            } else {
                alert(data.msg);
            }
        }

        // ── Eventos ───────────────────────────────────────────────────────────────
        document.getElementById('chatEnviar').addEventListener('click', enviarMensaje);
        document.getElementById('chatInput').addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                enviarMensaje();
            }
        });

        // ── Init + polling ────────────────────────────────────────────────────────
        (async () => {
            await cargarMensajes();

            // Si no había mensajes mostramos el estado vacío
            const contenedor = document.getElementById('chatMensajes');
            if (contenedor.querySelector('.chat-cargando')) {
                contenedor.innerHTML = '<p class="chat-vacio">Aún no hay mensajes. ¡Di hola! 👋</p>';
            }

            scrollAbajo(true);
            intervalo = setInterval(cargarMensajes, 4000);
        })();
    </script>

</body>

</html>