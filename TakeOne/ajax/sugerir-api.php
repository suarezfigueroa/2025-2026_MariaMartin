<?php

session_start();

require_once '../includes/conexion.php';

header('Content-Type: application/json');

$tipo = $_GET['tipo'] ?? 'aleatoria';
$generos = $_GET['generos'] ?? '';
$id_usuario = isset($_SESSION['usuario']['id']) ? (int)$_SESSION['usuario']['id'] : null;

// Condición reutilizable para excluir próximos estrenos (sin IMDB ni duración)
$no_estreno = "NOT (p.imdb IS NULL AND p.duracion IS NULL)";

try {
    $pelicula = null;

    switch ($tipo) {

        // ── Posters aleatorios para la animación baraja ──────────────────────
        case 'posters_random':
            $stmt = $pdo->query("
                SELECT poster FROM peliculas
                WHERE poster IS NOT NULL AND poster != ''
                AND NOT (imdb IS NULL AND duracion IS NULL)
                ORDER BY RAND()
                LIMIT 20
            ");
            $posters = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode(['posters' => $posters]);
            exit;

        // Acción historial
        case 'historial':
            if (!$id_usuario) {
                echo json_encode(['historial' => []]);
                exit;
            }
            $stmt = $pdo->prepare("
                SELECT p.id_pelicula, p.titulo, p.anio, p.sinopsis AS descripcion, p.poster,
                    GROUP_CONCAT(DISTINCT g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos_nombre
                FROM historial_sugerencias hs
                INNER JOIN peliculas p ON hs.id_pelicula = p.id_pelicula
                LEFT JOIN peliculas_generos pg ON p.id_pelicula = pg.id_pelicula
                LEFT JOIN generos g ON pg.id_genero = g.id_genero
                WHERE hs.id_usuario = ?
                GROUP BY hs.id_historial, p.id_pelicula
                ORDER BY hs.fecha DESC
                LIMIT 6
            ");
            $stmt->execute([$id_usuario]);
            echo json_encode(['historial' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;

        // ── 1. ALEATORIA pura ────────────────────────────────────────────────
        case 'aleatoria':
            if ($id_usuario) {
                $stmt = $pdo->prepare("
                    SELECT p.id_pelicula, p.titulo, p.anio, p.sinopsis AS descripcion, p.poster,
                           GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos_nombre
                    FROM peliculas p
                    LEFT JOIN peliculas_generos pg ON p.id_pelicula = pg.id_pelicula
                    LEFT JOIN generos g            ON pg.id_genero  = g.id_genero
                    WHERE p.id_pelicula NOT IN (
                        SELECT id_pelicula FROM usuarios_peliculas
                        WHERE id_usuario = ? AND estado IN ('vista', 'favorita')
                    )
                    AND NOT (p.imdb IS NULL AND p.duracion IS NULL)
                    GROUP BY p.id_pelicula
                    ORDER BY RAND()
                    LIMIT 1
                ");
                $stmt->execute([$id_usuario]);
            } else {
                $stmt = $pdo->query("
                    SELECT p.id_pelicula, p.titulo, p.anio, p.sinopsis AS descripcion, p.poster,
                           GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos_nombre
                    FROM peliculas p
                    LEFT JOIN peliculas_generos pg ON p.id_pelicula = pg.id_pelicula
                    LEFT JOIN generos g            ON pg.id_genero  = g.id_genero
                    WHERE NOT (p.imdb IS NULL AND p.duracion IS NULL)
                    GROUP BY p.id_pelicula
                    ORDER BY RAND()
                    LIMIT 1
                ");
            }
            $pelicula = $stmt->fetch(PDO::FETCH_ASSOC);
            break;

        // ── 2. FILTRADA por géneros ──────────────────────────────────────────
        case 'generos':
            if (empty($generos)) {
                // Sin filtro → aleatoria
                if ($id_usuario) {
                    $stmt = $pdo->prepare("
                        SELECT p.id_pelicula, p.titulo, p.anio, p.sinopsis AS descripcion, p.poster,
                               GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos_nombre
                        FROM peliculas p
                        LEFT JOIN peliculas_generos pg ON p.id_pelicula = pg.id_pelicula
                        LEFT JOIN generos g            ON pg.id_genero  = g.id_genero
                        WHERE p.id_pelicula NOT IN (
                            SELECT id_pelicula FROM usuarios_peliculas
                            WHERE id_usuario = ? AND estado IN ('vista', 'favorita')
                        )
                        AND NOT (p.imdb IS NULL AND p.duracion IS NULL)
                        GROUP BY p.id_pelicula
                        ORDER BY RAND()
                        LIMIT 1
                    ");
                    $stmt->execute([$id_usuario]);
                } else {
                    $stmt = $pdo->query("
                        SELECT p.id_pelicula, p.titulo, p.anio, p.sinopsis AS descripcion, p.poster,
                               GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos_nombre
                        FROM peliculas p
                        LEFT JOIN peliculas_generos pg ON p.id_pelicula = pg.id_pelicula
                        LEFT JOIN generos g            ON pg.id_genero  = g.id_genero
                        WHERE NOT (p.imdb IS NULL AND p.duracion IS NULL)
                        GROUP BY p.id_pelicula
                        ORDER BY RAND()
                        LIMIT 1
                    ");
                }
                $pelicula = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            }

            // Sanitizar: solo números y comas
            $generos_sanitized = preg_replace('/[^0-9,]/', '', $generos);
            $ids = array_filter(array_map('intval', explode(',', $generos_sanitized)));

            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['error' => 'IDs de género no válidos']);
                exit;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // ── Intento 1: película que tenga TODOS los géneros seleccionados ─
            $stmt = $pdo->prepare("
                SELECT p.id_pelicula, p.titulo, p.anio, p.sinopsis AS descripcion, p.poster,
                       GROUP_CONCAT(g2.nombre ORDER BY g2.nombre SEPARATOR ', ') AS generos_nombre
                FROM peliculas p
                INNER JOIN peliculas_generos pg  ON p.id_pelicula = pg.id_pelicula
                                                AND pg.id_genero IN ($placeholders)
                LEFT JOIN  peliculas_generos pg2 ON p.id_pelicula = pg2.id_pelicula
                LEFT JOIN  generos g2            ON pg2.id_genero = g2.id_genero
                WHERE p.id_pelicula NOT IN (
                    SELECT id_pelicula FROM usuarios_peliculas
                    WHERE id_usuario = ? AND estado IN ('vista', 'favorita')
                )
                AND NOT (p.imdb IS NULL AND p.duracion IS NULL)
                GROUP BY p.id_pelicula
                HAVING COUNT(DISTINCT pg.id_genero) = ?
                ORDER BY RAND()
                LIMIT 1
            ");
            $params = $id_usuario
                ? array_merge($ids, [$id_usuario, count($ids)])
                : array_merge($ids, [0, count($ids)]);
            $stmt->execute($params);
            $pelicula = $stmt->fetch(PDO::FETCH_ASSOC);

            // ── Intento 2 (fallback): película que tenga AL MENOS UNO ─────────
            if (!$pelicula) {
                $stmt = $pdo->prepare("
                    SELECT p.id_pelicula, p.titulo, p.anio, p.sinopsis AS descripcion, p.poster,
                           GROUP_CONCAT(g2.nombre ORDER BY g2.nombre SEPARATOR ', ') AS generos_nombre
                    FROM peliculas p
                    INNER JOIN peliculas_generos pg  ON p.id_pelicula = pg.id_pelicula
                                                    AND pg.id_genero IN ($placeholders)
                    LEFT JOIN  peliculas_generos pg2 ON p.id_pelicula = pg2.id_pelicula
                    LEFT JOIN  generos g2            ON pg2.id_genero = g2.id_genero
                    WHERE p.id_pelicula NOT IN (
                        SELECT id_pelicula FROM usuarios_peliculas
                        WHERE id_usuario = ? AND estado IN ('vista', 'favorita')
                    )
                    AND NOT (p.imdb IS NULL AND p.duracion IS NULL)
                    GROUP BY p.id_pelicula
                    ORDER BY RAND()
                    LIMIT 1
                ");
                $params = $id_usuario
                    ? array_merge($ids, [$id_usuario])
                    : array_merge($ids, [0]);
                $stmt->execute($params);
                $pelicula = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            break;

        // ── 3. BASADA EN GUSTOS del usuario ─────────────────────────────────
        case 'gustos':
            if (!$id_usuario) {
                // Usuario no logueado → aleatoria
                $stmt = $pdo->query("
                    SELECT 
                        p.id_pelicula, 
                        p.titulo, 
                        p.anio, 
                        p.sinopsis AS descripcion, 
                        p.poster,
                        GROUP_CONCAT(DISTINCT g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos_nombre
                    FROM peliculas p
                    LEFT JOIN peliculas_generos pg ON p.id_pelicula = pg.id_pelicula
                    LEFT JOIN generos g ON pg.id_genero = g.id_genero
                    WHERE NOT (p.imdb IS NULL AND p.duracion IS NULL)
                    GROUP BY p.id_pelicula
                    ORDER BY RAND()
                    LIMIT 1
                ");
                $pelicula = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
            }

            $pelicula = null;

            // 1. intentar recomendar según géneros de películas del usuario
            $stmt_favoritas = $pdo->prepare("
                SELECT id_pelicula 
                FROM usuarios_peliculas 
                WHERE id_usuario = ?
            ");
            $stmt_favoritas->execute([$id_usuario]);
            $peliculas_favoritas = $stmt_favoritas->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($peliculas_favoritas)) {
                $stmt = $pdo->prepare("
                    SELECT 
                        p.id_pelicula,
                        p.titulo,
                        p.anio,
                        p.sinopsis AS descripcion,
                        p.poster,
                        GROUP_CONCAT(DISTINCT g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos_nombre,
                        COUNT(DISTINCT pg.id_genero) AS coincidencias_genero
                    FROM peliculas p
                    INNER JOIN peliculas_generos pg 
                        ON p.id_pelicula = pg.id_pelicula
                    INNER JOIN generos g
                        ON pg.id_genero = g.id_genero
                    WHERE pg.id_genero IN (
                        SELECT DISTINCT pgf.id_genero
                        FROM usuarios_peliculas up
                        INNER JOIN peliculas_generos pgf 
                            ON up.id_pelicula = pgf.id_pelicula
                        WHERE up.id_usuario = ?
                    )
                    AND p.id_pelicula NOT IN (
                        SELECT id_pelicula 
                        FROM usuarios_peliculas 
                        WHERE id_usuario = ? AND estado IN ('vista', 'favorita')
                    )
                    AND NOT (p.imdb IS NULL AND p.duracion IS NULL)
                    GROUP BY p.id_pelicula
                    ORDER BY coincidencias_genero DESC, RAND()
                    LIMIT 1
                ");
                $stmt->execute([$id_usuario, $id_usuario]);
                $pelicula = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // 2. si no tiene películas, usar géneros favoritos guardados
            if (!$pelicula) {
                $stmt_generos = $pdo->prepare("
                    SELECT id_genero 
                    FROM usuarios_generos_favoritos 
                    WHERE id_usuario = ?
                ");
                $stmt_generos->execute([$id_usuario]);
                $generos_usuario = $stmt_generos->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($generos_usuario)) {
                    $ph = implode(',', array_fill(0, count($generos_usuario), '?'));

                    $stmt = $pdo->prepare("
                        SELECT 
                            p.id_pelicula,
                            p.titulo,
                            p.anio,
                            p.sinopsis AS descripcion,
                            p.poster,
                            GROUP_CONCAT(DISTINCT g2.nombre ORDER BY g2.nombre SEPARATOR ', ') AS generos_nombre,
                            COUNT(DISTINCT pg.id_genero) AS coincidencias_genero
                        FROM peliculas p
                        INNER JOIN peliculas_generos pg 
                            ON p.id_pelicula = pg.id_pelicula
                            AND pg.id_genero IN ($ph)
                        LEFT JOIN peliculas_generos pg2 
                            ON p.id_pelicula = pg2.id_pelicula
                        LEFT JOIN generos g2 
                            ON pg2.id_genero = g2.id_genero
                        WHERE p.id_pelicula NOT IN (
                            SELECT id_pelicula 
                            FROM usuarios_peliculas 
                            WHERE id_usuario = ? AND estado IN ('vista', 'favorita')
                        )
                        AND NOT (p.imdb IS NULL AND p.duracion IS NULL)
                        GROUP BY p.id_pelicula
                        ORDER BY coincidencias_genero DESC, RAND()
                        LIMIT 1
                    ");

                    $params = array_merge($generos_usuario, [$id_usuario]);
                    $stmt->execute($params);
                    $pelicula = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            // 3. fallback: aleatoria entre películas no vistas ni favoritas
            if (!$pelicula) {
                $stmt = $pdo->prepare("
                    SELECT 
                        p.id_pelicula, 
                        p.titulo, 
                        p.anio, 
                        p.sinopsis AS descripcion, 
                        p.poster,
                        GROUP_CONCAT(DISTINCT g.nombre ORDER BY g.nombre SEPARATOR ', ') AS generos_nombre
                    FROM peliculas p
                    LEFT JOIN peliculas_generos pg ON p.id_pelicula = pg.id_pelicula
                    LEFT JOIN generos g ON pg.id_genero = g.id_genero
                    WHERE p.id_pelicula NOT IN (
                        SELECT id_pelicula 
                        FROM usuarios_peliculas 
                        WHERE id_usuario = ? AND estado IN ('vista', 'favorita')
                    )
                    AND NOT (p.imdb IS NULL AND p.duracion IS NULL)
                    GROUP BY p.id_pelicula
                    ORDER BY RAND()
                    LIMIT 1
                ");
                $stmt->execute([$id_usuario]);
                $pelicula = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Tipo no reconocido']);
            exit;
    }

    if (!$pelicula) {
        http_response_code(404);
        echo json_encode(['error' => 'No se encontró ninguna película']);
        exit;
    }

    // ── Guardar en historial_sugerencias (solo usuarios logueados) ───────────
    if ($id_usuario) {
        $ins = $pdo->prepare("
            INSERT INTO historial_sugerencias (id_usuario, id_pelicula)
            VALUES (?, ?)
        ");
        $ins->execute([$id_usuario, $pelicula['id_pelicula']]);
    }

    echo json_encode(['pelicula' => $pelicula]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos', 'detalle' => $e->getMessage()]);
}