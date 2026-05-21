const avatarToggle = document.getElementById("avatarToggle");
const dropdownMenu = document.getElementById("dropdownMenu");
const logoutBtn = document.getElementById("logoutBtn");

// Toggle del dropdown
if (avatarToggle && dropdownMenu) {
  avatarToggle.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    dropdownMenu.classList.toggle("show");
  });
}

// Cerrar dropdown al hacer click fuera
document.addEventListener("click", (e) => {
  if (!avatarToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
    dropdownMenu.classList.remove("show");
  }
});

// Cerrar dropdown con tecla Escape
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && dropdownMenu.classList.contains("show")) {
    dropdownMenu.classList.remove("show");
  }
});

// Prevenir que el click dentro del dropdown lo cierre
if (dropdownMenu) {
  dropdownMenu.addEventListener("click", (e) => {
    e.stopPropagation();
  });
}

// Confirmación al cerrar sesión
if (logoutBtn) {
  logoutBtn.addEventListener("click", (e) => {
    if (!confirm("¿Estás seguro de que quieres cerrar sesión?")) {
      e.preventDefault();
    }
  });
}
