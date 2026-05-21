if (ES_MIEMBRO) {
  const chatMensajes = document.getElementById("chatMensajes");
  const chatInput = document.getElementById("chatInput");
  const chatEnviar = document.getElementById("chatEnviar");

  let ultimoIdMensaje = 0;
  let pollingTimer = null;

  // ── Helpers ────────────────────────────────────────────────
  function escapeHtml(str) {
    return String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function formatearFecha(fechaStr) {
    const fecha = new Date(fechaStr);
    const hoy = new Date();
    const esHoy = fecha.toDateString() === hoy.toDateString();
    const hora = fecha.toLocaleTimeString("es-ES", {
      hour: "2-digit",
      minute: "2-digit",
    });
    if (esHoy) return hora;
    return (
      fecha.toLocaleDateString("es-ES", { day: "2-digit", month: "2-digit" }) +
      " " +
      hora
    );
  }

  function crearBurbuja(msg) {
    const esMio = msg.id_usuario == ID_USUARIO;
    const div = document.createElement("div");
    div.classList.add("chat-burbuja", esMio ? "mia" : "suya");
    div.dataset.id = msg.id_mensaje;

    const avatar = msg.avatar
      ? `<img src="${escapeHtml(msg.avatar)}" alt="" class="burbuja-avatar">`
      : `<div class="burbuja-avatar burbuja-inicial" style="background:${colorUsuario(msg.username)}">${escapeHtml(msg.username[0].toUpperCase())}</div>`;

    const btnEditar = esMio
      ? `<button class="btn-editar-mensaje" data-id="${msg.id_mensaje}" title="Editar mensaje">
                <span class="material-icons-outlined">edit</span>
            </button>`
      : "";

    div.innerHTML = `
            ${!esMio ? avatar : ""}
            <div class="burbuja-contenido">
                ${!esMio ? `<span class="burbuja-usuario" style="color:${colorUsuario(msg.username)}">${escapeHtml(msg.username)}</span>` : ""}
                <p class="burbuja-texto">${escapeHtml(msg.mensaje)}</p>
                <div class="burbuja-footer">
                    <span class="burbuja-fecha">${formatearFecha(msg.fecha)}</span>
                    ${btnEditar}
                </div>
            </div>
            ${esMio ? avatar : ""}
        `;
    return div;
  }

  function scrollAbajo() {
    chatMensajes.scrollTop = chatMensajes.scrollHeight;
  }

  // ── Carga inicial ──────────────────────────────────────────
  async function cargarMensajes() {
    try {
      const res = await fetch(`api/mensajes-obtener.php?id_grupo=${ID_GRUPO}`);
      const data = await res.json();

      chatMensajes.innerHTML = "";

      if (!data.ok) return;

      if (!data.mensajes.length) {
        chatMensajes.innerHTML = `<p class="chat-vacio">Sé el primero en escribir algo 👋</p>`;
      } else {
        data.mensajes.forEach((msg) => {
          chatMensajes.appendChild(crearBurbuja(msg));
          ultimoIdMensaje = Math.max(ultimoIdMensaje, msg.id_mensaje);
        });
      }
      scrollAbajo();
    } catch (e) {
      chatMensajes.innerHTML = `<p class="chat-error">Error al cargar los mensajes.</p>`;
    }
  }

  // ── Polling ────────────────────────────────────────────────
  async function polling() {
    try {
      const res = await fetch(
        `api/mensajes-obtener.php?id_grupo=${ID_GRUPO}&desde_id=${ultimoIdMensaje}`,
      );
      const data = await res.json();

      if (data.ok && data.mensajes.length) {
        const estabaAbajo =
          chatMensajes.scrollHeight -
            chatMensajes.scrollTop -
            chatMensajes.clientHeight <
          100;

        data.mensajes.forEach((msg) => {
          chatMensajes.appendChild(crearBurbuja(msg));
          ultimoIdMensaje = Math.max(ultimoIdMensaje, msg.id_mensaje);
        });

        if (estabaAbajo) scrollAbajo();
      }
    } catch (e) {
      /* silencioso */
    }
  }

  // ── Enviar mensaje ─────────────────────────────────────────
  async function enviarMensaje() {
    const texto = chatInput.value.trim();
    if (!texto) return;

    chatInput.value = "";
    chatEnviar.disabled = true;

    try {
      const formData = new FormData();
      formData.append("id_grupo", ID_GRUPO);
      formData.append("mensaje", texto);

      const res = await fetch("api/mensajes-enviar.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();

      if (data.ok) {
        chatMensajes.appendChild(crearBurbuja(data.mensaje));
        ultimoIdMensaje = Math.max(ultimoIdMensaje, data.mensaje.id_mensaje);
        scrollAbajo();
      }
    } catch (e) {
      /* silencioso */
    }

    chatEnviar.disabled = false;
    chatInput.focus();
  }

  chatEnviar.addEventListener("click", enviarMensaje);
  chatInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      enviarMensaje();
    }
  });

  // ── Salir del grupo ────────────────────────────────────────
  document
    .getElementById("btnSalirGrupo")
    ?.addEventListener("click", async () => {
      if (!confirm("¿Seguro que quieres salir del grupo?")) return;

      const res = await fetch("api/grupo-unirse.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id_grupo=${ID_GRUPO}&accion=salir`,
      });
      const data = await res.json();
      if (data.ok) window.location.href = "comunidad.php";
    });

  // ── Unirse desde chat (no miembro) ─────────────────────────
  document
    .getElementById("btnUnirseDesdeChat")
    ?.addEventListener("click", async () => {
      const res = await fetch("api/grupo-unirse.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id_grupo=${ID_GRUPO}&accion=unirse`,
      });
      const data = await res.json();
      if (data.ok) window.location.reload();
    });

  // ── Arrancar ───────────────────────────────────────────────
  cargarMensajes().then(() => {
    pollingTimer = setInterval(polling, 3000);
  });
}

