document.addEventListener("DOMContentLoaded", () => {
  // ── Referencias generales ──────────────────────────────────────────────────
  const btnCrear = document.querySelector(".crear-lista-btn");
  const modalOverlay = document.getElementById("modalOverlay");
  const modalClose = document.getElementById("modalClose");
  const cancelarBtn = document.getElementById("cancelarBtn");
  const guardarBtn = document.getElementById("guardarListaBtn");
  const mensajeDiv = document.getElementById("modal-mis-listas-mensaje");

  // ── Previsualización de imagen ─────────────────────────────────────────────
  const portadaUpload = document.getElementById("portadaUpload");
  const portadaInput = document.getElementById("listaPortada");
  const portadaPreviewImg = document.getElementById("portadaPreviewImg");
  const portadaPlaceholder = document.getElementById("portadaPlaceholder");

  portadaUpload?.addEventListener("click", () => portadaInput.click());

  portadaInput?.addEventListener("change", () => {
    const archivo = portadaInput.files[0];
    if (!archivo) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      portadaPreviewImg.src = e.target.result;
      portadaPreviewImg.style.display = "block";
      portadaPlaceholder.style.display = "none";
    };
    reader.readAsDataURL(archivo);
  });

  // ── Buscador de listas ─────────────────────────────────────────────────────
  const inputBusqueda = document.getElementById("buscadorListas");
  const btnLimpiar = document.getElementById("limpiarBusqueda");
  const grid = document.getElementById("listasGrid");
  const infoResultados = document.getElementById("resultadosBusqueda");

  let debounceTimer = null;

  function renderizarListas(listas) {
    if (!listas.length) {
      grid.innerHTML = `<p class="no-resultados w-100 text-center">No se encontraron listas para esa búsqueda.</p>`;
      return;
    }
    grid.innerHTML = listas
      .map(
        (lista) => `
      <div class="lista-card">
        <a href="detalle-lista.php?id=${lista.id_lista}">
          <img src="${escapeHtml(lista.imagen)}" alt="${escapeHtml(lista.titulo)}">
          <div class="lista-overlay">
            <span class="lista-badge">${escapeHtml(lista.titulo)}</span>
          </div>
        </a>
      </div>
    `,
      )
      .join("");
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  async function buscarListas(q) {
    try {
      const res = await fetch(
        `api/buscar-listas.php?q=${encodeURIComponent(q)}`,
      );
      const data = await res.json();

      infoResultados.style.display = "block";
      infoResultados.innerHTML = `${data.length} resultado${data.length !== 1 ? "s" : ""} para <span class="highlight-busqueda">"${escapeHtml(q)}"</span>`;

      renderizarListas(data);
    } catch (e) {
      infoResultados.style.display = "block";
      infoResultados.textContent = "Error al buscar. Inténtalo de nuevo.";
    }
  }

  function restaurarListasOriginales() {
    // Recarga la página limpiamente para volver al estado inicial del servidor
    window.location.href = "listas.php";
  }

  inputBusqueda?.addEventListener("input", () => {
    const q = inputBusqueda.value.trim();

    // Mostrar/ocultar botón limpiar
    btnLimpiar.style.display = q.length ? "flex" : "none";

    clearTimeout(debounceTimer);

    if (!q) {
      infoResultados.style.display = "none";
      restaurarListasOriginales();
      return;
    }

    // Espera 350 ms desde que el usuario deja de escribir
    debounceTimer = setTimeout(() => buscarListas(q), 350);
  });

  btnLimpiar?.addEventListener("click", () => {
    inputBusqueda.value = "";
    btnLimpiar.style.display = "none";
    infoResultados.style.display = "none";
    restaurarListasOriginales();
  });

  // ── Abrir modal o redirigir a login ───────────────────────────────────────
  function abrirModal() {
    limpiarModal();
    modalOverlay.style.display = "flex";
  }

  btnCrear?.addEventListener("click", () => {
    if (btnCrear.dataset.logueado !== "true") {
      window.location.href = "login.php";
      return;
    }
    abrirModal();
  });

  // ── Cerrar modal ──────────────────────────────────────────────────────────
  function cerrarModal() {
    modalOverlay.style.display = "none";
    limpiarModal();
  }

  modalClose?.addEventListener("click", cerrarModal);
  cancelarBtn?.addEventListener("click", cerrarModal);
  modalOverlay?.addEventListener("click", (e) => {
    if (e.target === modalOverlay) cerrarModal();
  });

  // ── Limpiar campos del modal ───────────────────────────────────────────────
  function limpiarModal() {
    document.getElementById("listaNombre").value = "";
    document.getElementById("listaDescripcion").value = "";
    portadaInput.value = "";
    portadaPreviewImg.src = "";
    portadaPreviewImg.style.display = "none";
    portadaPlaceholder.style.display = "flex";
    document.querySelector(
      'input[name="visibilidad"][value="publica"]',
    ).checked = true;
    mensajeDiv.className = "alert d-none";
    mensajeDiv.textContent = "";
  }

  function mostrarMensaje(texto, tipo) {
    mensajeDiv.className = `alert alert-${tipo}`;
    mensajeDiv.textContent = texto;
  }

  // ── Guardar lista ─────────────────────────────────────────────────────────
  guardarBtn?.addEventListener("click", async () => {
    const titulo = document.getElementById("listaNombre").value.trim();
    const descripcion = document
      .getElementById("listaDescripcion")
      .value.trim();
    const visibilidad =
      document.querySelector('input[name="visibilidad"]:checked')?.value ||
      "publica";
    const archivo = portadaInput.files[0] || null;

    if (!titulo) {
      mostrarMensaje("El nombre de la lista es obligatorio.", "danger");
      return;
    }

    guardarBtn.disabled = true;
    guardarBtn.textContent = "Guardando...";

    try {
      const formData = new FormData();
      formData.append("titulo", titulo);
      formData.append("descripcion", descripcion);
      formData.append("visibilidad", visibilidad);
      if (archivo) formData.append("imagen", archivo);

      const res = await fetch("api/crear-lista.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();

      if (data.ok) {
        mostrarMensaje("¡Lista creada! Redirigiendo...", "success");
        setTimeout(() => {
          cerrarModal();
          window.location.href = "mis-listas.php";
        }, 1200);
      } else {
        mostrarMensaje(data.mensaje, "danger");
      }
    } catch (e) {
      mostrarMensaje("Error de conexión. Inténtalo de nuevo.", "danger");
    } finally {
      guardarBtn.disabled = false;
      guardarBtn.textContent = "Crear lista";
    }
  });
});
