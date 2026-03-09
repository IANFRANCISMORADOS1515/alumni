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
                    <form class="d-flex w-100 align-items-center" action="alumni_list.php" method="GET">
                        <input type="search"
                               name="search"
                               class="form-control form-control-sm rounded-pill"
                               placeholder="Search alumni..."
                               aria-label="Search alumni"
                               required>
                        <button type="submit" class="btn btn-outline-light btn-sm ms-2 rounded-pill">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
