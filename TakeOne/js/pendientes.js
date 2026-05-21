document.addEventListener("DOMContentLoaded", () => {
  const filterButtons = document.querySelectorAll(".filter-btn-pendientes");
  const grid = document.querySelector(".pendientes-grid");
  const emptyState = document.querySelector(".empty-state");
  const statNumber = document.querySelector(".stat-number");

  function actualizarContador() {
    const total = document.querySelectorAll(".pendiente-card").length;
    if (statNumber) {
      statNumber.textContent = total;
    }

    if (grid && emptyState) {
      if (total === 0) {
        grid.style.display = "none";
        emptyState.style.display = "flex";
      } else {
        grid.style.display = "grid";
        emptyState.style.display = "none";
      }
    }
  }

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");

      const filter = button.dataset.filter;
      const cardsArray = Array.from(
        document.querySelectorAll(".pendiente-card"),
      );

      if (filter === "agregadas-reciente") {
        cardsArray.sort(
          (a, b) => new Date(b.dataset.fecha) - new Date(a.dataset.fecha),
        );
      } else if (filter === "mas-antiguas") {
        cardsArray.sort(
          (a, b) => new Date(a.dataset.fecha) - new Date(b.dataset.fecha),
        );
      }

      cardsArray.forEach((card) => grid.appendChild(card));
    });
  });

  document.addEventListener("click", async (e) => {
    const removeBtn = e.target.closest(".btn-remove-pendiente");
    if (!removeBtn) return;

    e.preventDefault();
    e.stopPropagation();

    const peliculaId = removeBtn.dataset.peliculaId;
    const card = removeBtn.closest(".pendiente-card");

    if (!peliculaId || !card) return;

    try {
      const formData = new FormData();
      formData.append("id_pelicula", peliculaId);
      formData.append("estado", "");

      const response = await fetch("actualizar-estado-pelicula.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (!response.ok || !data.ok) {
        throw new Error(
          data.message || "No se pudo quitar la película de pendientes.",
        );
      }

      card.style.opacity = "0";
      card.style.transform = "scale(0.85)";

      setTimeout(() => {
        card.remove();
        actualizarContador();
      }, 250);
    } catch (error) {
      alert(error.message || "Ha ocurrido un error.");
    }
  });

  actualizarContador();
});