function colorUsuario(username) {
  let hash = 0;
  for (let i = 0; i < username.length; i++) {
    hash = username.charCodeAt(i) + ((hash << 5) - hash);
  }
  const colores = [
    "#d0c223",
    "#ff6347",
    "#a78bfa",
    "#f59e0b",
    "#34d399",
    "#60a5fa",
    "#f472b6",
    "#fb923c",
    "#4ade80",
    "#e879f9",
    "#38bdf8",
    "#facc15",
  ];
  return colores[Math.abs(hash) % colores.length];
}

// ── Modal Editar Grupo ─────────────────────────────────────────
const btnEditarGrupo = document.getElementById("btnEditarGrupo");
const modalEditar = document.getElementById("modalEditarGrupo");
const modalCloseEditar = document.getElementById("modalCloseEditar");
const cancelarEditarBtn = document.getElementById("cancelarEditarBtn");
const guardarEditarBtn = document.getElementById("guardarEditarBtn");
const mensajeEditarDiv = document.getElementById("modal-editar-mensaje");
const portadaEditUpload = document.getElementById("portadaEditUpload");
const portadaEditInput = document.getElementById("editPortada");
const portadaEditPreview = document.getElementById("portadaEditPreviewImg");
const portadaEditPlaceholder = document.getElementById(
  "portadaEditPlaceholder",
);

portadaEditUpload?.addEventListener("click", () => portadaEditInput.click());

portadaEditInput?.addEventListener("change", () => {
  const archivo = portadaEditInput.files[0];
  if (!archivo) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    portadaEditPreview.src = e.target.result;
    portadaEditPreview.style.display = "block";
    portadaEditPlaceholder.style.display = "none";
  };
  reader.readAsDataURL(archivo);
});

function abrirModalEditar() {
  mensajeEditarDiv.className = "alert d-none";
  mensajeEditarDiv.textContent = "";
  modalEditar.style.display = "flex";
}

function cerrarModalEditar() {
  modalEditar.style.display = "none";
}

btnEditarGrupo?.addEventListener("click", abrirModalEditar);
modalCloseEditar?.addEventListener("click", cerrarModalEditar);
cancelarEditarBtn?.addEventListener("click", cerrarModalEditar);
modalEditar?.addEventListener("click", (e) => {
  if (e.target === modalEditar) cerrarModalEditar();
});

guardarEditarBtn?.addEventListener("click", async () => {
  const nombre = document.getElementById("editNombre").value.trim();
  const descripcion = document.getElementById("editDescripcion").value.trim();
  const genero = document.getElementById("editGenero").value;
  const tipo = document.getElementById("editTipo").value;
  const archivo = portadaEditInput?.files[0] || null;

  if (!nombre) {
    mensajeEditarDiv.className = "alert alert-danger";
    mensajeEditarDiv.textContent = "El nombre es obligatorio.";
    return;
  }

  guardarEditarBtn.disabled = true;
  guardarEditarBtn.textContent = "Guardando...";

  try {
    const formData = new FormData();
    formData.append("id_grupo", ID_GRUPO);
    formData.append("nombre", nombre);
    formData.append("descripcion", descripcion);
    formData.append("id_genero", genero);
    formData.append("tipo", tipo);
    if (archivo) formData.append("imagen", archivo);

    const res = await fetch("api/editar-grupo.php", {
      method: "POST",
      body: formData,
    });
    const data = await res.json();

    if (data.ok) {
      mensajeEditarDiv.className = "alert alert-success";
      mensajeEditarDiv.textContent = "¡Cambios guardados!";
      setTimeout(() => window.location.reload(), 1200);
    } else {
      mensajeEditarDiv.className = "alert alert-danger";
      mensajeEditarDiv.textContent = data.msg;
    }
  } catch (e) {
    mensajeEditarDiv.className = "alert alert-danger";
    mensajeEditarDiv.textContent = "Error de conexión.";
  } finally {
    guardarEditarBtn.disabled = false;
    guardarEditarBtn.textContent = "Guardar cambios";
  }
});

