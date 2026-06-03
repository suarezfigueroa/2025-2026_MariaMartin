document.addEventListener("DOMContentLoaded", function () {
  const btnLike = document.querySelector(".btn-like");

  if (btnLike) {
    const heartIcon = btnLike.querySelector(".material-icons-outlined");
    const likeText = btnLike.querySelector("span:last-child");
    let isLiked =
      typeof USUARIO_DIO_LIKE !== "undefined" ? USUARIO_DIO_LIKE : false;
    let procesando = false;

    btnLike.addEventListener("click", async function (e) {
      e.preventDefault();
      if (procesando) return;
      procesando = true;

      try {
        const fd = new FormData();
        fd.append("id_lista", ID_LISTA);

        const res = await fetch("ajax/toggle-like-lista.php", {
          method: "POST",
          headers: { "X-Requested-With": "XMLHttpRequest" },
          body: fd,
        });
        const data = await res.json();

        if (!data.ok) {
          if (data.mensaje?.includes("sesión")) {
            window.location.href = "login.php";
          }
          return;
        }

        isLiked = data.liked;
        heartIcon.textContent = isLiked ? "favorite" : "favorite_border";
        likeText.textContent = isLiked ? "Te gusta" : "Me gusta";
        btnLike.classList.toggle("liked", isLiked);
        btnLike.style.transform = "scale(1.15)";
        setTimeout(() => (btnLike.style.transform = "scale(1)"), 200);
        const contador = document.getElementById("contador-likes");
        if (contador) contador.textContent = data.total_likes;
      } catch {
        // silencioso
      } finally {
        procesando = false;
      }
    });
  }

  // ================================================================
  // BOTÓN "AÑADIR A MIS LISTAS"
  // ================================================================
  const btnAdd = document.querySelector(".btn-add");

  if (btnAdd) {
    const addIcon = btnAdd.querySelector(".material-icons-outlined");
    const addText = btnAdd.querySelector("span:last-child");
    let isAdded = false;

    btnAdd.addEventListener("click", function (e) {
      e.preventDefault();
      isAdded = !isAdded;

      if (isAdded) {
        addIcon.textContent = "check_circle";
        addIcon.style.color = "#10b981";
        addText.textContent = "Añadida a tus listas";
        btnAdd.classList.add("added");
        btnAdd.style.transform = "scale(1.1)";
        setTimeout(() => { btnAdd.style.transform = "scale(1)"; }, 200);
      } else {
        addIcon.textContent = "add";
        addIcon.style.color = "#14b8a6";
        addText.textContent = "Añadir a mis listas";
        btnAdd.classList.remove("added");
      }
    });
  }

  // ================================================================
  // MENÚ HAMBURGUESA
  // ================================================================
  const hamburger = document.querySelector(".hamburger");
  const navCenter = document.querySelector(".nav-center");

  if (hamburger && navCenter) {
    hamburger.addEventListener("click", function () {
      navCenter.classList.toggle("active");
    });
  }

  // ================================================================
  // FUNCIONALIDAD DEL PROPIETARIO
  // ================================================================
  if (typeof ES_PROPIETARIO === "undefined" || !ES_PROPIETARIO) return;

  const modalEditar = document.getElementById("modalEditarLista");
  const btnAbrirEditar = document.getElementById("btnAbrirModalEditar");
  const modalEditarClose = document.getElementById("modalEditarClose");
  const editarCancelarBtn = document.getElementById("editarCancelarBtn");
  const editarGuardarBtn = document.getElementById("editarGuardarBtn");
  const mensajeEditar = document.getElementById("modal-editar-mensaje");
  const editarPortadaUpload = document.getElementById("editarPortadaUpload");
  const editarPortadaInput = document.getElementById("editarPortada");
  const editarPortadaPreviewImg = document.getElementById("editarPortadaPreviewImg");
  const editarPortadaPlaceholder = document.getElementById("editarPortadaPlaceholder");

  if (!modalEditar || !btnAbrirEditar) return;

  editarPortadaUpload.addEventListener("click", () => editarPortadaInput.click());

  editarPortadaInput.addEventListener("change", () => {
    const archivo = editarPortadaInput.files[0];
    if (!archivo) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      editarPortadaPreviewImg.src = e.target.result;
      editarPortadaPreviewImg.style.display = "block";
      editarPortadaPlaceholder.style.display = "none";
    };
    reader.readAsDataURL(archivo);
  });

  document.querySelectorAll(".editar-tab").forEach((tab) => {
    tab.addEventListener("click", () => {
      document.querySelectorAll(".editar-tab").forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");
      document.querySelectorAll(".editar-tab-panel").forEach((p) => (p.style.display = "none"));
      document.getElementById("tab-" + tab.dataset.tab).style.display = "block";
    });
  });

  function cerrarModalEditar() {
    modalEditar.style.display = "none";
  }

  btnAbrirEditar.addEventListener("click", () => {
    mensajeEditar.className = "alert d-none";
    modalEditar.style.display = "flex";
  });

  modalEditarClose.addEventListener("click", cerrarModalEditar);
  editarCancelarBtn.addEventListener("click", cerrarModalEditar);
  modalEditar.addEventListener("click", (e) => {
    if (e.target === modalEditar) cerrarModalEditar();
  });

  /* ── Guardar cambios ── */
  editarGuardarBtn.addEventListener("click", async () => {
    const titulo = document.getElementById("editarNombre").value.trim();

    if (!titulo) {
      mensajeEditar.className = "alert alert-danger";
      mensajeEditar.textContent = "El nombre de la lista es obligatorio.";
      return;
    }

    editarGuardarBtn.disabled = true;
    editarGuardarBtn.textContent = "Guardando...";

    try {
      const fd = new FormData();
      fd.append("id_lista", ID_LISTA);
      fd.append("accion", "editar");
      fd.append("titulo", titulo);
      fd.append("descripcion", document.getElementById("editarDescripcion").value.trim());
      const visibilidad =
        document.querySelector('input[name="editarVisibilidad"]:checked')?.value || "publica";
      fd.append("visibilidad", visibilidad);
      const archivo = editarPortadaInput.files[0];
      if (archivo) fd.append("imagen", archivo);

      const res = await fetch("ajax/acciones-lista.php", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();

      mensajeEditar.className = `alert alert-${data.ok ? "success" : "danger"}`;
      mensajeEditar.textContent = data.ok
        ? "¡Lista actualizada correctamente!"
        : data.mensaje;

      if (data.ok) setTimeout(() => location.reload(), 900);
    } catch {
      mensajeEditar.className = "alert alert-danger";
      mensajeEditar.textContent = "Error de conexión. Inténtalo de nuevo.";
    } finally {
      editarGuardarBtn.disabled = false;
      editarGuardarBtn.textContent = "Guardar cambios";
    }
  });

  /* ── Quitar película desde el modal ── */
  document.getElementById("editarPeliculasLista")?.addEventListener("click", async (e) => {
    const btn = e.target.closest(".btn-quitar-pelicula-modal");
    if (!btn) return;
    if (!confirm("¿Quitar esta película de la lista?")) return;

    const idPelicula = btn.dataset.idPelicula;
    const fd = new FormData();
    fd.append("id_lista", ID_LISTA);
    fd.append("id_pelicula", idPelicula);
    fd.append("accion", "quitar");

    try {
      const res = await fetch("ajax/gestionar-lista-pelicula.php", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: fd,
      });
      const data = await res.json();

      if (data.ok) {
        document.getElementById(`editar-item-${idPelicula}`)?.remove();
        document.getElementById(`card-pelicula-${idPelicula}`)?.remove();

        const cont = document.getElementById("contador-peliculas");
        const contTab = document.getElementById("tab-contador-peliculas");
        const newVal = Math.max(0, parseInt(cont?.textContent || "0") - 1);
        if (cont) cont.textContent = newVal;
        if (contTab) contTab.textContent = newVal;

        if (document.querySelectorAll("#editarPeliculasLista .editar-pelicula-item").length === 0) {
          const listaEl = document.getElementById("editarPeliculasLista");
          if (listaEl)
            listaEl.innerHTML =
              '<p style="color:var(--text-secondary);text-align:center;padding:24px 0;">Esta lista ya no tiene películas.</p>';
        }

        if (document.querySelectorAll(".movie-card-lista").length === 0) {
          document.getElementById("gridPeliculas").innerHTML = `
          <div id="msg-lista-vacia" style="padding:2rem 0;">
            <p style="color:white;margin-bottom:1rem;">Esta lista aún no tiene películas.</p>
            <a href="peliculas.php" class="btn-lista-action btn-editar-lista" style="display:inline-flex;text-decoration:none;">
              <span class="material-icons-outlined">movie</span>
              <span>Ir a Películas</span>
            </a>
          </div>`;
        }
      }
    } catch {
      /* silencioso */
    }
  });

  /* ── Eliminar lista entera ── */
  document.getElementById("btnEliminarLista")?.addEventListener("click", () => {
    if (!confirm("¿Seguro que quieres eliminar esta lista? Esta acción no se puede deshacer.")) return;

    const fd = new FormData();
    fd.append("id_lista", ID_LISTA);
    fd.append("accion", "eliminar");

    fetch("ajax/acciones-lista.php", {
      method: "POST",
      headers: { "X-Requested-With": "XMLHttpRequest" },
      body: fd,
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.ok) window.location.href = "mis-listas.php";
        else alert(data.mensaje || "No se pudo eliminar la lista.");
      })
      .catch(() => alert("Error de conexión al eliminar."));
  });

  function escHtml(str) {
    return str.replace(
      /[&<>"']/g,
      (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[m],
    );
  }
});