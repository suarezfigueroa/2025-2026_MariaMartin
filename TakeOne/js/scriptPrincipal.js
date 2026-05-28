// Reset scroll sliders
window.addEventListener("load", () => {
  document.querySelectorAll(".h-snap").forEach((slider) => {
    slider.scrollLeft = 0;
  });
});

// Sistema de carruseles
function setupCarousel(wrapperIndex, leftBtnId, rightBtnId) {
  const wrappers = document.querySelectorAll(".slider-wrapper");
  if (!wrappers[wrapperIndex]) return;

  const container = wrappers[wrapperIndex].querySelector(".h-snap");
  const left = document.getElementById(leftBtnId);
  const right = document.getElementById(rightBtnId);

  if (!container || !left || !right) return;

  function scrollByStep(step) {
    container.scrollBy({ left: step, behavior: "smooth" });
  }

  left.addEventListener("click", () => scrollByStep(-350));
  right.addEventListener("click", () => scrollByStep(350));
}

// Inicializar carruseles
setupCarousel(0, "leftBtn", "rightBtn");
setupCarousel(1, "leftBtn2", "rightBtn2");

// Animación de entrada suave
window.addEventListener("load", () => {
  document.querySelectorAll(".h-item, .news-card").forEach((el, i) => {
    el.style.opacity = "0";
    el.style.transform = "translateY(20px)";
    setTimeout(() => {
      el.style.transition = "all 0.5s ease";
      el.style.opacity = "1";
      el.style.transform = "translateY(0)";
    }, i * 50);
  });
});

const navLinks = document.querySelectorAll(".nav-center a");
const currentUrl = window.location.pathname.split("/").pop(); // nombre del archivo actual

navLinks.forEach((link) => {
  if (link.getAttribute("href") === currentUrl) {
    link.classList.add("active");
  }
});
