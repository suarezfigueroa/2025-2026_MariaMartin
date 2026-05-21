<?php

session_start();

if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>editar perfil - takeone</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="py-4">
        <div class="container" style="max-width:var(--container-max);">
            <div class="editar-perfil-header">
                <h1 class="editar-perfil-title">Editar perfil</h1>
                <p class="editar-perfil-subtitle">actualiza tu información personal</p>
            </div>

            <div class="editar-perfil-container">
                <form id="editarPerfilForm" class="editar-perfil-form" enctype="multipart/form-data">

                    <div class="form-section">
                        <label class="form-section-title">Imagen de perfil</label>
                        <div class="avatar-edit-section">
                            <div class="avatar-preview">
                                <img src="img/default-avatar.png" alt="avatar actual" id="avatarPreview">
                            </div>
                            <div class="avatar-upload">
                                <input type="file" id="avatarInput" name="avatar" accept="image/*" class="file-input">
                                <label for="avatarInput" class="btn-upload-avatar">
                                    <span class="material-icons-outlined">photo_camera</span>
                                    cambiar imagen
                                </label>
                                <p class="upload-hint">jpg, png o webp. Máximo 5MB.</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <label for="username" class="form-label">
                            <span class="material-icons-outlined">person</span>
                            Nombre de usuario
                        </label>
                        <input type="text" id="username" name="username" class="form-input" value="" placeholder="introduce tu nombre de usuario" required>
                        <p class="input-hint">este es tu identificador único en takeone</p>
                    </div>

                    <div class="form-section">
                        <label for="bio" class="form-label">
                            <span class="material-icons-outlined">description</span>
                            Biografía
                        </label>
                        <textarea id="bio" name="bio" class="form-textarea" placeholder="cuéntanos sobre ti y tus gustos cinematográficos..." rows="4" maxlength="200"></textarea>
                        <div class="textarea-footer">
                            <p class="input-hint">máximo 200 caracteres</p>
                            <span class="char-count" id="bioCharCount">0/200</span>
                        </div>
                    </div>

                    <div class="form-section">
                        <label for="location" class="form-label">
                            <span class="material-icons-outlined">location_on</span>
                            Localidad
                        </label>
                        <input type="text" id="location" name="location" class="form-input" placeholder="madrid, españa">
                        <p class="input-hint">tu ubicación (opcional)</p>
                    </div>

                    <div class="form-section">
                        <label for="email" class="form-label">
                            <span class="material-icons-outlined">email</span>
                            Correo electrónico
                        </label>
                        <input type="email" id="email" name="email" class="form-input" value="" placeholder="tucorreo@email.com" required>
                        <p class="input-hint">tu correo electrónico para notificaciones</p>
                    </div>

                    <div class="form-divider">
                        <span class="material-icons-outlined">movie</span>
                        <span class="divider-text">preferencias</span>
                    </div>

                    <div class="form-section">
                        <label class="form-section-title">
                            <span class="material-icons-outlined">favorite</span>
                            Películas favoritas del perfil (máximo 5)
                        </label>
                        <p class="input-hint" style="margin-bottom: 1rem;">Elige tus 5 películas favoritas.</p>

                        <div class="favorite-movies-editor">
                            <div class="current-movies-grid" id="currentMoviesGrid"></div>

                            <button type="button" class="btn-search-movie" id="btnSearchMovie">
                                <span class="material-icons-outlined">add_circle</span>
                                Buscar película
                            </button>

                            <div class="movie-search-panel" id="movieSearchPanel" style="display: none;">
                                <div class="search-input-wrapper">
                                    <span class="material-icons-outlined">search</span>
                                    <input type="text" id="movieSearchInput" class="movie-search-input" placeholder="Busca una película por título...">
                                    <button type="button" class="close-search-btn" id="closeSearchPanel">
                                        <span class="material-icons-outlined">close</span>
                                    </button>
                                </div>
                                <div class="movie-search-results" id="movieSearchResults">
                                    <p class="search-placeholder">Escribe el título de una película para buscar</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <label class="form-section-title">
                            <span class="material-icons-outlined">category</span>
                            Géneros favoritos
                        </label>
                        <p class="input-hint" style="margin-bottom: 1rem;">selecciona tus géneros cinematográficos preferidos</p>

                        <div class="genres-editor">
                            <div class="selected-genres" id="selectedGenres"></div>
                            <div class="available-genres" id="availableGenres"></div>
                        </div>
                    </div>

                    <div class="form-divider">
                        <span class="material-icons-outlined">security</span>
                        <span class="divider-text">seguridad</span>
                    </div>

                    <div class="form-section">
                        <label class="form-section-title">Cambiar contraseña</label>
                        <p class="input-hint" style="margin-bottom: 1rem;">deja estos campos en blanco si no deseas cambiar tu contraseña</p>

                        <div class="password-fields">
                            <div class="password-field-group">
                                <label for="currentPassword" class="form-label">
                                    <span class="material-icons-outlined">lock</span>
                                    Contraseña actual
                                </label>
                                <div class="password-input-wrapper">
                                    <input type="password" id="currentPassword" name="currentPassword" class="form-input password-input" placeholder="introduce tu contraseña actual">
                                    <button type="button" class="toggle-password" data-target="currentPassword">
                                        <span class="material-icons-outlined">visibility</span>
                                    </button>
                                </div>
                            </div>

                            <div class="password-field-group">
                                <label for="newPassword" class="form-label">
                                    <span class="material-icons-outlined">lock_open</span>
                                    Nueva contraseña
                                </label>
                                <div class="password-input-wrapper">
                                    <input type="password" id="newPassword" name="newPassword" class="form-input password-input" placeholder="introduce tu nueva contraseña">
                                    <button type="button" class="toggle-password" data-target="newPassword">
                                        <span class="material-icons-outlined">visibility</span>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength" style="display: none;">
                                    <div class="strength-bar">
                                        <div class="strength-bar-fill" id="strengthBarFill"></div>
                                    </div>
                                    <p class="strength-text" id="strengthText">débil</p>
                                </div>
                            </div>

                            <div class="password-field-group">
                                <label for="confirmPassword" class="form-label">
                                    <span class="material-icons-outlined">check_circle</span>
                                    Confirmar nueva contraseña
                                </label>
                                <div class="password-input-wrapper">
                                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-input password-input" placeholder="confirma tu nueva contraseña">
                                    <button type="button" class="toggle-password" data-target="confirmPassword">
                                        <span class="material-icons-outlined">visibility</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="password-requirements">
                            <p class="requirements-title">La contraseña debe contener:</p>
                            <ul class="requirements-list">
                                <li id="req-length">
                                    <span class="material-icons-outlined">cancel</span>
                                    al menos 8 caracteres
                                </li>
                                <li id="req-uppercase">
                                    <span class="material-icons-outlined">cancel</span>
                                    una letra mayúscula
                                </li>
                                <li id="req-lowercase">
                                    <span class="material-icons-outlined">cancel</span>
                                    una letra minúscula
                                </li>
                                <li id="req-number">
                                    <span class="material-icons-outlined">cancel</span>
                                    un número
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancelar" id="btnCancelar">
                            <span class="material-icons-outlined">close</span>
                            Cancelar cambios
                        </button>
                        <button type="submit" class="btn-guardar">
                            <span class="material-icons-outlined">save</span>
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        window.takeonePerfilConfig = {
            getPerfilUrl: 'api/obtener-perfil.php',
            savePerfilUrl: 'api/guardar-perfil.php',
            searchMoviesUrl: 'api/buscar-peliculas-perfil.php'
        };
    </script>
    <script src="js/editar-peliculas-generos.js"></script>
    <script src="js/editar-perfil.js"></script>
</body>

</html>