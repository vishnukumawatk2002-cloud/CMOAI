// CMO AI — minimal app JS (no npm/Vite required)
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
        setTimeout(() => el.remove(), 5000);
    });
});
