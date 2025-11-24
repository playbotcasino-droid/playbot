// script de pesquisa de jogos na página inicial
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-input');
    if (!searchInput) return;
    const cards = document.querySelectorAll('.games-grid .game-card');
    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        cards.forEach(card => {
            const name = card.dataset.name.toLowerCase();
            if (name.includes(query)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
