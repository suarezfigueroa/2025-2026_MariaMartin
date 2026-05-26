<?php
session_start();
$usuario = $_SESSION['usuario'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <title>Términos de servicio · TakeOne</title>
  <?php require_once 'includes/head.php'; ?>
  <style>
    /* ── Layout general ── */
    .terminos-wrap {
      max-width: 860px;
      margin: 0 auto;
      padding: 8rem 2rem 6rem;
    }

    /* ── Cabecera ── */
    .terminos-header {
      margin-bottom: 3.5rem;
    }

    .terminos-badge {
      display: inline-block;
      border: 2px solid #14b8a6;
      color: #14b8a6;
      font-size: 0.8rem;
      font-weight: 600;
      letter-spacing: 2px;
      text-transform: uppercase;
      padding: 0.35rem 1rem;
      border-radius: 2rem;
      margin-bottom: 1.25rem;
    }

    .terminos-header h1 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2.2rem, 5vw, 3.2rem);
      font-weight: 900;
      color: #fff;
      margin-bottom: 1rem;
      line-height: 1.2;
    }

    .terminos-meta {
      color: rgba(255, 255, 255, 0.45);
      font-size: 1rem;
    }

    .terminos-meta span {
      color: #14b8a6;
      font-weight: 500;
    }

    /* ── Índice ── */
    .terminos-toc {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.09);
      border-radius: 1.25rem;
      padding: 2rem 2.25rem;
      margin-bottom: 3rem;
    }

    .terminos-toc h3 {
      color: rgba(255, 255, 255, 0.8);
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 2px;
      font-weight: 600;
      margin-bottom: 1.25rem;
    }

    .terminos-toc ol {
      margin: 0;
      padding: 0 0 0 1.25rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.4rem;
    }

    .terminos-toc li a {
      color: rgba(255, 255, 255, 0.55);
      text-decoration: none;
      font-size: 1rem;
      transition: color 0.2s;
    }

    .terminos-toc li a:hover {
      color: #14b8a6;
    }

    /* ── Secciones del documento ── */
    .terminos-section {
      margin-bottom: 3rem;
      padding-bottom: 3rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.07);
    }

    .terminos-section:last-of-type {
      border-bottom: none;
    }

    .terminos-section-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .terminos-num {
      width: 40px;
      height: 40px;
      flex-shrink: 0;
      border-radius: 10px;
      background: rgba(255, 99, 71, 0.15);
      color: #14b8a6;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.95rem;
    }

    .terminos-section h2 {
      font-family: 'Playfair Display', serif;
      font-size: 1.45rem;
      font-weight: 700;
      color: #fff;
      margin: 0;
    }

    .terminos-section p,
    .terminos-section li {
      color: rgba(255, 255, 255, 0.65);
      line-height: 1.8;
      font-size: 1rem;
      margin-bottom: 0.85rem;
    }

    .terminos-section ul {
      padding-left: 1.4rem;
      margin-bottom: 1rem;
    }

    .terminos-section ul li {
      margin-bottom: 0.4rem;
    }

    /* Caja de aviso */
    .aviso-box {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.09);
      border-radius: 0.9rem;
      padding: 1.25rem 1.5rem;
      margin: 1.25rem 0;
      color: rgba(255, 255, 255, 0.75) !important;
      font-size: 1rem !important;
      line-height: 1.7 !important;
    }

    .aviso-box strong {
      color: #14b8a6;
    }

    /* Caja de info */
    .info-box {
      background: rgba(20, 184, 166, 0.08);
      border: 1px solid rgba(20, 184, 166, 0.25);
      border-radius: 0.9rem;
      padding: 1.25rem 1.5rem;
      margin: 1.25rem 0;
      color: rgba(255, 255, 255, 0.75) !important;
      font-size: 1rem !important;
      line-height: 1.7 !important;
    }

    .info-box strong {
      color: #14b8a6;
    }

    /* ── Footer del doc ── */
    .terminos-doc-footer {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 1.25rem;
      padding: 2rem;
      text-align: center;
      margin-top: 1rem;
    }

    .terminos-doc-footer p {
      color: rgba(255, 255, 255, 0.45);
      font-size: 1rem;
      margin-bottom: 1rem;
    }

    .terminos-doc-footer a {
      color: #ff6347;
      text-decoration: none;
      font-weight: 500;
    }

    .terminos-doc-footer a:hover {
      text-decoration: underline;
    }

    /* ── Responsive ── */
    @media (max-width: 600px) {
      .terminos-toc ol {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body style="background:#0f1419; color:#e5e7eb; font-family:'Inter',sans-serif; margin:0;">

  <?php include 'includes/header.php'; ?>

  <div class="terminos-wrap">

    <!-- Cabecera -->
    <header class="terminos-header">
      <div class="terminos-badge">📄 Legal</div>
      <h1>Términos de servicio</h1>
    </header>

    <!-- Índice -->
    <nav class="terminos-toc">
      <h3>Contenido</h3>
      <ol>
        <li><a href="#aceptacion">Aceptación de los términos</a></li>
        <li><a href="#cuenta">Registro y cuenta</a></li>
        <li><a href="#uso">Uso aceptable</a></li>
        <li><a href="#contenido">Contenido del usuario</a></li>
        <li><a href="#privacidad">Privacidad</a></li>
        <li><a href="#propiedad">Propiedad intelectual</a></li>
        <li><a href="#suspension">Suspensión y cancelación</a></li>
        <li><a href="#responsabilidad">Limitación de responsabilidad</a></li>
        <li><a href="#cambios">Cambios en los términos</a></li>
        <li><a href="#contacto">Contacto</a></li>
      </ol>
    </nav>

    <!-- 1. Aceptación -->
    <section class="terminos-section" id="aceptacion">
      <div class="terminos-section-header">
        <div class="terminos-num">01</div>
        <h2>Aceptación de los términos</h2>
      </div>
      <p>Al acceder o utilizar TakeOne, ya sea a través de nuestra web o cualquier aplicación asociada, aceptas quedar vinculado por estos Términos de Servicio. Si no estás de acuerdo con alguno de los puntos aquí recogidos, te pedimos que no utilices el servicio.</p>
      <div class="aviso-box"><strong>Importante:</strong> El uso continuado de la plataforma después de la publicación de cualquier cambio en estos términos implica tu aceptación de dichos cambios.</div>
    </section>

    <!-- 2. Cuenta -->
    <section class="terminos-section" id="cuenta">
      <div class="terminos-section-header">
        <div class="terminos-num">02</div>
        <h2>Registro y cuenta</h2>
      </div>
      <p>Para acceder a las funcionalidades completas de TakeOne deberás crear una cuenta. Al hacerlo, te comprometes a:</p>
      <ul>
        <li>Proporcionar información veraz, exacta y actualizada.</li>
        <li>Mantener la confidencialidad de tu contraseña y credenciales.</li>
        <li>Notificarnos de inmediato ante cualquier uso no autorizado de tu cuenta.</li>
        <li>Tener al menos 16 años de edad para registrarte.</li>
      </ul>
      <p>TakeOne no se hace responsable de las pérdidas derivadas del uso no autorizado de tu cuenta cuando no hayas tomado las medidas razonables de seguridad.</p>
    </section>

    <!-- 3. Uso aceptable -->
    <section class="terminos-section" id="uso">
      <div class="terminos-section-header">
        <div class="terminos-num">03</div>
        <h2>Uso aceptable</h2>
      </div>
      <p>TakeOne es una comunidad para amantes del cine. Te comprometes a no utilizar la plataforma para:</p>
      <ul>
        <li>Publicar contenido ofensivo, difamatorio, racista, sexista o discriminatorio.</li>
        <li>Acosar, amenazar o intimidar a otros usuarios.</li>
        <li>Distribuir spam, contenido publicitario no solicitado o malware.</li>
        <li>Intentar acceder sin autorización a sistemas o datos de otros usuarios.</li>
        <li>Suplantar la identidad de otras personas o entidades.</li>
        <li>Infringir derechos de propiedad intelectual de terceros.</li>
      </ul>
      <div class="info-box"><strong>Recuerda:</strong> el debate cinematográfico puede ser apasionado, pero siempre dentro del respeto. Las discrepancias de opinión son bienvenidas; el insulto, no.</div>
    </section>

    <!-- 4. Contenido -->
    <section class="terminos-section" id="contenido">
      <div class="terminos-section-header">
        <div class="terminos-num">04</div>
        <h2>Contenido del usuario</h2>
      </div>
      <p>Al publicar reseñas, listas, comentarios u otro contenido en TakeOne, declaras que:</p>
      <ul>
        <li>Eres el autor o tienes los derechos necesarios para compartirlo.</li>
        <li>Concedes a TakeOne una licencia no exclusiva, gratuita y mundial para mostrar dicho contenido dentro de la plataforma.</li>
        <li>El contenido no vulnera derechos de terceros ni la legislación aplicable.</li>
      </ul>
      <p>TakeOne se reserva el derecho de eliminar cualquier contenido que incumpla estos términos, sin previo aviso y a su sola discreción.</p>
    </section>

    <!-- 5. Privacidad -->
    <section class="terminos-section" id="privacidad">
      <div class="terminos-section-header">
        <div class="terminos-num">05</div>
        <h2>Privacidad</h2>
      </div>
      <p>El tratamiento de tus datos personales se rige por nuestra <a href="privacidad.php" style="color:#14b8a6;text-decoration:none;font-weight:500;">Política de Privacidad</a>, que forma parte integrante de estos Términos. Te recomendamos leerla detenidamente.</p>
      <p>TakeOne cumple con el Reglamento General de Protección de Datos (RGPD) y la normativa española de protección de datos vigente.</p>
    </section>

    <!-- 6. Propiedad intelectual -->
    <section class="terminos-section" id="propiedad">
      <div class="terminos-section-header">
        <div class="terminos-num">06</div>
        <h2>Propiedad intelectual</h2>
      </div>
      <p>Todos los elementos de TakeOne —logotipo, diseño, código, marca y contenidos propios— son propiedad exclusiva de TakeOne o de sus licenciantes. Queda prohibida su reproducción, distribución o modificación sin autorización expresa por escrito.</p>
      <p>Los títulos, carteles e información sobre películas pueden pertenecer a sus respectivos estudios y distribuidoras. TakeOne los utiliza únicamente con fines informativos y sin ánimo de lucro.</p>
    </section>

    <!-- 7. Suspensión -->
    <section class="terminos-section" id="suspension">
      <div class="terminos-section-header">
        <div class="terminos-num">07</div>
        <h2>Suspensión y cancelación</h2>
      </div>
      <p>TakeOne puede suspender o cancelar tu cuenta, de forma temporal o permanente, si:</p>
      <ul>
        <li>Incumples de forma reiterada estos Términos de Servicio.</li>
        <li>Tu comportamiento perjudica gravemente a otros usuarios o a la comunidad.</li>
        <li>Se detecta actividad fraudulenta o suplantación de identidad.</li>
      </ul>
      <p>También puedes cancelar tu cuenta en cualquier momento desde los ajustes de perfil. La cancelación no genera derecho a reembolso de ningún tipo.</p>
    </section>

    <!-- 8. Responsabilidad -->
    <section class="terminos-section" id="responsabilidad">
      <div class="terminos-section-header">
        <div class="terminos-num">08</div>
        <h2>Limitación de responsabilidad</h2>
      </div>
      <p>TakeOne se proporciona "tal como está". No garantizamos que el servicio sea ininterrumpido, libre de errores ni completamente seguro. En la medida permitida por la ley, TakeOne no será responsable de:</p>
      <ul>
        <li>Daños directos o indirectos derivados del uso o la imposibilidad de uso del servicio.</li>
        <li>Pérdida de datos, ingresos o beneficios.</li>
        <li>Contenido publicado por terceros usuarios en la plataforma.</li>
      </ul>
      <div class="aviso-box"><strong>Nota:</strong> Algunos países no permiten excluir ciertas garantías o limitar la responsabilidad por daños, por lo que las exclusiones anteriores pueden no aplicarse en tu caso.</div>
    </section>

    <!-- 9. Cambios -->
    <section class="terminos-section" id="cambios">
      <div class="terminos-section-header">
        <div class="terminos-num">09</div>
        <h2>Cambios en los términos</h2>
      </div>
      <p>TakeOne puede actualizar estos Términos de Servicio periódicamente. Cuando lo hagamos, actualizaremos la fecha de "última actualización" en la parte superior de esta página y, si los cambios son significativos, te lo notificaremos mediante un aviso visible en la plataforma o por correo electrónico.</p>
    </section>

    <!-- 10. Contacto -->
    <section class="terminos-section" id="contacto">
      <div class="terminos-section-header">
        <div class="terminos-num">10</div>
        <h2>Contacto</h2>
      </div>
      <p>Si tienes dudas sobre estos Términos de Servicio o sobre el funcionamiento de la plataforma, puedes contactar con nosotros a través de nuestra <a href="contacto.php" style="color:#14b8a6;text-decoration:none;font-weight:500;">página de contacto</a> o enviando un correo a <a href="mailto:legal@takeone.es" style="color:#14b8a6;text-decoration:none;font-weight:500;">legal@takeone.es</a>.</p>
    </section>

    <!-- Footer del documento -->
    <div class="terminos-doc-footer">
      <p>Al usar TakeOne confirmas que has leído y aceptas estos términos en su totalidad.</p>
      <a href="detras-de-camara.php">← Conoce más sobre TakeOne</a>
      &nbsp;&nbsp;·&nbsp;&nbsp;
      <a href="contacto.php">Contacto</a>
    </div>

  </div><!-- /terminos-wrap -->

  <?php include 'includes/footer.php'; ?>

</body>

</html>