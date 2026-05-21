document.addEventListener("DOMContentLoaded", () => {
  const btnsFiltro = document.querySelectorAll("#filtrosActividad .filtro-btn");
  const items = document.querySelectorAll("#actividadTimeline .actividad-item");

  if (!btnsFiltro.length) return;

  btnsFiltro.forEach((btn) => {
    btn.addEventListener("click", () => {
      btnsFiltro.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      const filtro = btn.dataset.filtro;

      items.forEach((item) => {
        const tipos = item.dataset.tipo.split(" ");
        const visible = filtro === "todos" || tipos.includes(filtro);
        item.style.display = visible ? "" : "none";

        // Modo valoración: mostrar icono y badge de valoración en tarjetas mixtas
        const esMixta = tipos.includes("valoracion") && tipos.length > 1;
        if (esMixta) {
          const icono = item.querySelector(".actividad-icon");
          const badge = item.querySelector(".actividad-badge");

          if (filtro === "valoracion") {
            // Guardar clases originales la primera vez
            if (!icono.dataset.claseOriginal) {
              icono.dataset.claseOriginal = icono.className;
              badge.dataset.textoOriginal = badge.textContent;
              badge.dataset.claseOriginal = badge.className;
            }
            icono.className = "actividad-icon icon-valoracion";
            badge.textContent = "Película valorada";
            badge.className = "actividad-badge badge-valoracion";
          } else {
            // Restaurar clases originales
            if (icono.dataset.claseOriginal) {
              icono.className = icono.dataset.claseOriginal;
              badge.textContent = badge.dataset.textoOriginal;
              badge.className = badge.dataset.claseOriginal;
            }
          }
        }
      });

      // Estado vacío
      const visibles = [...items].filter((i) => i.style.display !== "none");
      const emptyEl = document.querySelector(".empty-state-actividad");
      const timeline = document.getElementById("actividadTimeline");

      if (emptyEl && timeline) {
        timeline.style.display = visibles.length === 0 ? "none" : "";
        emptyEl.style.display = visibles.length === 0 ? "flex" : "none";
      }
    });
  });
});
