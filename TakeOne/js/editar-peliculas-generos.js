let favoriteMovies = [];
let selectedGenres = [];
let allGenres = [];
let activeSlotIndex = null;

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text ?? "";
  return div.innerHTML;
}

function getGenreNameById(idGenero) {
  const genre = allGenres.find(
    (item) => Number(item.id_genero) === Number(idGenero),
  );
  return genre ? genre.nombre : "";
}

function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === "success" ? "#10b981" : "#ef4444"};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
  notification.textContent = message;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = "slideOut 0.3s ease";
    setTimeout(() => notification.remove(), 300);
  }, 2500);
}

function ensureAnimationStyles() {
  if (document.getElementById("perfilNotificationStyles")) return;

  const style = document.createElement("style");
  style.id = "perfilNotificationStyles";
  style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    `;
  document.head.appendChild(style);
}

function normalizeFavoriteMovies(slots) {
  if (!Array.isArray(slots)) return [];

  return slots
    .filter(Boolean)
    .map((movie) => ({
      id: Number(movie.id_pelicula),
      title: movie.titulo,
      poster: movie.poster,
      orden: Number(movie.orden),
    }))
    .sort((a, b) => a.orden - b.orden);
}

function renderMovies() {
  const grid = document.getElementById("currentMoviesGrid");
  const searchBtn = document.getElementById("btnSearchMovie");
  if (!grid) return;

  grid.innerHTML = "";

  for (let slot = 1; slot <= 5; slot++) {
    const movie = favoriteMovies.find((item) => Number(item.orden) === slot);

    const slotEl = document.createElement("div");
    slotEl.className = movie ? "movie-slot filled" : "movie-slot empty";
    slotEl.dataset.slot = String(slot);

    if (movie) {
      slotEl.innerHTML = `
                <img src="${escapeHtml(movie.poster || "img/placeholder-movie.jpg")}" alt="${escapeHtml(movie.title)}" onerror="this.src='img/placeholder-movie.jpg'">
                <button type="button" class="remove-movie-btn" data-slot-remove="${slot}">
                    <span class="material-icons-outlined">close</span>
                </button>
                <div class="movie-overlay">
                    <span class="movie-title">${escapeHtml(movie.title)}</span>
                </div>
            `;
    } else {
      slotEl.innerHTML = `
                <button type="button" class="movie-slot-add-btn" data-slot-add="${slot}">
                    <span class="material-icons-outlined movie-slot-add-icon">add</span>
                </button>
            `;
    }

    grid.appendChild(slotEl);
  }

  if (searchBtn) {
    const hasFreeSlot = favoriteMovies.length < 5;
    searchBtn.disabled = !hasFreeSlot;
    searchBtn.style.opacity = hasFreeSlot ? "1" : "0.5";
    searchBtn.style.cursor = hasFreeSlot ? "pointer" : "not-allowed";
  }

  grid.querySelectorAll("[data-slot-remove]").forEach((btn) => {
    btn.addEventListener("click", function () {
      const slot = Number(this.dataset.slotRemove);
      removeMovie(slot);
    });
  });

  grid.querySelectorAll("[data-slot-add]").forEach((btn) => {
    btn.addEventListener("click", function () {
      const slot = Number(this.dataset.slotAdd);
      openSearchPanel(slot);
    });
  });
}

function removeMovie(slot) {
  favoriteMovies = favoriteMovies
    .filter((movie) => Number(movie.orden) !== Number(slot))
    .sort((a, b) => a.orden - b.orden)
    .map((movie, index) => ({
      ...movie,
      orden: index + 1,
    }));

  renderMovies();
}

function openSearchPanel(slot = null) {
  const panel = document.getElementById("movieSearchPanel");
  const input = document.getElementById("movieSearchInput");
  const results = document.getElementById("movieSearchResults");

  activeSlotIndex = slot;

  if (panel) panel.style.display = "block";
  if (input) {
    input.value = "";
    input.focus();
  }
  if (results) {
    results.innerHTML =
      '<p class="search-placeholder">escribe el título de una película para buscar</p>';
  }
}

function closeSearchPanel() {
  const panel = document.getElementById("movieSearchPanel");
  const input = document.getElementById("movieSearchInput");
  const results = document.getElementById("movieSearchResults");

  activeSlotIndex = null;

  if (panel) panel.style.display = "none";
  if (input) input.value = "";
  if (results) {
    results.innerHTML =
      '<p class="search-placeholder">escribe el título de una película para buscar</p>';
  }
}

async function searchMovies(query) {
  const resultsContainer = document.getElementById("movieSearchResults");
  if (!resultsContainer) return;

  if (query.trim().length < 2) {
    resultsContainer.innerHTML =
      '<p class="search-placeholder">escribe al menos 2 caracteres para buscar</p>';
    return;
  }

  try {
    const url = `${window.takeonePerfilConfig.searchMoviesUrl}?q=${encodeURIComponent(query)}`;
    const response = await fetch(url, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    const data = await response.json();

    if (!data.ok) {
      resultsContainer.innerHTML =
        '<p class="search-placeholder">no se pudo realizar la búsqueda</p>';
      return;
    }

    const results = Array.isArray(data.results) ? data.results : [];

    if (!results.length) {
      resultsContainer.innerHTML =
        '<p class="search-placeholder">no se encontraron películas</p>';
      return;
    }

    resultsContainer.innerHTML = results
      .map(
        (movie) => `
            <div class="movie-result-item" data-add-movie='${JSON.stringify(movie).replace(/'/g, "&#39;")}'>
                <img
                    src="${escapeHtml(movie.poster || "img/placeholder-movie.jpg")}"
                    alt="${escapeHtml(movie.title)}"
                    class="movie-result-poster"
                    onerror="this.src='img/placeholder-movie.jpg'">
                <div class="movie-result-info">
                    <div class="movie-result-title">${escapeHtml(movie.title)}</div>
                    <div class="movie-result-year">${movie.year ? movie.year : ""}</div>
                    <div class="movie-result-overview">${escapeHtml(movie.overview || "")}</div>
                </div>
            </div>
        `,
      )
      .join("");

    resultsContainer.querySelectorAll("[data-add-movie]").forEach((item) => {
      item.addEventListener("click", function () {
        const movie = JSON.parse(this.getAttribute("data-add-movie"));
        addMovie(movie);
      });
    });
  } catch (error) {
    resultsContainer.innerHTML =
      '<p class="search-placeholder">error al buscar películas</p>';
  }
}

function addMovie(movie) {
  const movieId = Number(movie.id);

  if (favoriteMovies.some((item) => Number(item.id) === movieId)) {
    showNotification(
      "esa película ya está en tus favoritas del perfil",
      "error",
    );
    return;
  }

  if (favoriteMovies.length >= 5 && activeSlotIndex === null) {
    showNotification("ya tienes 5 películas favoritas", "error");
    return;
  }

  if (activeSlotIndex !== null) {
    favoriteMovies = favoriteMovies.filter(
      (item) => Number(item.orden) !== Number(activeSlotIndex),
    );
    favoriteMovies.push({
      id: movieId,
      title: movie.title,
      poster: movie.poster,
      orden: activeSlotIndex,
    });
  } else {
    favoriteMovies.push({
      id: movieId,
      title: movie.title,
      poster: movie.poster,
      orden: favoriteMovies.length + 1,
    });
  }

  favoriteMovies = favoriteMovies
    .sort((a, b) => a.orden - b.orden)
    .map((item, index) => ({
      ...item,
      orden: index + 1,
    }));

  renderMovies();
  closeSearchPanel();
  showNotification("película añadida (no olvides guardar cambios)", "success");
}

function renderGenres() {
  const selectedContainer = document.getElementById("selectedGenres");
  const availableContainer = document.getElementById("availableGenres");

  if (!selectedContainer || !availableContainer) return;

  selectedContainer.innerHTML = selectedGenres
    .map(
      (idGenero) => `
        <span class="genre-tag selected" data-genre="${idGenero}">
            ${escapeHtml(getGenreNameById(idGenero))}
            <button type="button" class="remove-genre-btn" data-remove-genre="${idGenero}">
                <span class="material-icons-outlined">close</span>
            </button>
        </span>
    `,
    )
    .join("");

  const availableGenres = allGenres.filter(
    (genre) => !selectedGenres.includes(Number(genre.id_genero)),
  );

  availableContainer.innerHTML = availableGenres
    .map(
      (genre) => `
        <span class="genre-tag" data-add-genre="${genre.id_genero}">
            ${escapeHtml(genre.nombre)}
        </span>
    `,
    )
    .join("");

  selectedContainer.querySelectorAll("[data-remove-genre]").forEach((btn) => {
    btn.addEventListener("click", function () {
      removeGenre(Number(this.dataset.removeGenre));
    });
  });

  availableContainer.querySelectorAll("[data-add-genre]").forEach((btn) => {
    btn.addEventListener("click", function () {
      addGenre(Number(this.dataset.addGenre));
    });
  });
}

function addGenre(idGenero) {
  if (selectedGenres.includes(idGenero)) return;
  selectedGenres.push(idGenero);
  renderGenres();
}

function removeGenre(idGenero) {
  selectedGenres = selectedGenres.filter(
    (id) => Number(id) !== Number(idGenero),
  );
  renderGenres();
}

async function loadProfileData() {
  const response = await fetch(window.takeonePerfilConfig.getPerfilUrl, {
    headers: { "X-Requested-With": "XMLHttpRequest" },
  });

  const data = await response.json();

  if (!data.ok) {
    throw new Error(data.message || "no se pudo cargar el perfil");
  }

  const perfil = data.perfil;

  document.getElementById("username").value = perfil.username || "";
  document.getElementById("bio").value = perfil.biografia || "";
  document.getElementById("location").value = perfil.localidad || "";
  document.getElementById("email").value = perfil.email || "";
  document.getElementById("avatarPreview").src =
    perfil.avatar || "img/default-avatar.png";

  favoriteMovies = normalizeFavoriteMovies(perfil.peliculas_favoritas);
  allGenres = Array.isArray(perfil.todos_generos) ? perfil.todos_generos : [];
  selectedGenres = Array.isArray(perfil.generos_favoritos)
    ? perfil.generos_favoritos.map((item) => Number(item.id_genero))
    : [];

  renderMovies();
  renderGenres();

  const bioCharCount = document.getElementById("bioCharCount");
  if (bioCharCount) {
    bioCharCount.textContent = `${document.getElementById("bio").value.length}/200`;
  }
}

document.addEventListener("DOMContentLoaded", function () {
  ensureAnimationStyles();

  const searchBtn = document.getElementById("btnSearchMovie");
  const closeBtn = document.getElementById("closeSearchPanel");
  const searchInput = document.getElementById("movieSearchInput");

  if (searchBtn) {
    searchBtn.addEventListener("click", function () {
      if (favoriteMovies.length >= 5) return;
      openSearchPanel(null);
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener("click", closeSearchPanel);
  }

  if (searchInput) {
    let timer = null;

    searchInput.addEventListener("input", function () {
      clearTimeout(timer);
      const query = this.value.trim();

      timer = setTimeout(() => {
        searchMovies(query);
      }, 300);
    });
  }

  loadProfileData().catch((error) => {
    console.error(error);
    showNotification("no se pudo cargar el perfil", "error");
  });
});

window.getFavoriteMoviesForSubmit = function () {
  return favoriteMovies
    .sort((a, b) => a.orden - b.orden)
    .map((movie) => ({
      id: Number(movie.id),
      orden: Number(movie.orden),
    }));
};

window.getSelectedGenresForSubmit = function () {
  return selectedGenres.map(Number);
};
