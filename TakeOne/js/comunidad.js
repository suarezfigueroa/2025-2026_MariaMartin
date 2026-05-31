// ── Toggle tabs ────────────────────────────────────────────────
document.querySelectorAll(".tab-btn").forEach((tab) => {
  tab.addEventListener("click", function () {
    document
      .querySelectorAll(".tab-btn")
      .forEach((t) => t.classList.remove("active"));
    this.classList.add("active");
  });
});

// ── Unirse/salir de grupo ──────────────────────────────────────
async function handleJoinBtn(e) {
  e.stopPropagation();
  const btn = e.currentTarget;
  const idGrupo = btn.dataset.id;
  const unido = btn.classList.contains("joined");
  const accion = unido ? "salir" : "unirse";

  const res = await fetch("ajax/grupo-unirse.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: `id_grupo=${idGrupo}&accion=${accion}`,
  });
  const data = await res.json();

  if (data.ok) {
    if (accion === "unirse") {
      btn.textContent = "Unido al grupo";
      btn.classList.add("joined");
    } else {
      btn.textContent = "Unirse al grupo";
      btn.classList.remove("joined");
    }
    const meta = btn.closest(".group-card").querySelector(".group-meta span");
    meta.textContent = `👥 ${data.miembros.toLocaleString("es-ES")} miembros`;
  }
}

document.querySelectorAll(".join-btn").forEach((btn) => {
  btn.addEventListener("click", handleJoinBtn);
});

// ── Buscador en tiempo real ────────────────────────────────────
const inputBusqueda = document.getElementById("buscadorGrupos");
const groupsContainer = document.querySelector(".groups-container");
const groupsHeader = document.querySelector(".groups-header h2");

let debounceTimer = null;
const tarjetasOriginales = groupsContainer.innerHTML;
const tituloOriginal = groupsHeader.textContent;

function escapeHtml(str) {
  return String(str ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function renderGrupos(grupos) {
  if (!grupos.length) {
    groupsContainer.innerHTML = `<p class="text-muted px-3">No se encontraron grupos.</p>`;
    return;
  }
  groupsContainer.innerHTML = grupos
    .map(
      (g) => `
        <div class="group-card" data-id="${g.id_grupo}">
            <img src="${escapeHtml(g.imagen ?? "img/logo gato sin fondo.png")}"
                 alt="${escapeHtml(g.nombre)}" class="group-image">
            <div class="group-info">
                <h3 class="group-name">${escapeHtml(g.nombre)}</h3>
                <p class="group-description">${escapeHtml(g.descripcion)}</p>
                <div class="group-meta">
                    <span>👥 ${Number(g.num_miembros).toLocaleString("es-ES")} miembros</span>
                    ${g.nombre_genero ? `<span class="group-tag">${escapeHtml(g.nombre_genero)}</span>` : ""}
                </div>
                ${g.creador_username ? `<span class="group-creador">Creado por: ${escapeHtml(g.creador_username)}</span>` : ""}
                <button class="join-btn ${g.unido == 1 ? "joined" : ""}" data-id="${g.id_grupo}">
                    ${g.unido == 1 ? "Unido al grupo" : "Unirse al grupo"}
                </button>
            </div>
        </div>
    `,
    )
    .join("");

  groupsContainer.querySelectorAll(".join-btn").forEach((btn) => {
    btn.addEventListener("click", handleJoinBtn);
  });
}

function asignarClickCards() {
  document.querySelectorAll(".group-card").forEach((card) => {
    card.addEventListener("click", (e) => {
      if (e.target.closest(".join-btn")) return;
      const id = card.dataset.id;
      const btn = card.querySelector(".join-btn");
      if (btn && btn.classList.contains("joined")) {
        window.location.href = `grupo-mensajes.php?id=${id}`;
      }
    });
  });
}

asignarClickCards();

async function buscarGrupos(q) {
  try {
    const res = await fetch(`ajax/buscar-grupos.php?q=${encodeURIComponent(q)}`, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    });
    const data = await res.json();
    groupsHeader.textContent = `${data.length} resultado${data.length !== 1 ? "s" : ""} para "${q}"`;
    renderGrupos(data);
  } catch (e) {
    groupsContainer.innerHTML = `<p class="text-muted px-3">Error al buscar. Inténtalo de nuevo.</p>`;
  }
}

inputBusqueda?.addEventListener("input", () => {
  const q = inputBusqueda.value.trim();
  clearTimeout(debounceTimer);

  if (q.length < 2) {
    groupsContainer.innerHTML = tarjetasOriginales;
    groupsHeader.textContent = tituloOriginal;
    groupsContainer.querySelectorAll(".join-btn").forEach((btn) => {
      btn.addEventListener("click", handleJoinBtn);
    });
    return;
  }

  debounceTimer = setTimeout(() => buscarGrupos(q), 300);
});

// ── Modal Crear Grupo ──────────────────────────────────────────
const btnCrearGrupo = document.getElementById("btnCrearGrupo");
const modalGrupo = document.getElementById("modalCrearGrupo");
const modalCloseGrupo = document.getElementById("modalCloseGrupo");
const cancelarGrupoBtn = document.getElementById("cancelarGrupoBtn");
const guardarGrupoBtn = document.getElementById("guardarGrupoBtn");
const mensajeGrupoDiv = document.getElementById("modal-grupo-mensaje");
const portadaGrupoUpload = document.getElementById("portadaGrupoUpload");
const portadaGrupoInput = document.getElementById("grupoPortada");
const portadaGrupoPreview = document.getElementById("portadaGrupoPreviewImg");
const portadaGrupoPlaceholder = document.getElementById(
  "portadaGrupoPlaceholder",
);

portadaGrupoUpload?.addEventListener("click", () => portadaGrupoInput.click());

portadaGrupoInput?.addEventListener("change", () => {
  const archivo = portadaGrupoInput.files[0];
  if (!archivo) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    portadaGrupoPreview.src = e.target.result;
    portadaGrupoPreview.style.display = "block";
    portadaGrupoPlaceholder.style.display = "none";
  };
  reader.readAsDataURL(archivo);
});

