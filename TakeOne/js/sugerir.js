const GENERO_ICONOS = {
    'acción': 'img/generos/accion.png',
    'animación': 'img/generos/animacion.png',
    'aventura': 'img/generos/aventura.png',
    'biográfico': 'img/generos/biografia.png',
    'ciencia': 'img/generos/ciencia-ficcion.png',
    'cine independiente': 'img/generos/cine-independiente.png',
    'comedia': 'img/generos/comedia.png',
    'crimen': 'img/generos/crimen.png',
    'drama': 'img/generos/drama.png',
    'fantasía': 'img/generos/fantasia.png',
    'intriga': 'img/generos/intriga.png',
    'policíaco': 'img/generos/policiaco.png',
    'road movie': 'img/generos/road-movie.png',
    'romance': 'img/generos/romance.png',
    'superhéroes': 'img/generos/superheroes.png',
    'terror': 'img/generos/terror.png',
    'thriller': 'img/generos/thriller.png',
    'thriller psicológico': 'img/generos/thriller-psicologico.png',
    'western': 'img/generos/western.png'
};

function iconoParaGenero(nombre) {
    const lower = nombre.toLowerCase();

    if (GENERO_ICONOS[lower]) return GENERO_ICONOS[lower];

    for (const [clave, ruta] of Object.entries(GENERO_ICONOS)) {
        if (lower.includes(clave)) return ruta;
    }

    return 'img/generos/default.png';
}

function claveHistorial() {
    return USUARIO_ID ? `historialSugerencias_u${USUARIO_ID}` : 'historialSugerencias_guest';
}

let historial = [];
let ultimoTipo = 'aleatoria';
let animacionEnCurso = false;

function cargarHistorial() {
    if (USUARIO_ID) {
        fetch('api/sugerir-api.php?tipo=historial')
            .then(r => r.json())
            .then(data => {
                if (data.historial && data.historial.length > 0) {
                    historial = data.historial;
                    guardarHistorial();
                    mostrarHistorial();
                }
            })
            .catch(() => {
                _cargarHistorialLocal();
            });
    } else {
        _cargarHistorialLocal();
    }
}

function _cargarHistorialLocal() {
    try {
        const guardado = localStorage.getItem(claveHistorial());
        if (guardado) {
            historial = JSON.parse(guardado);
            mostrarHistorial();
        }
    } catch (e) {
        historial = [];
    }
}

function guardarHistorial() {
    try {
        localStorage.setItem(claveHistorial(), JSON.stringify(historial));
    } catch (e) {}
}

async function cargarGenerosModal() {
    const grid = document.querySelector('.generos-grid');
    if (!grid) return;

    try {
        const res = await fetch('api/generos-api.php');
        if (!res.ok) throw new Error('HTTP ' + res.status);

        const data = await res.json();

        grid.innerHTML = '';

        data.generos.forEach(g => {
            const icono = iconoParaGenero(g.nombre);
            const label = document.createElement('label');

            label.className = 'genero-checkbox';
            label.innerHTML = `
                <input type="checkbox" value="${g.id_genero}">
                <span>
                    <img src="${icono}" alt="${g.nombre}">
                    ${g.nombre}
                </span>
            `;

            grid.appendChild(label);
        });
    } catch (err) {
        console.error('Error cargando géneros:', err);
        grid.innerHTML = '<p style="color:#666">No se pudieron cargar los géneros. Inténtalo de nuevo.</p>';
    }
}

async function obtenerPeliculaDesdeAPI(tipo, generoIds = []) {
    let url = `api/sugerir-api.php?tipo=${tipo}`;

    if (tipo === 'generos' && generoIds.length > 0) {
        url += `&generos=${generoIds.join(',')}`;
    }

    const res = await fetch(url);

    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.error || 'Error en el servidor');
    }

    const data = await res.json();

    if (data.error) throw new Error(data.error);

    return data.pelicula;
}

function precargarImagenes(urls = []) {
    return Promise.all(
        urls.map(src => new Promise(resolve => {
            const img = new Image();
            img.onload = resolve;
            img.onerror = resolve;
            img.src = src;
        }))
    );
}

