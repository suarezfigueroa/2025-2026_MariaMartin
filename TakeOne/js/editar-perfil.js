document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("editarPerfilForm");
  const avatarInput = document.getElementById("avatarInput");
  const avatarPreview = document.getElementById("avatarPreview");
  const bioTextarea = document.getElementById("bio");
  const bioCharCount = document.getElementById("bioCharCount");
  const btnCancelar = document.getElementById("btnCancelar");
  const btnGuardar = form.querySelector(".btn-guardar");
  const emailInput = document.getElementById("email");
  const usernameInput = document.getElementById("username");

  let originalValues = {
    username: "",
    bio: "",
    location: "",
    email: "",
    avatar: "",
  };

  let formChanged = false;

  function updateCharCount() {
    const currentLength = bioTextarea.value.length;
    bioCharCount.textContent = `${currentLength}/200`;
    bioCharCount.style.color =
      currentLength >= 200 ? "var(--primary)" : "rgba(255, 255, 255, 0.6)";
  }

  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  function syncOriginalValuesFromForm() {
    originalValues = {
      username: document.getElementById("username").value,
      bio: bioTextarea.value,
      location: document.getElementById("location").value,
      email: document.getElementById("email").value,
      avatar: avatarPreview.src,
    };
  }

  function setSavingState(isSaving, text = "guardando...") {
    if (isSaving) {
      btnGuardar.disabled = true;
      btnGuardar.innerHTML = `<span class="material-icons-outlined">hourglass_empty</span> ${text}`;
    } else {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML =
        '<span class="material-icons-outlined">save</span> guardar cambios';
      btnGuardar.style.background = "";
    }
  }

  avatarInput.addEventListener("change", function (e) {
    const file = e.target.files[0];

    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      alert("la imagen no puede superar los 5mb");
      avatarInput.value = "";
      return;
    }

    if (!file.type.match(/^image\//)) {
      alert("selecciona una imagen válida");
      avatarInput.value = "";
      return;
    }

    const reader = new FileReader();
    reader.onload = function (ev) {
      avatarPreview.src = ev.target.result;
    };
    reader.readAsDataURL(file);
  });

  bioTextarea.addEventListener("input", updateCharCount);

  const togglePasswordButtons = document.querySelectorAll(".toggle-password");
  togglePasswordButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const targetId = this.getAttribute("data-target");
      const targetInput = document.getElementById(targetId);
      const icon = this.querySelector(".material-icons-outlined");

      if (targetInput.type === "password") {
        targetInput.type = "text";
        icon.textContent = "visibility_off";
      } else {
        targetInput.type = "password";
        icon.textContent = "visibility";
      }
    });
  });

  const newPasswordInput = document.getElementById("newPassword");
  const confirmPasswordInput = document.getElementById("confirmPassword");
  const passwordStrength = document.getElementById("passwordStrength");
  const strengthBarFill = document.getElementById("strengthBarFill");
  const strengthText = document.getElementById("strengthText");

  const reqLength = document.getElementById("req-length");
  const reqUppercase = document.getElementById("req-uppercase");
  const reqLowercase = document.getElementById("req-lowercase");
  const reqNumber = document.getElementById("req-number");

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

  function checkPasswordStrength(password) {
    if (password.length === 0) {
      passwordStrength.style.display = "none";
      return;
    }

    passwordStrength.style.display = "block";

    const checks = {
      length: password.length >= 8,
      uppercase: /[A-Z]/.test(password),
      lowercase: /[a-z]/.test(password),
      number: /[0-9]/.test(password),
      special: /[^A-Za-z0-9]/.test(password),
    };

    validatePasswordRequirement(reqLength, checks.length);
    validatePasswordRequirement(reqUppercase, checks.uppercase);
    validatePasswordRequirement(reqLowercase, checks.lowercase);
    validatePasswordRequirement(reqNumber, checks.number);

    let strength = 0;
    Object.values(checks).forEach((value) => {
      if (value) strength++;
    });

    strengthBarFill.className = "strength-bar-fill";
    strengthText.className = "strength-text";

    if (strength <= 2) {
      strengthBarFill.classList.add("weak");
      strengthText.classList.add("weak");
      strengthText.textContent = "débil";
    } else if (strength <= 4) {
      strengthBarFill.classList.add("medium");
      strengthText.classList.add("medium");
      strengthText.textContent = "media";
    } else {
      strengthBarFill.classList.add("strong");
      strengthText.classList.add("strong");
      strengthText.textContent = "fuerte";
    }
  }

  newPasswordInput.addEventListener("input", function () {
    checkPasswordStrength(this.value);
  });

  emailInput.addEventListener("blur", function () {
    if (this.value && !validateEmail(this.value)) {
      this.style.borderColor = "#ef4444";
    } else {
      this.style.borderColor = "rgba(255, 255, 255, 0.15)";
    }
  });

  usernameInput.addEventListener("input", function () {
    this.value = this.value.replace(/[^a-zA-Z0-9_]/g, "");
  });

  btnCancelar.addEventListener("click", function (e) {
    e.preventDefault();

    const confirmCancel = confirm(
      "¿estás seguro de que quieres cancelar? se perderán todos los cambios no guardados.",
    );
    if (!confirmCancel) return;

    document.getElementById("username").value = originalValues.username;
    bioTextarea.value = originalValues.bio;
    document.getElementById("location").value = originalValues.location;
    document.getElementById("email").value = originalValues.email;
    avatarPreview.src = originalValues.avatar || "img/default-avatar.png";
    avatarInput.value = "";

    document.getElementById("currentPassword").value = "";
    document.getElementById("newPassword").value = "";
    document.getElementById("confirmPassword").value = "";

    passwordStrength.style.display = "none";

    [reqLength, reqUppercase, reqLowercase, reqNumber].forEach((req) => {
      req.classList.remove("valid");
      req.querySelector(".material-icons-outlined").textContent = "cancel";
    });

    formChanged = false;
    updateCharCount();
    window.location.href = "perfil.php";
  });

  form.addEventListener("input", function () {
    formChanged = true;
  });

  window.addEventListener("beforeunload", function (e) {
    if (formChanged) {
      e.preventDefault();
      e.returnValue = "";
    }
  });

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const username = document.getElementById("username").value.trim();
    const bio = bioTextarea.value.trim();
    const location = document.getElementById("location").value.trim();
    const email = document.getElementById("email").value.trim();
    const currentPassword = document.getElementById("currentPassword").value;
    const newPassword = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    if (username === "") {
      alert("el nombre de usuario es obligatorio");
      document.getElementById("username").focus();
      return;
    }

    if (username.length < 3) {
      alert("el nombre de usuario debe tener al menos 3 caracteres");
      document.getElementById("username").focus();
      return;
    }

    if (bio.length > 200) {
      alert("la biografía no puede superar los 200 caracteres");
      bioTextarea.focus();
      return;
    }

    if (email === "" || !validateEmail(email)) {
      alert("introduce un correo electrónico válido");
      emailInput.focus();
      return;
    }

    const changingPassword = currentPassword || newPassword || confirmPassword;

    if (changingPassword) {
      if (!currentPassword) {
        alert("debes introducir tu contraseña actual");
        return;
      }

      if (!newPassword) {
        alert("debes introducir una nueva contraseña");
        return;
      }

      if (!confirmPassword) {
        alert("debes confirmar la nueva contraseña");
        return;
      }

      if (
        newPassword.length < 8 ||
        !/[A-Z]/.test(newPassword) ||
        !/[a-z]/.test(newPassword) ||
        !/[0-9]/.test(newPassword)
      ) {
        alert("la nueva contraseña no cumple los requisitos mínimos");
        return;
      }

      if (newPassword !== confirmPassword) {
        alert("las contraseñas no coinciden");
        return;
      }

      if (currentPassword === newPassword) {
        alert("la nueva contraseña debe ser diferente a la actual");
        return;
      }
    }

    const favoriteMovies =
      typeof window.getFavoriteMoviesForSubmit === "function"
        ? window.getFavoriteMoviesForSubmit()
        : [];

    const selectedGenres =
      typeof window.getSelectedGenresForSubmit === "function"
        ? window.getSelectedGenresForSubmit()
        : [];

    const payload = new FormData();
    payload.append("username", username);
    payload.append("bio", bio);
    payload.append("location", location);
    payload.append("email", email);
    payload.append("favoriteMovies", JSON.stringify(favoriteMovies));
    payload.append("selectedGenres", JSON.stringify(selectedGenres));

    if (avatarInput.files[0]) {
      payload.append("avatar", avatarInput.files[0]);
    }

    if (changingPassword) {
      payload.append("currentPassword", currentPassword);
      payload.append("newPassword", newPassword);
    }

    try {
      setSavingState(true);

      const response = await fetch(window.takeonePerfilConfig.savePerfilUrl, {
        method: "POST",
        body: payload,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      const data = await response.json();

      if (!data.ok) {
        throw new Error(data.message || "no se pudo guardar el perfil");
      }

      btnGuardar.innerHTML =
        '<span class="material-icons-outlined">check_circle</span> guardado';
      btnGuardar.style.background =
        "linear-gradient(135deg, #10b981 0%, #059669 100%)";

      formChanged = false;
      syncOriginalValuesFromForm();

      setTimeout(() => {
        window.location.href = "perfil.php";
      }, 900);
    } catch (error) {
      alert(error.message || "ha ocurrido un error al guardar");
      setSavingState(false);
    }
  });

  updateCharCount();

  setTimeout(() => {
    syncOriginalValuesFromForm();
  }, 700);
});
