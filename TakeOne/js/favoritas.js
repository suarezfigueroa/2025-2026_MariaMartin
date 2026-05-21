document.addEventListener("DOMContentLoaded", () => {
  const favoritasGrid = document.querySelector(".favoritas-grid");
  const emptyState = document.querySelector(".empty-state");
  const statNumber = document.querySelector(".stat-card-fav .stat-number");

  function actualizarEstadoVista() {
    const total = document.querySelectorAll(".favorita-card").length;

    if (statNumber) {
      statNumber.textContent = total;
    }

    if (!favoritasGrid || !emptyState) return;

    if (total === 0) {
      favoritasGrid.style.display = "none";
      emptyState.style.display = "flex";
    } else {
      favoritasGrid.style.display = "grid";
      emptyState.style.display = "none";
    }
  }

  actualizarEstadoVista();
});