function abrirModalGrupo() {
  limpiarModalGrupo();
  modalGrupo.style.display = "flex";
}

function cerrarModalGrupo() {
  modalGrupo.style.display = "none";
  limpiarModalGrupo();
}

function limpiarModalGrupo() {
  document.getElementById("grupoNombre").value = "";
  document.getElementById("grupoDescripcion").value = "";
  document.getElementById("grupoGenero").value = "";
  document.getElementById("grupoTipo").value = "";
  portadaGrupoInput.value = "";
  portadaGrupoPreview.src = "";
  portadaGrupoPreview.style.display = "none";
  portadaGrupoPlaceholder.style.display = "flex";
  mensajeGrupoDiv.className = "alert d-none";
  mensajeGrupoDiv.textContent = "";
}

function mostrarMensajeGrupo(texto, tipo) {
  mensajeGrupoDiv.className = `alert alert-${tipo}`;
  mensajeGrupoDiv.textContent = texto;
}

btnCrearGrupo?.addEventListener("click", abrirModalGrupo);
modalCloseGrupo?.addEventListener("click", cerrarModalGrupo);
cancelarGrupoBtn?.addEventListener("click", cerrarModalGrupo);
modalGrupo?.addEventListener("click", (e) => {
  if (e.target === modalGrupo) cerrarModalGrupo();
});

guardarGrupoBtn?.addEventListener("click", async () => {
  const nombre = document.getElementById("grupoNombre").value.trim();
  const descripcion = document.getElementById("grupoDescripcion").value.trim();
  const genero = document.getElementById("grupoGenero").value;
  const tipo = document.getElementById("grupoTipo").value;
  const archivo = portadaGrupoInput.files[0] || null;

  if (!nombre) {
    mostrarMensajeGrupo("El nombre del grupo es obligatorio.", "danger");
    return;
  }
  if (!tipo) {
    mostrarMensajeGrupo("Debes seleccionar un tipo de actividad.", "danger");
    return;
  }

  guardarGrupoBtn.disabled = true;
  guardarGrupoBtn.textContent = "Creando...";

  try {
    const formData = new FormData();
    formData.append("nombre", nombre);
    formData.append("descripcion", descripcion);
    formData.append("id_genero", genero);
    formData.append("tipo", tipo);
    if (archivo) formData.append("imagen", archivo);

    const res = await fetch("ajax/crear-grupo.php", {
      method: "POST",
      headers: { "X-Requested-With": "XMLHttpRequest" },
      body: formData,
    });
    const data = await res.json();

    if (data.ok) {
      mostrarMensajeGrupo("¡Grupo creado correctamente!", "success");
      setTimeout(() => {
        cerrarModalGrupo();
        window.location.reload();
      }, 1200);
    } else {
      mostrarMensajeGrupo(data.mensaje, "danger");
    }
  } catch (e) {
    mostrarMensajeGrupo("Error de conexión. Inténtalo de nuevo.", "danger");
  } finally {
    guardarGrupoBtn.disabled = false;
    guardarGrupoBtn.textContent = "Crear grupo";
  }
});