<!doctype html>
<html lang="es" class="light">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Contacto - TakeOne</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
</head>

<body>
  <?php
  session_start();
  require_once 'includes/conexion.php';

  $usuarioLogueado = isset($_SESSION['usuario']);
  $nombre          = $usuarioLogueado ? htmlspecialchars($_SESSION['usuario']['nombre'] ?? '') : '';
  $email           = $usuarioLogueado ? htmlspecialchars($_SESSION['usuario']['email']  ?? '') : '';
  $idUsuario       = $usuarioLogueado ? (int)($_SESSION['usuario']['id_usuario'] ?? $_SESSION['usuario']['id'] ?? null) : null;

  $mensajeExito = false;
  $mensajeError = '';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombrePost  = trim($_POST['nombre']  ?? '');
    $emailPost   = trim($_POST['email']   ?? '');
    $mensajePost = trim($_POST['mensaje'] ?? '');

    if ($nombrePost === '' || $emailPost === '' || $mensajePost === '') {
      $mensajeError = 'Por favor, rellena todos los campos.';
    } elseif (!filter_var($emailPost, FILTER_VALIDATE_EMAIL)) {
      $mensajeError = 'El correo electrónico no es válido.';
    } elseif (mb_strlen($mensajePost) > 1000) {
      $mensajeError = 'El mensaje no puede superar los 1000 caracteres.';
    } else {
      $stmt = $pdo->prepare("
            INSERT INTO contacto (nombre, email, motivo, id_usuario)
            VALUES (:nombre, :email, :motivo, :id_usuario)
        ");
      $stmt->execute([
        ':nombre'     => $nombrePost,
        ':email'      => $emailPost,
        ':motivo'     => $mensajePost,
        ':id_usuario' => $idUsuario,
      ]);
      $mensajeExito = true;
    }
  }
  ?>

  <?php include 'includes/header.php'; ?>

  <main class="py-4">
    <div class="container" style="max-width:var(--container-max);">

      <div class="contacto-hero">
        <div class="contacto-hero-content">
          <div class="contacto-hero-text">
            <h1>Contacta con nosotros</h1>
            <p>¿Tienes alguna pregunta, sugerencia o has encontrado algún problema? Estamos aquí para ayudarte. Tu opinión es muy importante para nosotros.</p>
          </div>
          <div class="contacto-icon-wrapper">
            <span class="material-icons-outlined contacto-main-icon">mail</span>
          </div>
        </div>
      </div>

      <div class="contacto-content">
        <div class="contacto-grid">

          <!-- Formulario de Contacto -->
          <div class="contacto-form-card">
            <h2 class="contacto-section-title">Envíanos un mensaje</h2>

            <?php if ($mensajeExito): ?>
              <div class="contacto-exito">
                <span class="material-icons-outlined">check_circle</span>
                <div>
                  <strong>¡Mensaje enviado correctamente!</strong>
                  <p>Te responderemos en menos de 24 horas.</p>
                </div>
              </div>
            <?php else: ?>

              <?php if ($mensajeError): ?>
                <div class="contacto-error">
                  <span class="material-icons-outlined">error</span>
                  <?= htmlspecialchars($mensajeError) ?>
                </div>
              <?php endif; ?>

              <form id="contactForm" class="contacto-form" method="POST" action="contacto.php">

                <div class="form-group-contacto">
                  <label for="nombre">
                    <span class="material-icons-outlined">person</span>
                    Nombre
                  </label>
                  <input type="text" id="nombre" name="nombre"
                    value="<?= $nombre ?>"
                    placeholder="Tu nombre completo" required />
                </div>

                <div class="form-group-contacto">
                  <label for="email">
                    <span class="material-icons-outlined">email</span>
                    Correo electrónico
                  </label>
                  <input type="email" id="email" name="email"
                    value="<?= $email ?>"
                    <?php if ($usuarioLogueado): ?>
                    readonly class="input-readonly"
                    <?php else: ?>
                    placeholder="tucorreo@ejemplo.com" required
                    <?php endif; ?> />
                </div>

                <div class="form-group-contacto">
                  <label for="mensaje">
                    <span class="material-icons-outlined">message</span>
                    Mensaje
                  </label>
                  <textarea id="mensaje" name="mensaje" rows="6"
                    placeholder="Cuéntanos con detalle tu consulta, sugerencia o problema..." required></textarea>
                  <small class="char-counter">0 / 1000 caracteres</small>
                </div>

                <button type="submit" class="btn-enviar-contacto">
                  <span class="material-icons-outlined">send</span>
                  Enviar mensaje
                </button>

              </form>

            <?php endif; ?>
          </div>

          <div class="contacto-info-sidebar">

            <div class="info-card-contacto">
              <h3>
                <span class="material-icons-outlined">contact_support</span>
                Otras formas de contacto
              </h3>

              <div class="metodo-contacto">
                <div class="metodo-icon">
                  <span class="material-icons-outlined">email</span>
                </div>
                <div class="metodo-info">
                  <h4>Email</h4>
                  <a href="mailto:contacto@takeone.com">contacto@takeone.com</a>
                </div>
              </div>

              <div class="metodo-contacto">
                <div class="metodo-icon">
                  <span class="material-icons-outlined">chat</span>
                </div>
                <div class="metodo-info">
                  <h4>Redes sociales</h4>
                  <div class="social-links-contacto">
                    <a href="#" aria-label="Instagram">
                      <img src="img/instagram.png" alt="Instagram">
                    </a>
                    <a href="#" aria-label="Youtube">
                      <img src="img/youtube.png" alt="Youtube">
                    </a>
                    <a href="#" aria-label="TikTok">
                      <img src="img/tik-tok.png" alt="TikTok">
                    </a>
                  </div>
                </div>
              </div>

              <div class="metodo-contacto">
                <div class="metodo-icon">
                  <span class="material-icons-outlined">schedule</span>
                </div>
                <div class="metodo-info">
                  <h4>Tiempo de respuesta</h4>
                  <p>Normalmente respondemos en menos de 24 horas</p>
                </div>
              </div>
            </div>

            <div class="info-card-contacto faq-card">
              <h3>
                <span class="material-icons-outlined">help</span>
                Preguntas frecuentes
              </h3>

              <div class="faq-item">
                <h4>¿Cómo puedo cambiar mi contraseña?</h4>
                <p>Ve a tu perfil > Configuración > Seguridad</p>
              </div>

              <div class="faq-item">
                <h4>¿Cómo creo una lista personalizada?</h4>
                <p>En la sección "Listas", haz clic en "+ Crear lista"</p>
              </div>

              <div class="faq-item">
                <h4>¿Puedo sugerir películas para añadir?</h4>
                <p>¡Sí! Rellena el formulario y en el campo "Mensaje" escribe tu sugerencia</p>
              </div>
            </div>

          </div>
        </div>
      </div>

    </div>
  </main>

  <?php include 'includes/footer.php'; ?>

  <?php if (!$mensajeExito): ?>
    <script src="js/contacto.js"></script>
  <?php endif; ?>

</body>

</html>