document.addEventListener("DOMContentLoaded", () => {
  const modalOverlay = document.getElementById("modalOverlay");
  const modalClose = document.getElementById("modalClose");
  const cancelarBtn = document.getElementById("cancelarBtn");
  const crearListaBtn = document.getElementById("crearListaBtn");
  const crearPrimeraBtn = document.getElementById("crearPrimeraListaBtn");
  const guardarBtn = document.getElementById("guardarListaBtn");
  const mensajeDiv = document.getElementById("modal-mis-listas-mensaje");

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

  function abrirModal() {
    limpiarModal();
    modalOverlay.style.display = "flex";
  }

  crearListaBtn?.addEventListener("click", abrirModal);
  crearPrimeraBtn?.addEventListener("click", abrirModal);

  function cerrarModal() {
    modalOverlay.style.display = "none";
    limpiarModal();
  }

  modalClose?.addEventListener("click", cerrarModal);
  cancelarBtn?.addEventListener("click", cerrarModal);
  modalOverlay?.addEventListener("click", (e) => {
    if (e.target === modalOverlay) cerrarModal();
  });

  function limpiarModal() {
    document.getElementById("listaNombre").value = "";
    document.getElementById("listaDescripcion").value = "";
    portadaInput.value = "";
    portadaPreviewImg.src = "";
    portadaPreviewImg.style.display = "none";
    portadaPlaceholder.style.display = "flex";
    document.querySelector('input[name="visibilidad"][value="publica"]').checked = true;
    mensajeDiv.className = "alert d-none";
    mensajeDiv.textContent = "";
  }

  function mostrarMensaje(texto, tipo) {
    mensajeDiv.className = `alert alert-${tipo}`;
    mensajeDiv.textContent = texto;
  }

  guardarBtn?.addEventListener("click", async () => {
    const titulo = document.getElementById("listaNombre").value.trim();
    const descripcion = document.getElementById("listaDescripcion").value.trim();
    const visibilidad =
      document.querySelector('input[name="visibilidad"]:checked')?.value || "publica";
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

      const res = await fetch("ajax/crear-lista.php", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: formData,
      });
      const data = await res.json();

      if (data.ok) {
        mostrarMensaje("¡Lista creada correctamente!", "success");
        setTimeout(() => window.location.reload(), 1000);
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

  document.querySelector(".mis-listas-grid")?.addEventListener("click", (e) => {
    const card = e.target.closest(".mis-lista-card");
    if (!card) return;
    const idLista = card.dataset.id;

    if (e.target.closest(".btn-eliminar-lista")) {
      if (confirm("¿Seguro que quieres eliminar esta lista? Esta acción no se puede deshacer.")) {
        eliminarLista(idLista, card);
      }
    }
  });

  async function eliminarLista(idLista, cardEl) {
    try {
      const formData = new FormData();
      formData.append("id_lista", idLista);

      const res = await fetch("ajax/eliminar-lista.php", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: formData,
      });
      const data = await res.json();

      if (data.ok) {
        cardEl.remove();
        const statNumber = document.querySelector(".stat-number");
        if (statNumber)
          statNumber.textContent = Math.max(0, parseInt(statNumber.textContent) - 1);
        if (!document.querySelector(".mis-lista-card")) {
          document.querySelector(".mis-listas-grid")?.remove();
          document.querySelector(".empty-state-mis-listas")?.style.removeProperty("display");
        }
      } else {
        alert(data.mensaje || "No se pudo eliminar la lista.");
      }
    } catch (e) {
      alert("Error de conexión al eliminar.");
    }
  }
});