async function animarBaraja(peliculaFinal) {
    const poster = document.getElementById('peliculaPoster');
    const info = document.getElementById('peliculaInfo');

    info.style.opacity = '0';

    let postersBarajado = [peliculaFinal.poster];

    try {
        const res = await fetch('api/sugerir-api.php?tipo=posters_random');
        const data = await res.json();

        if (data.posters && data.posters.length > 0) {
            postersBarajado = data.posters.filter(Boolean);
        }
    } catch (e) {
        postersBarajado = [peliculaFinal.poster];
    }

    if (!postersBarajado.includes(peliculaFinal.poster)) {
        postersBarajado.push(peliculaFinal.poster);
    }

    await precargarImagenes(postersBarajado);

    let ronda = 0;
    let postersUsados = [];

    const esMovil = window.innerWidth <= 768;
    const totalRondas = esMovil ? 3 : 5;
    const distancia = esMovil ? 120 : 200;

    function getRandomPoster() {
        if (postersBarajado.length === 0) return peliculaFinal.poster;

        let disponibles = postersBarajado.filter(p => !postersUsados.includes(p));

        if (disponibles.length === 0) {
            postersUsados = [];
            disponibles = [...postersBarajado];
        }

        const posterElegido = disponibles[Math.floor(Math.random() * disponibles.length)];
        postersUsados.push(posterElegido);

        return posterElegido;
    }

    return new Promise(resolve => {
        function barajar() {
            if (ronda >= totalRondas) {
                revelarFinal();
                return;
            }

            const goRight = Math.random() > 0.5;

            poster.style.transition = 'transform 0.20s ease-in, opacity 0.20s ease-in';
            poster.style.transform = `rotate(${goRight ? 14 : -14}deg) translate(${goRight ? distancia : -distancia}px, -35px)`;
            poster.style.opacity = '0';

            setTimeout(() => {
                poster.src = getRandomPoster();
                poster.style.transition = 'none';
                poster.style.transform = `rotate(${goRight ? -10 : 10}deg) translate(${goRight ? -80 : 80}px, -20px)`;
                poster.style.opacity = '0';

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        poster.style.transition = 'transform 0.26s ease-out, opacity 0.26s ease-out';
                        poster.style.transform = 'rotate(0deg) translate(0,0)';
                        poster.style.opacity = '1';
                    });
                });

                ronda++;

                setTimeout(barajar, esMovil ? 340 : 420);
            }, 220);
        }

        function revelarFinal() {
            poster.style.transition = 'transform 0.18s ease-in, opacity 0.18s ease-in';
            poster.style.transform = 'scale(0.92) translateY(10px)';
            poster.style.opacity = '0.35';

            setTimeout(() => {
                poster.src = peliculaFinal.poster;
                poster.style.transition = 'none';
                poster.style.transform = 'scale(1.05) translateY(-6px)';
                poster.style.opacity = '0';

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        poster.style.transition = 'transform 0.32s cubic-bezier(0.34,1.56,0.64,1), opacity 0.28s ease';
                        poster.style.transform = 'scale(1) translateY(0)';
                        poster.style.opacity = '1';

                        mostrarPelicula(peliculaFinal);
                        resolve();
                    });
                });
            }, 180);
        }

        barajar();
    });
}

function mostrarPelicula(pelicula) {
    document.getElementById('peliculaPoster').src = pelicula.poster;
    document.getElementById('peliculaTitulo').textContent = pelicula.titulo;
    document.getElementById('peliculaDescripcion').textContent = pelicula.descripcion || '';
    document.getElementById('peliculaAnio').textContent = pelicula.anio || '';
    document.getElementById('peliculaGenero').textContent = pelicula.generos_nombre || '';

    const btnVer = document.getElementById('btnVerMas');

    if (pelicula.id_pelicula) {
        btnVer.onclick = () => {
            window.location.href = `detalle-pelicula.php?id=${pelicula.id_pelicula}`;
        };
    }

    const info = document.getElementById('peliculaInfo');

    setTimeout(() => {
        info.style.transition = 'opacity 0.6s ease';
        info.style.opacity = '1';
    }, 350);

    historial.unshift(pelicula);

    if (historial.length > 6) {
        historial = historial.slice(0, 6);
    }

    guardarHistorial();
    mostrarHistorial();
}

function mostrarHistorial() {
    const contenedor = document.getElementById('historialContainer');
    const grid = document.getElementById('historialGrid');

    if (historial.length === 0) {
        contenedor.style.display = 'none';
        return;
    }

    contenedor.style.display = 'block';
    grid.innerHTML = '';

    historial.forEach(p => {
        const card = document.createElement('div');

        card.className = 'historial-card';
        card.innerHTML = `
            <img src="${p.poster}" alt="${p.titulo}" loading="lazy">
            <div class="historial-overlay">
                <h4>${p.titulo}</h4>
                <p>${p.anio || ''}</p>
            </div>
        `;

        if (p.id_pelicula) {
            card.style.cursor = 'pointer';
            card.addEventListener('click', () => {
                window.location.href = `detalle-pelicula.php?id=${p.id_pelicula}`;
            });
        }

        grid.appendChild(card);
    });
}

async function manejarSugerencia(tipo, generoIds = []) {
    if (animacionEnCurso) return;

    const resultadoContainer = document.getElementById('resultadoContainer');
    const btnOtra = document.getElementById('btnOtra');

    animacionEnCurso = true;

    resultadoContainer.style.display = 'block';
    resultadoContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });

    if (btnOtra) btnOtra.disabled = true;

    try {
        const pelicula = await obtenerPeliculaDesdeAPI(tipo, generoIds);
        await animarBaraja(pelicula);
    } catch (err) {
        console.error(err);

        document.getElementById('peliculaInfo').innerHTML =
            `<p style="color:#ff6347">No se pudo obtener una sugerencia. Inténtalo de nuevo.</p>`;

        document.getElementById('peliculaInfo').style.opacity = '1';
    } finally {
        animacionEnCurso = false;

        if (btnOtra) btnOtra.disabled = false;
    }
}

document.querySelectorAll('.opcion-card').forEach(card => {
    card.addEventListener('click', function () {
        if (animacionEnCurso) return;

        const tipo = this.getAttribute('data-tipo');
        ultimoTipo = tipo;

        if (tipo === 'generos') {
            document.getElementById('modalGeneros').style.display = 'flex';
        } else {
            manejarSugerencia(tipo);
        }
    });
});

document.getElementById('btnConfirmarGeneros').addEventListener('click', function () {
    if (animacionEnCurso) return;

    const seleccionados = Array.from(
        document.querySelectorAll('.genero-checkbox input:checked')
    ).map(i => i.value);

    ultimoTipo = 'generos';

    document.getElementById('modalGeneros').style.display = 'none';
    manejarSugerencia('generos', seleccionados);
});

document.getElementById('closeModal').addEventListener('click', () => {
    document.getElementById('modalGeneros').style.display = 'none';
});

document.getElementById('modalGeneros').addEventListener('click', function (e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});

document.getElementById('btnOtra').addEventListener('click', () => {
    if (animacionEnCurso) return;
    manejarSugerencia(ultimoTipo);
});

cargarHistorial();
cargarGenerosModal();