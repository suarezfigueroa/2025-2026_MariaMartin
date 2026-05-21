<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar__logo">
        <img src="../img/logo gato sin fondo.png" alt="TakeOne" class="admin-sidebar__logo-img">
        <span class="admin-sidebar__brand">TakeOne</span>
        <span class="admin-sidebar__badge">admin</span>
    </div>

    <nav class="admin-sidebar__nav">
        <p class="admin-sidebar__nav-label">General</p>
        <a href="index.php" class="admin-sidebar__item <?= $current === 'index.php' ? 'admin-sidebar__item--active' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <rect x="1" y="1" width="6" height="6" rx="1.2" fill="currentColor" />
                <rect x="9" y="1" width="6" height="6" rx="1.2" fill="currentColor" opacity=".45" />
                <rect x="1" y="9" width="6" height="6" rx="1.2" fill="currentColor" opacity=".45" />
                <rect x="9" y="9" width="6" height="6" rx="1.2" fill="currentColor" opacity=".45" />
            </svg>
            Panel
        </a>

        <p class="admin-sidebar__nav-label">Contenido</p>
        <a href="cartelera.php" class="admin-sidebar__item <?= $current === 'cartelera.php' ? 'admin-sidebar__item--active' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <rect x="1" y="3" width="14" height="11" rx="1" stroke="currentColor" stroke-width="1.3" />
                <path d="M5 3V1M11 3V1M1 7h14" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
            </svg>
            Cartelera
        </a>

        <a href="proximos-estrenos.php" class="admin-sidebar__item <?= $current === 'proximos-estrenos.php' ? 'admin-sidebar__item--active' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3" />
                <path d="M8 4.5V8l2.5 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Próximos estrenos
        </a>

        <a href="peliculas.php" class="admin-sidebar__item <?= $current === 'peliculas.php' ? 'admin-sidebar__item--active' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                <rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" stroke-width="1.5" />
                <path d="M7 4v16M17 4v16M2 9h5M17 9h5M2 15h5M17 15h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
            Películas
        </a>

        <a href="recomendadas.php" class="admin-sidebar__item <?= $current === 'recomendadas.php' ? 'admin-sidebar__item--active' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
            </svg>
            Recomendadas
        </a>

        <a href="noticias.php" class="admin-sidebar__item <?= $current === 'noticias.php' ? 'admin-sidebar__item--active' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.5" />
                <path d="M7 8h10M7 12h6M7 16h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                <rect x="7" y="8" width="4" height="4" rx="0.5" stroke="currentColor" stroke-width="1.5" />
            </svg>
            Noticias
        </a>

        <p class="admin-sidebar__nav-label">Moderación</p>
        <a href="usuarios.php" class="admin-sidebar__item <?= $current === 'usuarios.php' ? 'admin-sidebar__item--active' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="5" r="3" stroke="currentColor" stroke-width="1.3" />
                <path d="M2 14c0-3 2.7-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
            </svg>
            Usuarios
        </a>
        <a href="comentarios.php" class="admin-sidebar__item <?= $current === 'comentarios.php' ? 'admin-sidebar__item--active' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <path d="M2 2h12v9H9l-3 3v-3H2V2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
            </svg>
            Comentarios
        </a>

        <a href="grupos.php" class="admin-sidebar__item <?= $current === 'grupos.php' ? 'admin-sidebar__item--active' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <circle cx="5" cy="6" r="2.5" stroke="currentColor" stroke-width="1.3" />
                <circle cx="11" cy="6" r="2.5" stroke="currentColor" stroke-width="1.3" />
                <path d="M1 14c0-2.2 1.8-4 4-4M8 14c0-2.2 1.8-4 4-4M6 14c.4-1.6 1-2.5 2-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
            </svg>
            Grupos
        </a>

        <a href="contacto.php" class="admin-sidebar__item <?= $current === 'contacto.php' ? 'admin-sidebar__item--active' : '' ?>">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <path d="M2 3h12v9H2z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
                <path d="M2 3l6 5.5L14 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Contacto
            <?php if (!empty($mensajes_sin_leer) && $mensajes_sin_leer > 0): ?>
                <span class="admin-sidebar__dot"><?= $mensajes_sin_leer ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <div class="admin-sidebar__footer">
        <a href="../index.php" class="admin-sidebar__item admin-sidebar__item--volver">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M8 1L1 7h2v7h4v-4h2v4h4V7h2L8 1z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
            </svg>
            Volver al sitio
        </a>
        <a href="../logout.php" class="admin-sidebar__item admin-sidebar__item--danger">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M6 2H2v12h4M11 11l3-3-3-3M14 8H6" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Cerrar sesión
        </a>
    </div>
</aside>