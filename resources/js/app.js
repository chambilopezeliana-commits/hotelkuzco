const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const btnMenu = document.getElementById('btn-menu');

function abrirMenu() {
    if (!sidebar) return;
    sidebar.classList.remove('-translate-x-full');
    overlay?.classList.remove('hidden');
}

function cerrarMenu() {
    if (!sidebar) return;
    sidebar.classList.add('-translate-x-full');
    overlay?.classList.add('hidden');
}

btnMenu?.addEventListener('click', abrirMenu);
overlay?.addEventListener('click', cerrarMenu);

window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        sidebar?.classList.remove('-translate-x-full');
        overlay?.classList.add('hidden');
    } else {
        sidebar?.classList.add('-translate-x-full');
    }
});

if (window.innerWidth < 1024) {
    sidebar?.classList.add('-translate-x-full');
}
