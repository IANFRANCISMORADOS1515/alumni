<?php
$activePage = basename($_SERVER['PHP_SELF']);
$navLinks = [
    ['href' => 'index.php', 'label' => 'Home'],
    ['href' => 'about.php', 'label' => 'About'],
    ['href' => 'alumni_list.php', 'label' => 'Alumni'],
    ['href' => 'event_page.php', 'label' => 'Events'],
];
?>

<nav class="navbar navbar-expand-lg navbar-dark sci-navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="default/logo.png" alt="SCI" height="45" class="me-3" onerror="this.src='/placeholder.svg?height=45&width=45'">
            <span>SCI Alumni</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span>☰</span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($navLinks as $link): ?>
                    <?php
                        $isActive = ($activePage === basename($link['href']));
                        $linkColor = $isActive ? '#1572e8' : 'white';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $link['href'] ?>" style="color: <?= $linkColor ?>;">
                            <?= $link['label'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item d-flex align-items-center ms-2">
                    <button class="btn btn-outline-light btn-sm rounded-pill d-flex align-items-center gap-2"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#navSearchModal">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Navbar Search Modal -->
<div class="modal fade" id="navSearchModal" tabindex="-1" aria-labelledby="navSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="navSearchModalLabel">Search the SCI Network</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="navbarSearchForm" class="mb-3">
                    <div class="input-group">
                        <input
                            type="text"
                            id="navbarSearchInput"
                            name="search"
                            class="form-control form-control-lg rounded-pill"
                            placeholder="Alumni name, student ID, college..."
                            aria-label="Search alumni"
                            required
                        />
                        <button class="btn btn-primary btn-lg rounded-pill ms-2" type="submit">
                            <i class="fas fa-search me-1"></i>
                            Search
                        </button>
                    </div>
                </form>
                <div id="navbarSearchResults" class="list-group"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function initNavbarSearch() {
        const form = document.getElementById('navbarSearchForm');
        const input = document.getElementById('navbarSearchInput');
        const resultsContainer = document.getElementById('navbarSearchResults');
        const modalEl = document.getElementById('navSearchModal');

        if (!form || !input || !resultsContainer || !modalEl) return;

        function escapeHTML(str) {
            return str.replace(/[&<>"']/g, function(match) {
                switch (match) {
                    case '&': return '&amp;';
                    case '<': return '&lt;';
                    case '>': return '&gt;';
                    case '"': return '&quot;';
                    case "'": return '&#39;';
                }
            });
        }

        function renderMessage(message, type = 'muted') {
            resultsContainer.innerHTML = `<p class="text-${type} mb-0">${escapeHTML(message)}</p>`;
        }

        async function handleSearch(event) {
            event.preventDefault();
            const term = input.value.trim();
            if (!term) return;
            resultsContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status" aria-hidden="true"></div><div class="mt-2 small text-muted">Searching...</div></div>';

            try {
                const response = await fetch('search_modal_results.php?search=' + encodeURIComponent(term));
                if (!response.ok) throw new Error('Search failed');
                const payload = await response.json();

                if (!Array.isArray(payload.results) || !payload.results.length) {
                    renderMessage('No results found for "' + (payload.query ?? term) + '".');
                    return;
                }

                const itemsMarkup = payload.results.map(item => `
                    <a href="${item.link}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold mb-1">${escapeHTML(item.title)}</div>
                            <small class="text-muted">${escapeHTML(item.description)}</small>
                        </div>
                        <span class="badge bg-success rounded-pill ms-3 align-self-center">View</span>
                    </a>
                `).join('');

                resultsContainer.innerHTML = `<div class="list-group">${itemsMarkup}</div>`;
            } catch (error) {
                console.error(error);
                renderMessage('Unable to fetch search results. Please try again later.', 'danger');
            }
        }

        form.addEventListener('submit', handleSearch);

        modalEl.addEventListener('shown.bs.modal', function() {
            input.focus();
            resultsContainer.innerHTML = '';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavbarSearch);
    } else {
        initNavbarSearch();
    }
})();
</script>
