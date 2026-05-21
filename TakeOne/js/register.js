document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("registerForm");
  const passwordInput = document.getElementById("registerPassword");
  const confirmPasswordInput = document.getElementById(
    "registerPasswordConfirm",
  );
  const passwordStrength = document.getElementById("passwordStrength");
  const strengthBarFill = document.getElementById("strengthBarFill");
  const strengthText = document.getElementById("strengthText");
  const errorMsg = document.getElementById("errorMsg");
  const errorText = document.getElementById("errorText");

  // Elementos de requisitos
  const reqLength = document.getElementById("req-length");
  const reqUppercase = document.getElementById("req-uppercase");
  const reqLowercase = document.getElementById("req-lowercase");
  const reqNumber = document.getElementById("req-number");

  // ============ HELPER: mostrar/ocultar error inline ============

  function showError(msg) {
    errorText.textContent = msg;
    errorMsg.classList.add("visible");
    errorMsg.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function hideError() {
    errorMsg.classList.remove("visible");
    errorText.textContent = "";
  }

  // ============ FUNCIONALIDAD DE CONTRASEÑA ============

  // Botones de mostrar/ocultar contraseña
  const togglePasswordButtons = document.querySelectorAll(".toggle-password");

  togglePasswordButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const targetId = this.getAttribute("data-target");
      const targetInput = document.getElementById(targetId);
      const icon = this.querySelector(".material-icons-outlined");

      if (targetInput.type === "password") {
        targetInput.type = "text";
        icon.textContent = "visibility";
      } else {
        targetInput.type = "password";
        icon.textContent = "visibility_off";
      }
    });
  });

  // Función para validar un requisito individual
  function validatePasswordRequirement(requirement, isValid) {
    const icon = requirement.querySelector(".material-icons-outlined");
    if (isValid) {
      requirement.classList.add("valid");
      icon.textContent = "check_circle";
    } else {
      requirement.classList.remove("valid");
      icon.textContent = "cancel";
    }
  }

  // Función para verificar la fortaleza de la contraseña
  function checkPasswordStrength(password) {
    if (password.length === 0) {
      passwordStrength.style.display = "none";
      return 0;
    }

    passwordStrength.style.display = "block";

    let strength = 0;
    const checks = {
      length: password.length >= 8,
      uppercase: /[A-Z]/.test(password),
      lowercase: /[a-z]/.test(password),
      number: /[0-9]/.test(password),
      special: /[^A-Za-z0-9]/.test(password),
    };

    // Actualizar indicadores visuales
    validatePasswordRequirement(reqLength, checks.length);
    validatePasswordRequirement(reqUppercase, checks.uppercase);
    validatePasswordRequirement(reqLowercase, checks.lowercase);
    validatePasswordRequirement(reqNumber, checks.number);

    // Calcular fortaleza
    if (checks.length) strength++;
    if (checks.uppercase) strength++;
    if (checks.lowercase) strength++;
    if (checks.number) strength++;
    if (checks.special) strength++;

    // Actualizar barra de fortaleza
    strengthBarFill.className = "strength-bar-fill";
    strengthText.className = "strength-text";

    if (strength <= 2) {
      strengthBarFill.classList.add("weak");
      strengthText.classList.add("weak");
      strengthText.textContent = "Débil";
    } else if (strength <= 4) {
      strengthBarFill.classList.add("medium");
      strengthText.classList.add("medium");
      strengthText.textContent = "Media";
    } else {
      strengthBarFill.classList.add("strong");
      strengthText.classList.add("strong");
      strengthText.textContent = "Fuerte";
    }

    return strength;
  }

  // Event listener para el input de contraseña
  passwordInput.addEventListener("input", function () {
    checkPasswordStrength(this.value);
    hideError();
  });

  // ============ VALIDACIÓN DEL FORMULARIO ============

  // Validación de email
  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Ocultar error al escribir en cualquier campo
  form.querySelectorAll(".form-input").forEach((input) => {
    input.addEventListener("input", hideError);
  });

  // Submit del formulario
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    hideError();

    const username = document.getElementById("registerName").value.trim();
    const email = document.getElementById("registerEmail").value.trim();
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    // Validaciones del nombre de usuario
    if (username === "") {
      showError("El nombre de usuario es obligatorio");
      document.getElementById("registerName").focus();
      return;
    }

    if (username.length < 3) {
      showError("El nombre de usuario debe tener al menos 3 caracteres");
      document.getElementById("registerName").focus();
      return;
    }

    // Validar email
    if (email === "") {
      showError("El correo electrónico es obligatorio");
      document.getElementById("registerEmail").focus();
      return;
    }

    if (!validateEmail(email)) {
      showError("Por favor introduce un correo electrónico válido");
      document.getElementById("registerEmail").focus();
      return;
    }

    // Validaciones de contraseña
    if (password === "") {
      showError("La contraseña es obligatoria");
      passwordInput.focus();
      return;
    }

    if (password.length < 8) {
      showError("La contraseña debe tener al menos 8 caracteres");
      passwordInput.focus();
      return;
    }

    if (!/[A-Z]/.test(password)) {
      showError("La contraseña debe contener al menos una letra mayúscula");
      passwordInput.focus();
      return;
    }

    if (!/[a-z]/.test(password)) {
      showError("La contraseña debe contener al menos una letra minúscula");
      passwordInput.focus();
      return;
    }

    if (!/[0-9]/.test(password)) {
      showError("La contraseña debe contener al menos un número");
      passwordInput.focus();
      return;
    }

    // Validar confirmación de contraseña
    if (confirmPassword === "") {
      showError("Debes confirmar tu contraseña");
      confirmPasswordInput.focus();
      return;
    }

    if (password !== confirmPassword) {
      showError("Las contraseñas no coinciden");
      confirmPasswordInput.focus();
      return;
    }

    // Validar términos y condiciones
    const acceptTerms = document.getElementById("acceptTerms").checked;
    if (!acceptTerms) {
      showError("Debes aceptar los términos y condiciones");
      return;
    }

    // Si todas las validaciones pasan, proceder con el registro
    const btnSubmit = form.querySelector(".btn-submit");
    btnSubmit.innerHTML =
      '<span class="material-icons-outlined">hourglass_empty</span> Creando cuenta...';
    btnSubmit.disabled = true;

    const formData = new FormData();
    formData.append("username", username);
    formData.append("email", email);
    formData.append("password", password);

    fetch("register.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.ok) {
          btnSubmit.innerHTML =
            '<span class="material-icons-outlined">check_circle</span> ¡Cuenta creada!';
          btnSubmit.style.background =
            "linear-gradient(135deg, #10b981 0%, #059669 100%)";
          setTimeout(() => {
            window.location.href = "login.html";
          }, 1500);
        } else {
          showError(data.mensaje);
          btnSubmit.innerHTML = "<span>Crear cuenta</span>";
          btnSubmit.disabled = false;
        }
      })
      .catch(() => {
        showError("Error de conexión. Inténtalo de nuevo.");
        btnSubmit.innerHTML = "<span>Crear cuenta</span>";
        btnSubmit.disabled = false;
      });
  });

  // Validación en tiempo real del nombre de usuario
  const usernameInput = document.getElementById("registerName");
  usernameInput.addEventListener("input", function () {
    const value = this.value;
    this.value = value.replace(/[^a-zA-Z0-9_]/g, "");
  });

  // Validación visual del email
  const emailInput = document.getElementById("registerEmail");
  emailInput.addEventListener("blur", function () {
    if (this.value && !validateEmail(this.value)) {
      this.style.borderColor = "#ef4444";
    } else {
      this.style.borderColor = "rgba(255, 255, 255, 0.1)";
    }
  });

  emailInput.addEventListener("focus", function () {
    this.style.borderColor = "var(--accent)";
  });
});
