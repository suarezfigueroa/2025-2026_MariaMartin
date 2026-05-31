/* ====================== SISTEMA DE NOTIFICACIONES TOAST ====================== */
function showToast(message, type = "success") {
  const toast = document.createElement("div");
  toast.className = `toast-notification toast-${type}`;

  const icon = type === "success" ? "check_circle" : "info";

  toast.innerHTML = `
    <span class="material-icons-outlined toast-icon">${icon}</span>
    <span class="toast-message">${message}</span>
  `;

  document.body.appendChild(toast);

  setTimeout(() => toast.classList.add("show"), 10);
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

/* =================== TODA LA LÓGICA DE LA PÁGINA =================== */
document.addEventListener("DOMContentLoaded", function () {
  /* =================== DROPDOWN "MARCAR COMO" =================== */
  const dropdownMarcar = document.querySelector(".dropdown-marcar");
  const dropdownToggle = dropdownMarcar?.querySelector(".dropdown-toggle");
  const dropdownMenu = dropdownMarcar?.querySelector(".dropdown-menu-marcar");

  if (dropdownMarcar && dropdownToggle && dropdownMenu) {
    const tituloPelicula = dropdownMarcar.dataset.titulo || "";

    const config = {
      vista: {
        icon: "check_circle",
        label: "Vista",
        toast: `Has marcado ${tituloPelicula} como vista`,
      },
      favorita: {
        icon: "favorite",
        label: "Favorita",
        toast: `Has añadido ${tituloPelicula} a favoritas`,
      },
      pendiente: {
        icon: "schedule",
        label: "Pendiente",
        toast: `Has añadido ${tituloPelicula} a pendientes`,
      },
    };

    const btnIcon = dropdownToggle.querySelector(".material-icons-outlined");
    const btnLabel = dropdownToggle.querySelector("span:last-child");
    let estadoActual = dropdownMarcar.dataset.estado || "";

    function actualizarBoton(estado) {
      if (estado && config[estado]) {
        btnIcon.textContent = config[estado].icon;
        btnLabel.textContent = config[estado].label;
        dropdownToggle.classList.add("estado-activo");
      } else {
        btnIcon.textContent = "bookmark_border";
        btnLabel.textContent = "Marcar como";
        dropdownToggle.classList.remove("estado-activo");
      }

      dropdownMenu.querySelectorAll(".dropdown-item-marcar").forEach((item) => {
        item.classList.toggle("activo", item.dataset.status === estado);
      });
    }

    dropdownToggle.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const isOpen = dropdownMenu.classList.contains("show");
      dropdownMenu.classList.toggle("show", !isOpen);
      dropdownMarcar.classList.toggle("active", !isOpen);
    });

    document.addEventListener("click", function (e) {
      if (!dropdownMarcar.contains(e.target)) {
        dropdownMenu.classList.remove("show");
        dropdownMarcar.classList.remove("active");
      }
    });

    dropdownMenu.querySelectorAll(".dropdown-item-marcar").forEach((item) => {
      item.addEventListener("click", async function (e) {
        e.preventDefault();

        const status = this.dataset.status;
        const idPelicula = dropdownMarcar.dataset.peliculaId;

        // si pulsa el mismo estado otra vez, se desmarca
        const estadoFinal = estadoActual === status ? "" : status;

        dropdownMenu.classList.remove("show");
        dropdownMarcar.classList.remove("active");

        try {
          const fd = new FormData();
          fd.append("id_pelicula", idPelicula);
          fd.append("estado", estadoFinal);

          const res = await fetch("ajax/actualizar-estado-pelicula.php", {
            method: "POST",
            body: fd,
          });

          const data = await res.json();

          if (res.ok && data.ok) {
            estadoActual = data.estado || "";
            dropdownMarcar.dataset.estado = estadoActual;
            actualizarBoton(estadoActual);

            if (estadoActual && config[estadoActual]) {
              showToast(config[estadoActual].toast, "success");
            } else {
              showToast("Estado eliminado", "info");
            }
          } else if (res.status === 401) {
            window.location.href = "login.php";
          } else {
            showToast(data.message || "Error al guardar el estado", "info");
          }
        } catch {
          showToast("Error al guardar el estado", "info");
        }
      });
    });

    actualizarBoton(estadoActual);
  }

  /* ===================== SISTEMA DE RATING CON CORAZONES ====================== */
  const ratingWidget = document.querySelector(".rating-widget");

  if (ratingWidget) {
    const hearts = ratingWidget.querySelectorAll(".heart");
    const idPelicula = ratingWidget.dataset.peliculaId;
    let valorActual = parseInt(ratingWidget.dataset.valoracion, 10) || 0;

    function pintarHasta(n) {
      hearts.forEach((h, i) => {
        if (i < n) {
          h.classList.add("filled");
          h.textContent = "favorite";
        } else {
          h.classList.remove("filled");
          h.textContent = "favorite_border";
        }
      });
    }

    function guardarValoracion(valor) {
      hearts.forEach((h) => (h.style.pointerEvents = "none"));

      const fd = new FormData();
      fd.append("id_pelicula", idPelicula);
      fd.append("valoracion", valor);

      fetch("ajax/valoracion.php", { method: "POST", body: fd })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            valorActual = data.valoracion;
            ratingWidget.dataset.valoracion = valorActual;
            pintarHasta(valorActual);
            const tituloPeliculaRating =
              document.querySelector(".dropdown-marcar")?.dataset.titulo || "";
            showToast(
              `Has valorado ${tituloPeliculaRating} con ${valorActual} ${valorActual === 1 ? "corazón" : "corazones"}`,
              "success",
            );
          } else if (data.error === "no_sesion") {
            window.location.href = "login.php";
          } else {
            pintarHasta(valorActual);
            showToast("Error al guardar la valoración", "info");
          }
        })
        .catch(() => {
          pintarHasta(valorActual);
          showToast("Error al guardar la valoración", "info");
        })
        .finally(() => {
          hearts.forEach((h) => (h.style.pointerEvents = ""));
        });
    }

    function borrarValoracion() {
      hearts.forEach((h) => (h.style.pointerEvents = "none"));

      const fd = new FormData();
      fd.append("id_pelicula", idPelicula);

      fetch("ajax/valoracion.php", { method: "POST", body: fd })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            valorActual = 0;
            ratingWidget.dataset.valoracion = 0;
            pintarHasta(0);
            showToast("Valoración eliminada", "info");
          } else if (data.error === "no_sesion") {
            window.location.href = "login.php";
          } else {
            pintarHasta(valorActual);
            showToast("Error al eliminar la valoración", "info");
          }
        })
        .catch(() => {
          pintarHasta(valorActual);
          showToast("Error al eliminar la valoración", "info");
        })
        .finally(() => {
          hearts.forEach((h) => (h.style.pointerEvents = ""));
        });
    }

    hearts.forEach((heart, index) => {
      heart.addEventListener("mouseenter", () => pintarHasta(index + 1));

      heart.addEventListener("click", () => {
        const clicado = index + 1;
        if (clicado === valorActual) {
          borrarValoracion();
        } else {
          guardarValoracion(clicado);
        }
      });
    });

    ratingWidget.addEventListener("mouseleave", () => pintarHasta(valorActual));
    pintarHasta(valorActual);
  }

  /* ===================== MODAL "AÑADIR A LISTA" ====================== */
  const abrirModalListaBtn = document.getElementById("abrirModalListaBtn");
  const modalAgregarListaOverlay = document.getElementById(
    "modalAgregarListaOverlay",
  );
  const cerrarModalListaBtn = document.getElementById("cerrarModalListaBtn");
  const cancelarAgregarListaBtn = document.getElementById(
    "cancelarAgregarListaBtn",
  );
  const guardarEnListaBtn = document.getElementById("guardarEnListaBtn");
  const selectLista = document.getElementById("selectLista");
  const mensajeAgregarLista = document.getElementById("mensajeAgregarLista");

  if (abrirModalListaBtn && modalAgregarListaOverlay) {
    function abrirModalLista() {
      modalAgregarListaOverlay.style.display = "flex";
      limpiarMensaje();
    }

    function cerrarModalLista() {
      modalAgregarListaOverlay.style.display = "none";
      limpiarMensaje();
      if (selectLista) selectLista.value = "";
    }

    function mostrarMensaje(texto, tipo = "success") {
      if (!mensajeAgregarLista) return;
      mensajeAgregarLista.className = `alert alert-${tipo} mb-3`;
      mensajeAgregarLista.textContent = texto;
    }

    function limpiarMensaje() {
      if (!mensajeAgregarLista) return;
      mensajeAgregarLista.className = "alert d-none mb-3";
      mensajeAgregarLista.textContent = "";
    }

    abrirModalListaBtn.addEventListener("click", (e) => {
      e.preventDefault();
      abrirModalLista();
    });

    cerrarModalListaBtn?.addEventListener("click", cerrarModalLista);
    cancelarAgregarListaBtn?.addEventListener("click", cerrarModalLista);

    modalAgregarListaOverlay.addEventListener("click", (e) => {
      if (e.target === modalAgregarListaOverlay) cerrarModalLista();
    });

    guardarEnListaBtn?.addEventListener("click", async function () {
      const idLista = selectLista?.value || "";
      const idPelicula = this.dataset.peliculaId;

      if (!idLista) {
        mostrarMensaje("Selecciona una lista.", "danger");
        return;
      }

      this.disabled = true;
      this.textContent = "Guardando...";

      try {
        const fd = new FormData();
        fd.append("id_lista", idLista);
        fd.append("id_pelicula", idPelicula);

        const res = await fetch("ajax/guardar-pelicula-lista.php", {
          method: "POST",
          body: fd,
        });

        const data = await res.json();

        if (data.ok) {
          mostrarMensaje(data.mensaje, "success");
          const nombreLista =
            selectLista?.options[selectLista.selectedIndex]?.text || "";
          const tituloPeliculaLista =
            document.querySelector(".dropdown-marcar")?.dataset.titulo || "";
          showToast(
            `Has añadido ${tituloPeliculaLista} a la lista ${nombreLista}`,
            "success",
          );
          setTimeout(cerrarModalLista, 900);
        } else {
          mostrarMensaje(
            data.mensaje || "No se pudo guardar la película en la lista.",
            "danger",
          );
        }
      } catch {
        mostrarMensaje("Error de conexión. Inténtalo de nuevo.", "danger");
      } finally {
        this.disabled = false;
        this.textContent = "Guardar en la lista";
      }
    });
  }

  /* ===================== EDITAR Y ELIMINAR COMENTARIOS ===================== */

  document.querySelectorAll(".btn-edit-comment").forEach((btn) => {
    btn.addEventListener("click", function () {
      const card = this.closest(".comment-card");
      const spoilerWrapper = card.querySelector(".spoiler-wrapper");
      const body = spoilerWrapper
        ? card.querySelector(".spoiler-body")
        : card.querySelector(".comment-body");
      const editForm = card.querySelector(".comment-edit-form");
      const input = editForm?.querySelector(".edit-input");

      if (!editForm) return;

      if (editForm.style.display !== "none") {
        editForm.style.display = "none";
        if (spoilerWrapper) {
          spoilerWrapper.style.display = "";
        } else {
          body.style.display = "";
        }
        return;
      }

      if (spoilerWrapper) {
        spoilerWrapper.style.display = "none";
      } else {
        body.style.display = "none";
      }
      editForm.style.display = "block";
      input?.focus();

      if (input) {
        const len = input.value.length;
        input.setSelectionRange(len, len);
      }
    });
  });

  document.querySelectorAll(".btn-cancel-edit").forEach((btn) => {
    btn.addEventListener("click", function () {
      const card = this.closest(".comment-card");
      const spoilerWrapper = card.querySelector(".spoiler-wrapper");
      card.querySelector(".comment-edit-form").style.display = "none";
      if (spoilerWrapper) {
        spoilerWrapper.style.display = "";
      } else {
        card.querySelector(".comment-body").style.display = "";
      }
    });
  });

  document.querySelectorAll(".btn-confirm-edit").forEach((btn) => {
    btn.addEventListener("click", async function () {
      const card = this.closest(".comment-card");
      const idComentario = this.dataset.id;
      const input = card.querySelector(".edit-input");
      const nuevoTexto = input?.value.trim();

      if (!nuevoTexto) {
        showToast("El comentario no puede estar vacío.", "info");
        return;
      }

      this.disabled = true;

      try {
        const fd = new FormData();
        fd.append("accion", "editar");
        fd.append("id_comentario", idComentario);
        fd.append("comentario", nuevoTexto);

        const res = await fetch("ajax/comentarios-acciones.php", {
          method: "POST",
          body: fd,
        });

        const data = await res.json();

        if (data.success) {
          const spoilerWrapper = card.querySelector(".spoiler-wrapper");
          const editForm = card.querySelector(".comment-edit-form");

          if (spoilerWrapper) {
            // Es un comentario spoiler: actualizar texto y resetear el blur
            const spoilerBody = card.querySelector(".spoiler-body");
            spoilerBody.textContent = nuevoTexto;
            spoilerWrapper.classList.remove("revealed");
            spoilerWrapper.style.display = "";
          } else {
            const body = card.querySelector(".comment-body");
            body.textContent = nuevoTexto;
            body.style.display = "";
          }

          editForm.style.display = "none";
          card.querySelector(".btn-edit-comment").dataset.texto = nuevoTexto;
          showToast("Comentario actualizado.", "success");
        } else if (data.error === "no_sesion") {
          window.location.href = "login.php";
        } else {
          showToast(data.mensaje || "Error al guardar el comentario.", "info");
        }
      } catch {
        showToast("Error de conexión.", "info");
      } finally {
        this.disabled = false;
      }
    });
  });

  let comentarioAEliminar = null;
  const modalBorrar = document.getElementById("modalConfirmarBorrarComentario");
  const btnConfirmarBorrar = document.getElementById(
    "confirmarBorrarComentarioBtn",
  );
  const btnCancelarBorrar = document.getElementById(
    "cancelarBorrarComentarioBtn",
  );

  document.querySelectorAll(".btn-delete-comment").forEach((btn) => {
    btn.addEventListener("click", function () {
      comentarioAEliminar = this.dataset.id;
      if (modalBorrar) modalBorrar.style.display = "flex";
    });
  });

  btnCancelarBorrar?.addEventListener("click", () => {
    comentarioAEliminar = null;
    if (modalBorrar) modalBorrar.style.display = "none";
  });

  modalBorrar?.addEventListener("click", (e) => {
    if (e.target === modalBorrar) {
      comentarioAEliminar = null;
      modalBorrar.style.display = "none";
    }
  });

  btnConfirmarBorrar?.addEventListener("click", async function () {
    if (!comentarioAEliminar) return;

    this.disabled = true;
    this.textContent = "Eliminando...";

    try {
      const fd = new FormData();
      fd.append("accion", "eliminar");
      fd.append("id_comentario", comentarioAEliminar);

      const res = await fetch("ajax/comentarios-acciones.php", {
        method: "POST",
        body: fd,
      });

      const data = await res.json();

      if (data.success) {
        const card = document.querySelector(
          `.comment-card[data-comment-id="${comentarioAEliminar}"]`,
        );

        if (card) {
          card.style.transition = "opacity 0.3s, transform 0.3s";
          card.style.opacity = "0";
          card.style.transform = "translateX(-10px)";
          setTimeout(() => card.remove(), 300);
        }

        if (modalBorrar) modalBorrar.style.display = "none";
        showToast("Comentario eliminado.", "info");
      } else if (data.error === "no_sesion") {
        window.location.href = "login.php";
      } else {
        showToast(data.mensaje || "Error al eliminar el comentario.", "info");
      }
    } catch {
      showToast("Error de conexión.", "info");
    } finally {
      this.disabled = false;
      this.textContent = "Eliminar";
      comentarioAEliminar = null;
    }
  });

  /* ===================== ENVÍO DE COMENTARIO SIN RECARGA ===================== */
  const formComentario = document.getElementById("formComentario");

  if (formComentario) {
    formComentario.addEventListener("submit", async function (e) {
      e.preventDefault();
      e.stopPropagation();

      const idPelicula = this.dataset.peliculaId;
      const input = this.querySelector(".comment-input-new");
      const checkSpoiler = this.querySelector('input[name="es_spoiler"]');
      const btnEnviar = this.querySelector('button[type="submit"]');
      const texto = input.value.trim();

      if (!texto) return;

      btnEnviar.disabled = true;

      try {
        const fd = new FormData();
        fd.append("id_pelicula", idPelicula);
        fd.append("comentario", texto);
        if (checkSpoiler?.checked) fd.append("es_spoiler", "1");

        const res = await fetch("ajax/guardar-comentario.php", {
          method: "POST",
          body: fd,
        });

        if (res.status === 401) {
          window.location.href = "login.php";
          return;
        }

        // Leer la respuesta como texto primero para detectar errores PHP
        const rawText = await res.text();
        let data;
        try {
          data = JSON.parse(rawText);
        } catch {
          console.error("Respuesta no-JSON del servidor:", rawText);
          showToast("Error del servidor. Revisa la consola.", "info");
          return;
        }

        if (data.ok) {
          const c = data.comentario;

          // Corazones de valoración
          const heartsHTML = c.valoracion
            ? Array.from(
                { length: 5 },
                (_, i) =>
                  `<span class="material-icons-outlined comment-heart ${i < c.valoracion ? "filled" : ""}"
                       style="font-size:0.9rem;">
                   ${i < c.valoracion ? "favorite" : "favorite_border"}
                 </span>`,
              ).join("")
            : "";

          // Contenido del comentario (spoiler o normal)
          const contenidoComentario = c.es_spoiler
            ? `<div class="spoiler-wrapper">
                 <div class="spoiler-blur" onclick="this.parentElement.classList.add('revealed')">
                   <p class="comment-body spoiler-body">${c.texto}</p>
                   <div class="spoiler-overlay">
                     <button class="spoiler-btn">
                       <span class="material-icons-outlined">visibility_off</span>
                       <span>Spoiler · clic para leer</span>
                     </button>
                   </div>
                 </div>
               </div>`
            : `<p class="comment-body">${c.texto}</p>`;

          // Avatar igual que en PHP: img si hay avatar, div con inicial si no
          const avatarHTML = c.avatar
            ? `<img src="${c.avatar}"
                    alt="${c.username}"
                    class="avatar-comentario comment-avatar-new"
                    onerror="this.src='img/avatar-placeholder.jpg'">`
            : `<div class="comment-avatar-new"
                    style="background:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;">
                 ${c.username.charAt(0).toUpperCase()}
               </div>`;

          const nuevaTarjeta = document.createElement("div");
          nuevaTarjeta.className = "comment-card";
          nuevaTarjeta.dataset.commentId = c.id;
          nuevaTarjeta.style.cssText =
            "opacity:0;transform:translateY(-8px);transition:opacity 0.3s,transform 0.3s;";
          nuevaTarjeta.innerHTML = `
            <div class="comment-header-new">
              ${avatarHTML}
              <div class="comment-author-info">
                <div class="author-name">${c.username}</div>
                <div class="comment-date">Ahora</div>
              </div>
              <div class="comment-actions">
                <button class="comment-action-btn btn-edit-comment"
                        data-id="${c.id}"
                        data-texto="${c.texto.replace(/"/g, "&quot;")}"
                        title="Editar comentario">
                  <span class="material-icons-outlined">edit</span>
                </button>
                <button class="comment-action-btn btn-delete-comment"
                        data-id="${c.id}"
                        title="Eliminar comentario">
                  <span class="material-icons-outlined">delete</span>
                </button>
              </div>
            </div>
            ${heartsHTML ? `<div class="comment-rating">${heartsHTML}</div>` : ""}
            ${contenidoComentario}
            <div class="comment-edit-form" style="display:none;">
              <div class="comment-form-new">
                <input type="text" class="comment-input-new edit-input"
                       value="${c.texto.replace(/"/g, "&quot;")}" maxlength="500">
                <button type="button" class="btn-send btn-confirm-edit" data-id="${c.id}">
                  <span class="material-icons-outlined">check</span>
                </button>
                <button type="button" class="btn-send btn-cancel-edit"
                        style="background:rgba(255,255,255,0.1);">
                  <span class="material-icons-outlined">close</span>
                </button>
              </div>
            </div>
          `;

          // Insertar al inicio de la lista existente, o crear la lista si es el primer comentario
          const lista = document.querySelector(".comments-new");
          if (lista) {
            lista.prepend(nuevaTarjeta);
          } else {
            const seccion = formComentario.closest(".glass-card");
            const placeholder = seccion?.querySelector("p");
            if (placeholder) placeholder.remove();
            const nuevaLista = document.createElement("div");
            nuevaLista.className = "comments-new";
            nuevaLista.appendChild(nuevaTarjeta);
            formComentario.before(nuevaLista);
          }

          // Animar entrada
          requestAnimationFrame(() => {
            nuevaTarjeta.style.opacity = "1";
            nuevaTarjeta.style.transform = "translateY(0)";
          });

          // Scroll suave al nuevo comentario sin saltar al principio
          nuevaTarjeta.scrollIntoView({ behavior: "smooth", block: "nearest" });

          // Activar editar/borrar en la nueva tarjeta
          nuevaTarjeta
            .querySelector(".btn-edit-comment")
            ?.addEventListener("click", function () {
              const card = this.closest(".comment-card");
              const editForm = card.querySelector(".comment-edit-form");
              const body = card.querySelector(
                ".comment-body, .spoiler-wrapper",
              );
              if (!editForm) return;
              const abierto = editForm.style.display !== "none";
              editForm.style.display = abierto ? "none" : "block";
              if (body) body.style.display = abierto ? "" : "none";
              if (!abierto) editForm.querySelector(".edit-input")?.focus();
            });

          nuevaTarjeta
            .querySelector(".btn-cancel-edit")
            ?.addEventListener("click", function () {
              const card = this.closest(".comment-card");
              card.querySelector(".comment-edit-form").style.display = "none";
              const body = card.querySelector(
                ".comment-body, .spoiler-wrapper",
              );
              if (body) body.style.display = "";
            });

          nuevaTarjeta
            .querySelector(".btn-confirm-edit")
            ?.addEventListener("click", async function () {
              const card = this.closest(".comment-card");
              const idComentario = this.dataset.id;
              const nuevoTexto = card
                .querySelector(".edit-input")
                ?.value.trim();
              if (!nuevoTexto) {
                showToast("El comentario no puede estar vacío.", "info");
                return;
              }
              this.disabled = true;
              try {
                const fd2 = new FormData();
                fd2.append("accion", "editar");
                fd2.append("id_comentario", idComentario);
                fd2.append("comentario", nuevoTexto);
                const r2 = await fetch("ajax/comentarios-acciones.php", {
                  method: "POST",
                  body: fd2,
                });
                const d2 = await r2.json();
                if (d2.success) {
                  card.querySelector(".comment-body").textContent = nuevoTexto;
                  card.querySelector(".comment-edit-form").style.display =
                    "none";
                  card.querySelector(".comment-body").style.display = "";
                  showToast("Comentario actualizado.", "success");
                } else {
                  showToast(d2.mensaje || "Error al guardar.", "info");
                }
              } catch {
                showToast("Error de conexión.", "info");
              } finally {
                this.disabled = false;
              }
            });

          nuevaTarjeta
            .querySelector(".btn-delete-comment")
            ?.addEventListener("click", function () {
              comentarioAEliminar = this.dataset.id;
              if (modalBorrar) modalBorrar.style.display = "flex";
            });

          // Limpiar formulario
          input.value = "";
          if (checkSpoiler) checkSpoiler.checked = false;

          const tituloPeliculaComentario =
            document.querySelector(".dropdown-marcar")?.dataset.titulo || "";
          showToast(
            `Has escrito un comentario en ${tituloPeliculaComentario}`,
            "success",
          );
        } else {
          showToast(data.error || "Error al guardar el comentario.", "info");
        }
      } catch (err) {
        console.error("Error al enviar comentario:", err);
        showToast("Error de conexión.", "info");
      } finally {
        btnEnviar.disabled = false;
      }
    });
  }
});
