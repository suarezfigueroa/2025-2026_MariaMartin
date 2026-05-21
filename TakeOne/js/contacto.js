// Contador de caracteres
const mensajeTextarea = document.getElementById("mensaje");
const charCounter = document.querySelector(".char-counter");

mensajeTextarea.addEventListener("input", function () {
  const length = this.value.length;
  charCounter.textContent = `${length} / 1000 caracteres`;

  if (length > 1000) {
    this.value = this.value.substring(0, 1000);
    charCounter.textContent = "1000 / 1000 caracteres";
  }
});