// ── Lógica del modal editar mensaje ─────────────────────────────────────────────
const modalEditarMensaje = document.getElementById("modalEditarMensaje");
const cerrarModalMensaje = document.getElementById("cerrarModalMensaje");
const cancelarEditarMensaje = document.getElementById("cancelarEditarMensaje");
const guardarEditarMensaje = document.getElementById("guardarEditarMensaje");
const editarMensajeTexto = document.getElementById("editarMensajeTexto");
const avisoEditar = document.getElementById("modal-mensaje-editar-aviso");

let idMensajeEditando = null;

function abrirModalEditarMensaje(idMensaje, textoActual) {
  idMensajeEditando = idMensaje;
  editarMensajeTexto.value = textoActual;
  avisoEditar.className = "alert d-none";
  modalEditarMensaje.style.display = "flex";
  editarMensajeTexto.focus();
}

function cerrarModal() {
  modalEditarMensaje.style.display = "none";
  idMensajeEditando = null;
}

cerrarModalMensaje?.addEventListener("click", cerrarModal);
cancelarEditarMensaje?.addEventListener("click", cerrarModal);
modalEditarMensaje?.addEventListener("click", (e) => {
  if (e.target === modalEditarMensaje) cerrarModal();
});

// Detectar click en botón editar dentro del chat (delegación)
chatMensajes.addEventListener("click", (e) => {
  const btn = e.target.closest(".btn-editar-mensaje");
  if (!btn) return;
  const burbuja = btn.closest(".chat-burbuja");
  const textoActual = burbuja.querySelector(".burbuja-texto").textContent;
  abrirModalEditarMensaje(btn.dataset.id, textoActual);
});

guardarEditarMensaje?.addEventListener("click", async () => {
  const texto = editarMensajeTexto.value.trim();
  if (!texto) {
    avisoEditar.className = "alert alert-danger";
    avisoEditar.textContent = "El mensaje no puede estar vacío.";
    return;
  }

  guardarEditarMensaje.disabled = true;
  guardarEditarMensaje.textContent = "Guardando...";

  try {
    const formData = new FormData();
    formData.append("id_mensaje", idMensajeEditando);
    formData.append("mensaje", texto);

    const res = await fetch("api/mensajes-editar.php", {
      method: "POST",
      body: formData,
    });
    const data = await res.json();

    if (data.ok) {
      // Actualizar el texto en el DOM sin recargar
      const burbuja = chatMensajes.querySelector(
        `.chat-burbuja[data-id="${idMensajeEditando}"]`,
      );
      if (burbuja) {
        burbuja.querySelector(".burbuja-texto").textContent = texto;
      }
      cerrarModal();
    } else {
      avisoEditar.className = "alert alert-danger";
      avisoEditar.textContent = data.msg;
    }
  } catch (e) {
    avisoEditar.className = "alert alert-danger";
    avisoEditar.textContent = "Error de conexión.";
  } finally {
    guardarEditarMensaje.disabled = false;
    guardarEditarMensaje.textContent = "Guardar";
  }
});

// ── Expulsar miembro (solo creador del grupo) ──────────────────
document.querySelectorAll(".btn-expulsar-miembro").forEach((btn) => {
  btn.addEventListener("click", async () => {
    const idExpulsado = btn.dataset.id;
    const nombre = btn.dataset.nombre;

    if (
      !confirm(
        `¿Expulsar a ${nombre} del grupo? No podrá volver a unirse hasta que alguien le invite.`,
      )
    )
      return;

    btn.disabled = true;

    try {
      const formData = new FormData();
      formData.append("id_grupo", ID_GRUPO);
      formData.append("id_usuario", idExpulsado);

      const res = await fetch("api/expulsar-miembro.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();

      if (data.ok) {
        // Eliminar el li del miembro del sidebar sin recargar
        btn.closest("li").remove();
      } else {
        alert(data.msg || "No se pudo expulsar al miembro.");
        btn.disabled = false;
      }
    } catch (e) {
      alert("Error de conexión.");
      btn.disabled = false;
    }
  });
});
