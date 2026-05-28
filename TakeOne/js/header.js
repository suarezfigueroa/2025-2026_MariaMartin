document.addEventListener("DOMContentLoaded", () => {
  const hamburger = document.querySelector(".hamburger");
  const navCenter = document.querySelector(".nav-center");

  if (hamburger && navCenter) {
    hamburger.addEventListener("click", () => {
      navCenter.classList.toggle("active");
    });

    document.addEventListener("click", (e) => {
      if (!e.target.closest(".site-nav")) {
        navCenter.classList.remove("active");
      }
    });
  }
});