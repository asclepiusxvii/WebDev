document.addEventListener('DOMContentLoaded', () => {
  const flash = document.querySelector('[data-flash]');
  if (flash) {
    setTimeout(() => flash.remove(), 5000);
  }
});
