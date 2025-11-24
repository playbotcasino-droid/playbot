// script de alternância de modo claro/escuro
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('theme-toggle');
    const storedTheme = localStorage.getItem('theme') || 'light';
    if (storedTheme === 'dark') {
        document.body.classList.add('dark');
        toggle.textContent = '☀️';
    } else {
        toggle.textContent = '🌙';
    }
    toggle.addEventListener('click', function () {
        document.body.classList.toggle('dark');
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        toggle.textContent = isDark ? '☀️' : '🌙';
    });
});
