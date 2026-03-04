<?php
$activePage = basename($_SERVER['PHP_SELF']);
$navLinks = [
    ['href' => 'index.php', 'label' => 'Home'],
    ['href' => 'about.php', 'label' => 'About'],
    ['href' => 'alumni_list.php', 'label' => 'Alumni'],
    ['href' => 'event_page.php', 'label' => 'Events'],
];
?>
<nav class="navbar navbar-expand-lg navbar-dark sci-navbar shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            &nbsp;<img src="default/logo.png" alt="SCI" height="40" class="me-2" onerror="this.src='/placeholder.svg?height=40&width=40'">
            SCI Alumni
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="margin-right:10px;">
            <span style="color:black;text-align:center;font-size:1rem;line-height:1rem;padding:10px;">&#9776;</span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($navLinks as $link): ?>
                    <?php
                        $linkColor = ($activePage === basename($link['href'])) ? '#1572e8' : 'black';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $link['href'] ?>" style="color: <?= $linkColor ?>;">
                            <?= $link['label'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a class="nav-link btn btn-light text-success px-3 ms-2" href="admin_login.php">
                        Admin Login
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
