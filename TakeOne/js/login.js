// Toggle para mostrar/ocultar contraseña
const toggleButton = document.querySelector(".toggle-password");

toggleButton.addEventListener("click", () => {
  const targetId = toggleButton.dataset.target;
  const input = document.getElementById(targetId);
  const icon = toggleButton.querySelector(".material-icons-outlined");

  if (input.type === "password") {
    input.type = "text";
    icon.textContent = "visibility";
  } else {
    input.type = "password";
    icon.textContent = "visibility_off";
  }
});

// Mostrar error si viene por URL
const params = new URLSearchParams(window.location.search);
const error = params.get("error");
if (error) {
  const box = document.getElementById("loginError");
  const msg = document.getElementById("loginErrorMsg");
  if (error === "credenciales") {
    msg.textContent = "Usuario o contraseña incorrectos. Inténtalo de nuevo.";
  } else if (error === "vacios") {
    msg.textContent = "Por favor, rellena todos los campos.";
  } else if (error === "suspendido") {
    msg.textContent = "Tu cuenta ha sido suspendida. Contacta con soporte.";
  }
  box.style.display = "flex";
  // Limpiar la URL para que no persista el error al recargar
  history.replaceState(null, "", "login.html");
}

// Manejo del formulario de login
const loginForm = document.getElementById("loginForm");

loginForm.addEventListener("submit", (e) => {});
