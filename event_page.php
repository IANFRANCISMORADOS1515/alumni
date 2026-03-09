<?php
require_once 'db_con.php';

$event_type = $_GET['event_type'] ?? 'all';
$timeframe = $_GET['timeframe'] ?? 'upcoming';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;

$where = ["e.status = 'Published'"];
$params = [];

if ($event_type !== 'all') {
    $where[] = 'e.event_type = ?';
    $params[] = $event_type;
}

if ($timeframe === 'upcoming') {
    $where[] = 'e.start_date >= CURDATE()';
} elseif ($timeframe === 'past') {
    $where[] = 'e.start_date < CURDATE()';
}

$where_sql = implode(' AND ', $where);

$event_types = fetchAll("SELECT DISTINCT event_type FROM events WHERE status = 'Published' ORDER BY event_type ASC");

$count_row = fetchRow("SELECT COUNT(*) as count FROM events e WHERE $where_sql", $params);
$total_results = (int)($count_row['count'] ?? 0);
$total_pages = (int)ceil($total_results / $limit);

$order_sql = $timeframe === 'upcoming' ? 'e.start_date ASC' : 'e.start_date DESC';

$events = fetchAll("
    SELECT e.id, e.title, e.description, e.event_type, e.start_date, e.end_date,
           e.start_time, e.end_time, e.venue, e.is_online, e.featured_image
    FROM events e
    WHERE $where_sql
    ORDER BY $order_sql
    LIMIT $limit OFFSET $offset
", $params);

function getDefaultImage($type, $image) {
    if (empty($image) || !file_exists($image)) {
        switch($type) {
            case 'event':
                return 'default/default-event.png';
            default:
                return 'default/default-image.png';
        }
    }
    return $image;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCI Events</title>
    <link rel="icon" href="default/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="style/main.css" />
    <link rel="stylesheet" href="style/navbar.css" />
    <link rel="stylesheet" href="style/events.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
    <?php include 'include_homepage/navbar.php'; ?>

    <style>
    .hero-watermark{position:relative;overflow:hidden}
    .hero-watermark::before{content:"";position:absolute;inset:0;background-image:url('default/logo.png');background-repeat:no-repeat;background-position:center;background-size:40%;opacity:0.08;pointer-events:none;z-index:0}
    .hero-watermark .container{position:relative;z-index:1;text-align:center}
    /* Center hero content */
    .hero-section{padding:80px 0;display:flex;align-items:center;justify-content:center}
    .hero-section .row{justify-content:center;align-items:center}
    .hero-section .container{max-width:1000px}
    @media (max-width: 992px){
        .hero-section{padding:60px 0}
        .hero-section .container{padding:0 20px}
    }
    </style>

    <section class="hero-section hero-watermark">
        <div class="container">
            <h1 class="display-5 fw-bold mb-3">Sandigan Colleges Incorporated Events</h1>
            <p class="lead mb-4">Upcoming activities and community gatherings for SCI alumni</p>
            <form method="GET" class="filter-box">
                <div class="row g-2 justify-content-center">
                    <div class="col-md-4">
                        <select name="event_type" class="w-100">
                            <option value="all">All Event Types</option>
                            <?php foreach ($event_types as $type): ?>
                                <option value="<?= htmlspecialchars($type['event_type']) ?>" <?= $event_type === $type['event_type'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['event_type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="timeframe" class="w-100">
                            <option value="upcoming" <?= $timeframe === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="past" <?= $timeframe === 'past' ? 'selected' : '' ?>>Past</option>
                            <option value="all" <?= $timeframe === 'all' ? 'selected' : '' ?>>All Dates</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit"><i class="fas fa-filter"></i> Apply</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title m-0">Event Listings</h2>
                <span class="text-muted"><?= $total_results ?> event<?= $total_results === 1 ? '' : 's' ?></span>
            </div>

            <?php if (empty($events)): ?>
                <div class="text-center py-5">
                    <p class="text-muted">No events matched your filters.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($events as $event): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="event-card" onclick="showDetailModal('event', <?= $event['id'] ?>)">
                                <div class="position-relative">
                                    <img src="<?= htmlspecialchars(getDefaultImage('event', $event['featured_image'])) ?>" class="event-image"
                                         onerror="this.src='default/default-event.png'" alt="<?= htmlspecialchars($event['title']) ?>">
                                    <span class="event-type-badge"><?= htmlspecialchars($event['event_type']) ?></span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title mb-2"><?= htmlspecialchars($event['title']) ?></h5>
                                    <p class="event-meta mb-2">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('M j, Y', strtotime($event['start_date'])) ?>
                                        <?php if (!empty($event['start_time'])): ?>
                                            � <?= date('g:i A', strtotime($event['start_time'])) ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="event-meta mb-2">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= $event['is_online'] ? 'Online Event' : htmlspecialchars($event['venue'] ?: 'Venue TBA') ?>
                                    </p>
                                    <?php if (!empty($event['description'])): ?>
                                        <p class="text-muted small mb-0"><?= htmlspecialchars(substr($event['description'], 0, 120)) ?>...</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Event pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(['event_type' => $event_type, 'timeframe' => $timeframe, 'page' => $page - 1]) ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(['event_type' => $event_type, 'timeframe' => $timeframe, 'page' => $i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(['event_type' => $event_type, 'timeframe' => $timeframe, 'page' => $page + 1]) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onmouseover="this.style.filter='opacity(1)'" onmouseout="this.style.filter='opacity(0.6)'"></button>
                </div>
                <div class="modal-body" id="detailModalBody">
                    Content will be loaded here
                </div>
            </div>
        </div>
    </div>

    <?php include 'include_homepage/footer.php'; ?>

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script>
        function showDetailModal(type, id) {
            $('#detailModalTitle').text('Loading...');
            $('#detailModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#detailModal').modal('show');

            $.post('get_details.php', {type: type, id: id}, function(response) {
                if (response.success) {
                    const data = response.data;
                    let content = '';

                    if (type === 'event') {
                        $('#detailModalTitle').text(data.title);
                        let galleryMarkup = '';
                        try {
                            const parsedGallery = data.gallery ? JSON.parse(data.gallery) : [];
                            const galleryImages = Array.isArray(parsedGallery) ? parsedGallery.filter(Boolean) : [];
                            if (galleryImages.length) {
                                galleryMarkup = `
                                    <div class="mt-4">
                                        <h6 class="mb-2">Gallery</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            ${galleryImages.map(src => `
                                                <div class="gallery-thumb border rounded" style="width: 100px; height: 70px; overflow: hidden;">
                                                    <img src="${src}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='default/default-event.png';" alt="Gallery image">
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                `;
                            }
                        } catch (error) {
                            galleryMarkup = '';
                        }

                        content = `
                            <div class="mb-3">
                                <span class="badge badge-success me-2">${data.event_type}</span>
                                <small class="text-muted">${new Date(data.start_date).toLocaleDateString()}</small>
                            </div>
                            ${data.featured_image ? `<img src="${data.featured_image}" class="img-fluid mb-3 rounded" onerror="this.src='default/default-event.png'">` : ''}
                            <p><i class="fas fa-calendar"></i> <strong>Date:</strong> ${new Date(data.start_date).toLocaleDateString()}</p>
                            ${data.venue ? `<p><i class="fas fa-map-marker-alt"></i> <strong>Venue:</strong> ${data.venue}</p>` : ''}
                            <div class="content">${data.content ? data.content.replace(/\n/g, '<br>') : 'No description available.'}</div>
                            ${galleryMarkup}
                        `;
                    }

                    $('#detailModalBody').html(content);
                } else {
                    $('#detailModalTitle').text('Error');
                    $('#detailModalBody').html('<p class="text-danger">Failed to load details.</p>');
                }
            }, 'json').fail(function() {
                $('#detailModalTitle').text('Error');
                $('#detailModalBody').html('<p class="text-danger">Failed to load details.</p>');
            });
        }
    </script>
</body>
</html>
