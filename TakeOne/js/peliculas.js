document.addEventListener("DOMContentLoaded", () => {
  // ── Menú hamburguesa ─────────────────────────────────────────
  const hamburger = document.querySelector(".hamburger");
  const navCenter = document.querySelector(".nav-center");

  if (hamburger) {
    hamburger.addEventListener("click", () => {
      navCenter.classList.toggle("active");
    });
  }

  // ── Dropdowns de filtros ──────────────────────────────────────
  document.querySelectorAll(".filter-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const dropdown = btn.nextElementSibling;
      if (!dropdown || !dropdown.classList.contains("dropdown-menu-custom"))
        return;
      document.querySelectorAll(".dropdown-menu-custom").forEach((d) => {
        if (d !== dropdown) d.classList.remove("show");
      });
      dropdown.classList.toggle("show");
    });
  });

  document.addEventListener("click", (e) => {
    if (!e.target.closest(".filter-dropdown")) {
      document.querySelectorAll(".dropdown-menu-custom").forEach((d) => {
        d.classList.remove("show");
      });
    }
  });

  // ── Carruseles ────────────────────────────────────────────────
  function setupCarousel(wrapperId, leftId, rightId) {
    const wrapper = document.getElementById(wrapperId);
    const left = document.getElementById(leftId);
    const right = document.getElementById(rightId);

    if (!wrapper || !left || !right) return;

    left.addEventListener("click", () => {
      wrapper.scrollBy({ left: -350, behavior: "smooth" });
    });

    right.addEventListener("click", () => {
      wrapper.scrollBy({ left: 350, behavior: "smooth" });
    });
  }

  setupCarousel("popularSlider", "popularLeft", "popularRight");
  setupCarousel("valoradasSlider", "valoradasLeft", "valoradasRight");

  // ── Animación de entrada (solo sliders) ──────────────────────
  document
    .querySelectorAll("#popularSlider .h-item, #valoradasSlider .h-item")
    .forEach((el, i) => {
      el.style.opacity = "0";
      el.style.transform = "translateY(20px)";
      setTimeout(() => {
        el.style.transition = "all 0.5s ease";
        el.style.opacity = "1";
        el.style.transform = "translateY(0)";
      }, i * 50);
    });

  // Animación para el grid de resultados
  document.querySelectorAll(".results-grid .h-item").forEach((el, i) => {
    el.style.opacity = "0";
    el.style.transform = "translateY(20px)";
    setTimeout(() => {
      el.style.transition = "all 0.5s ease";
      el.style.opacity = "1";
      el.style.transform = "translateY(0)";
    }, i * 80);
  });

  // ── Nav activo ────────────────────────────────────────────────
  const currentUrl = window.location.pathname.split("/").pop();
  document.querySelectorAll(".nav-center a").forEach((link) => {
    if (link.getAttribute("href") === currentUrl) {
      link.classList.add("active");
    }
  });

  // ── Búsqueda en tiempo real ───────────────────────────────────
  const inputPeliculas = document.getElementById("buscadorPeliculas");
  const btnLimpiar = document.getElementById("limpiarBusquedaPeliculas");
  const resultadosDinamicos = document.getElementById("resultadosDinamicos");
  const gridDinamico = document.getElementById("gridDinamico");
  const tituloDinamico = document.getElementById("tituloDinamico");
  const seccionSliders = document.getElementById("seccionSliders");
  const seccionFiltrosPHP = document.getElementById("seccionFiltrosPHP");

  let debounceTimer = null;

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function renderizarPeliculas(peliculas, q) {
    if (!peliculas.length) {
      tituloDinamico.innerHTML = `Sin resultados para <span style="color:var(--accent-light);">"${escapeHtml(q)}"</span>`;
      gridDinamico.innerHTML = `<p style="color:rgba(255,255,255,0.6);padding:2rem 0;">No se encontraron películas con ese título.</p>`;
      return;
    }

    tituloDinamico.innerHTML = `Resultados para <span style="color:var(--accent-light);">"${escapeHtml(q)}"</span>
       <span style="font-size:1rem;font-weight:400;opacity:0.7;">(${peliculas.length} película${peliculas.length !== 1 ? "s" : ""})</span>`;

    gridDinamico.innerHTML = peliculas
      .map(
        (p) => `
      <div class="h-item" style="opacity:0;transform:translateY(20px);">
        <a href="detalle-pelicula.php?id=${p.id_pelicula}">
          <img src="${escapeHtml(p.poster)}"
               alt="${escapeHtml(p.titulo)}"
               onerror="this.src='img/poster-placeholder.jpg'">
          <div class="hover-info">
            <h6>${escapeHtml(p.titulo)}</h6>
            <small>${p.imdb} ⭐</small>
          </div>
        </a>
      </div>
    `,
      )
      .join("");

    gridDinamico.querySelectorAll(".h-item").forEach((el, i) => {
      setTimeout(() => {
        el.style.transition = "all 0.5s ease";
        el.style.opacity = "1";
        el.style.transform = "translateY(0)";
      }, i * 60);
    });
  }

  async function buscarPeliculas(q) {
    try {
      const res = await fetch(
        `api/buscar-peliculas.php?q=${encodeURIComponent(q)}`,
      );
      const data = await res.json();
      renderizarPeliculas(data, q);
      resultadosDinamicos.style.display = "block";
      if (seccionSliders) seccionSliders.style.display = "none";
      if (seccionFiltrosPHP) seccionFiltrosPHP.style.display = "none";
    } catch (e) {
      tituloDinamico.textContent = "Error al buscar. Inténtalo de nuevo.";
      resultadosDinamicos.style.display = "block";
    }
  }

  function restaurarVista() {
    resultadosDinamicos.style.display = "none";
    if (seccionSliders) seccionSliders.style.display = "";
    if (seccionFiltrosPHP) seccionFiltrosPHP.style.display = "";
  }

  inputPeliculas?.addEventListener("input", () => {
    const q = inputPeliculas.value.trim();

    btnLimpiar.style.display = q.length ? "flex" : "none";

    clearTimeout(debounceTimer);

    if (!q) {
      restaurarVista();
      return;
    }

    debounceTimer = setTimeout(() => buscarPeliculas(q), 350);
  });

  btnLimpiar?.addEventListener("click", () => {
    inputPeliculas.value = "";
    btnLimpiar.style.display = "none";
    restaurarVista();
    inputPeliculas.focus();
  });
}); // fin DOMContentLoaded
