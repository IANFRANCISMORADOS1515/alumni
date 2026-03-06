<?php
require_once 'db_con.php';

// Handle search
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';
$page = max(1, $_GET['page'] ?? 1);
$limit = 12;
$offset = ($page - 1) * $limit;

// Initialize search results
$search_results = [];
$total_results = 0;

if ($search) {
    $search_term = "%$search%";
    
    // Build search queries based on category
    if ($category === 'all' || $category === 'alumni') {
        $alumni_query = "
            SELECT 'alumni' as type, a.id, 
                   CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) as title,
                   CONCAT(c.name, ' - ', p.name, ' (Batch ', b.year, ')') as description,
                   a.profile_picture as image,
                   a.created_at as date_created,
                   a.email, a.phone, a.bio, a.present_address,
                   c.name as college, p.name as program, b.year as batch_year, b.semester
            FROM alumni a
            JOIN colleges c ON a.college_id = c.id
            JOIN programs p ON a.program_id = p.id
            JOIN batches b ON a.batch_id = b.id
            WHERE (a.first_name LIKE ? OR a.middle_name LIKE ? OR a.last_name LIKE ? 
                   OR a.student_id LIKE ? OR a.email LIKE ?)
            AND a.is_active = 1
            ORDER BY a.created_at DESC
        ";
        
        $stmt = $pdo->prepare($alumni_query);
        $stmt->execute([$search_term, $search_term, $search_term, $search_term, $search_term]);
        $alumni_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $search_results = array_merge($search_results, $alumni_results);
    }
    
    if ($category === 'all' || $category === 'announcements') {
        $announcement_query = "
            SELECT 'announcement' as type, a.id,
                   a.title,
                   CONCAT(a.announcement_type, ' - ', LEFT(COALESCE(a.excerpt, a.content), 150), '...') as description,
                   a.featured_image as image,
                   a.created_at as date_created,
                   a.content, a.announcement_type, a.priority, a.excerpt
            FROM announcements a
            WHERE (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)
            AND a.status = 'Published' AND a.is_public = 1
            AND (a.expires_at IS NULL OR a.expires_at > NOW())
            ORDER BY a.created_at DESC
        ";
        
        $stmt = $pdo->prepare($announcement_query);
        $stmt->execute([$search_term, $search_term, $search_term]);
        $announcement_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $search_results = array_merge($search_results, $announcement_results);
    }
    
    if ($category === 'all' || $category === 'events') {
        $event_query = "
            SELECT 'event' as type, e.id,
                   e.title,
                   CONCAT(e.event_type, ' - ', DATE_FORMAT(e.start_date, '%M %d, %Y')) as description,
                   e.featured_image as image,
                   e.created_at as date_created,
                   e.description as content, e.start_date, e.end_date, e.venue, e.event_type
            FROM events e
            WHERE (e.title LIKE ? OR e.description LIKE ?)
            AND e.status = 'Published'
            ORDER BY e.start_date DESC
        ";
        
        $stmt = $pdo->prepare($event_query);
        $stmt->execute([$search_term, $search_term]);
        $event_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $search_results = array_merge($search_results, $event_results);
    }
    
    if ($category === 'all' || $category === 'photos') {
        $photo_query = "
            SELECT 'photo' as type, p.id,
                   COALESCE(p.title, 'Untitled Photo') as title,
                   CONCAT(pa.title, ' - ', COALESCE(p.description, 'No description')) as description,
                   p.file_path as image,
                   p.upload_date as date_created,
                   p.caption, pa.title as album_title, p.description as content
            FROM photos p
            JOIN photo_albums pa ON p.album_id = pa.id
            WHERE (p.title LIKE ? OR p.description LIKE ? OR p.caption LIKE ? OR pa.title LIKE ?)
            AND pa.is_public = 1
            ORDER BY p.upload_date DESC
        ";
        
        $stmt = $pdo->prepare($photo_query);
        $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
        $photo_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $search_results = array_merge($search_results, $photo_results);
    }
    
    // Sort all results by date and paginate
    usort($search_results, function($a, $b) {
        return strtotime($b['date_created']) - strtotime($a['date_created']);
    });
    
    $total_results = count($search_results);
    $search_results = array_slice($search_results, $offset, $limit);
}

// Get recent content for homepage
if (!$search) {
    $recent_alumni = fetchAll("
        SELECT a.id, CONCAT(a.first_name, ' ', a.last_name) as name, 
               c.name as college, p.name as program, b.year as batch_year,
               a.profile_picture, a.bio, a.email, a.phone, a.present_address,
               b.semester
        FROM alumni a
        JOIN colleges c ON a.college_id = c.id
        JOIN programs p ON a.program_id = p.id
        JOIN batches b ON a.batch_id = b.id
        WHERE a.is_active = 1
        ORDER BY a.created_at DESC
        LIMIT 8
    ");

    $recent_announcements = fetchAll("
        SELECT id, title, excerpt, announcement_type, created_at, featured_image, content, priority
        FROM announcements
        WHERE status = 'Published' AND is_public = 1
        AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY created_at DESC
        LIMIT 6
    ");

    $recent_events = fetchAll("
        SELECT id, title, description, event_type, start_date, end_date, featured_image, venue
        FROM events
        WHERE status = 'Published'
        ORDER BY start_date DESC
        LIMIT 6
    ");

    $recent_photos = fetchAll("
        SELECT p.id, p.title, p.file_path, pa.title as album_title, p.description, p.caption, p.upload_date
        FROM photos p
        JOIN photo_albums pa ON p.album_id = pa.id
        WHERE pa.is_public = 1
        ORDER BY p.upload_date DESC
        LIMIT 8
    ");
}

$total_pages = ceil($total_results / $limit);

// Function to get default image
function getDefaultImage($type, $image) {
    if (empty($image) || !file_exists($image)) {
        switch($type) {
            case 'alumni':
                return 'default/default-alumni.png';
            case 'announcement':
                return 'default/default-announcement.png';
            case 'event':
                return 'default/default-event.png';
            case 'photo':
                return 'default/default-photo.png';
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
    <title>Sandigan Colleges Incorporated Alumni System</title>
    <link rel="icon" href="default/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="style/main.css" />
    <style>
        .hero-section.hero-watermark {
            position: relative;
            overflow: hidden;
        }
        .hero-section.hero-watermark::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url('default/logo.png') center/contain no-repeat;
            background-size: 55%;
            opacity: 0.12;
            pointer-events: none;
        }
        .hero-section.hero-watermark .container {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <?php include 'include_homepage/navbar.php'; ?>

    <?php if ($search): ?>
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Search Results for "<?= htmlspecialchars($search) ?>"</h2>
                <p class="text-muted"><?= $total_results ?> results found</p>
                
                
                <ul class="nav nav-pills category-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?= $category === 'all' ? 'active' : '' ?>" 
                           href="?search=<?= urlencode($search) ?>&category=all">All</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $category === 'alumni' ? 'active' : '' ?>" 
                           href="?search=<?= urlencode($search) ?>&category=alumni">Alumni</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $category === 'announcements' ? 'active' : '' ?>" 
                           href="?search=<?= urlencode($search) ?>&category=announcements">Announcements</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $category === 'events' ? 'active' : '' ?>" 
                           href="?search=<?= urlencode($search) ?>&category=events">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $category === 'photos' ? 'active' : '' ?>" 
                           href="?search=<?= urlencode($search) ?>&category=photos">Photos</a>
                    </li>
                </ul>
                
                
                <div class="row">
                    <?php foreach ($search_results as $result): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card result-card h-100" onclick="showDetailModal('<?= $result['type'] ?>', <?= $result['id'] ?>)">
                            <div class="result-type type-<?= $result['type'] ?>">
                                <?= ucfirst($result['type']) ?>
                            </div>
                            <?php 
                            $image_src = getDefaultImage($result['type'], $result['image']);
                            if ($image_src): 
                            ?>
                            <img src="<?= htmlspecialchars($image_src) ?>" class="card-img-top" style="height: 200px; object-fit: cover;" 
                                 onerror="this.src='<?= getDefaultImage($result['type'], '') ?>'">
                            <?php else: ?>
                            <div class="card-img-top default-image" style="height: 200px;">
                                <i class="fas fa-<?= $result['type'] === 'alumni' ? 'user' : ($result['type'] === 'announcement' ? 'bullhorn' : ($result['type'] === 'event' ? 'calendar' : 'image')) ?> fa-3x"></i>
                            </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($result['title']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($result['description']) ?></p>
                                <small class="text-muted">
                                    <?= date('M j, Y', strtotime($result['date_created'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Search results pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?search=<?= urlencode($search) ?>&category=<?= $category ?>&page=<?= $page - 1 ?>">Previous</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?search=<?= urlencode($search) ?>&category=<?= $category ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?search=<?= urlencode($search) ?>&category=<?= $category ?>&page=<?= $page + 1 ?>">Next</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
     
    <section class="hero-section hero-watermark" id="home">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Sandigan Colleges Incorporated</h1>
            <h2 class="h3 mb-4">Look at the Alumni of SCI</h2>
        
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <div class="stats-wrap">
                <div class="row">
                    <div class="col-6 col-lg-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= fetchRow("SELECT COUNT(*) as count FROM alumni WHERE is_active = 1")['count'] ?></div>
                            <div class="stat-label">Alumni Registered</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= fetchRow("SELECT COUNT(*) as count FROM colleges WHERE is_active = 1")['count'] ?></div>
                            <div class="stat-label">Colleges</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= fetchRow("SELECT COUNT(*) as count FROM programs WHERE is_active = 1")['count'] ?></div>
                            <div class="stat-label">Programs</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= fetchRow("SELECT COUNT(*) as count FROM batches")['count'] ?></div>
                            <div class="stat-label">Graduation Batches</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" id="alumni">
        <div class="container">
            <h2 class="section-title">Recent Alumni</h2>
            <div class="row">
                <?php foreach ($recent_alumni as $alumni): ?>
                <div class="col-md-3 mb-4">
                    <div class="alumni-card" onclick="showDetailModal('alumni', <?= $alumni['id'] ?>)">
                        <img src="<?= getDefaultImage('alumni', $alumni['profile_picture']) ?>" 
                             alt="<?= htmlspecialchars($alumni['name']) ?>" class="alumni-avatar"
                             onerror="this.src='default/default-alumni.png'">
                        <h5><?= htmlspecialchars($alumni['name']) ?></h5>
                        <p class="text-muted small"><?= htmlspecialchars($alumni['program']) ?></p>
                        <p class="text-muted small"><?= htmlspecialchars($alumni['college']) ?></p>
                        <span class="badge badge-primary">Batch <?= $alumni['batch_year'] ?></span>
                        <?php if ($alumni['bio']): ?>
                        <p class="mt-2 small"><?= htmlspecialchars(substr($alumni['bio'], 0, 80)) ?>...</p>
                        <?php endif; ?>
                            <svg class="card-border" viewBox="0 0 100 100" preserveAspectRatio="none">
                                <rect x="2" y="2" width="96" height="96" rx="12" ry="12"/>
                            </svg>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Show detail modal function
        function showDetailModal(type, id) {
            $('#detailModalTitle').text('Loading...');
            $('#detailModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#detailModal').modal('show');
            
            // Fetch details based on type
            $.post('get_details.php', {type: type, id: id}, function(response) {
                if (response.success) {
                    const data = response.data;
                    let content = '';
                    
                    switch(type) {
                        case 'alumni':
                            $('#detailModalTitle').text(data.name);
                            content = `
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <img src="${data.profile_picture || 'default/default-alumni.png'}" 
                                             class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;"
                                             onerror="this.src='default/default-alumni.png'">
                                        <h5>${data.name}</h5>
                                        <p class="text-muted">${data.program}</p>
                                        <span class="badge badge-primary">Batch ${data.batch_year}</span>
                                    </div>
                                    <div class="col-md-8">
                                        <h6>Contact Information</h6>
                                        <p><i class="fas fa-envelope"></i> ${data.email || 'Not provided'}</p>
                                        <p><i class="fas fa-phone"></i> ${data.phone || 'Not provided'}</p>
                                        <p><i class="fas fa-map-marker-alt"></i> ${data.present_address || 'Not provided'}</p>
                                        
                                        ${data.bio ? `<h6 class="mt-3">Bio</h6><p>${data.bio}</p>` : ''}
                                        
                                        <h6 class="mt-3">Academic Information</h6>
                                        <p><strong>College:</strong> ${data.college}</p>
                                        <p><strong>Program:</strong> ${data.program}</p>
                                        <p><strong>Graduation:</strong> ${data.batch_year} - ${data.semester} Semester</p>
                                    </div>
                                </div>
                            `;
                            break;
                            
                        case 'announcement':
                            $('#detailModalTitle').text(data.title);
                            content = `
                                <div class="mb-3">
                                    <span class="badge badge-info me-2">${data.announcement_type}</span>
                                    <span class="badge badge-${data.priority === 'High' ? 'warning' : (data.priority === 'Critical' ? 'danger' : 'primary')}">${data.priority}</span>
                                    <small class="text-muted ms-2">${new Date(data.created_at).toLocaleDateString()}</small>
                                </div>
                                ${data.featured_image ? `<img src="${data.featured_image}" class="img-fluid mb-3 rounded" onerror="this.src='default/default-announcement.png'">` : ''}
                                <div class="content">${data.content.replace(/\n/g, '<br>')}</div>
                            `;
                            break;
                            
                        case 'event':
                            $('#detailModalTitle').text(data.title);
                            content = `
                                <div class="mb-3">
                                    <span class="badge badge-success me-2">${data.event_type}</span>
                                    <small class="text-muted">${new Date(data.start_date).toLocaleDateString()}</small>
                                </div>
                                ${data.featured_image ? `<img src="${data.featured_image}" class="img-fluid mb-3 rounded" onerror="this.src='default/default-event.png'">` : ''}
                                <p><i class="fas fa-calendar"></i> <strong>Date:</strong> ${new Date(data.start_date).toLocaleDateString()}</p>
                                ${data.venue ? `<p><i class="fas fa-map-marker-alt"></i> <strong>Venue:</strong> ${data.venue}</p>` : ''}
                                <div class="content">${data.content ? data.content.replace(/\n/g, '<br>') : 'No description available.'}</div>
                            `;
                            break;
                            
                        case 'photo':
                            $('#detailModalTitle').text(data.title || 'Photo');
                            content = `
                                <div class="text-center mb-3">
                                    <img src="${data.file_path}" class="img-fluid rounded" style="max-height: 400px;"
                                         onerror="this.src='default/default-photo.png'">
                                </div>
                                <p><strong>Album:</strong> ${data.album_title}</p>
                                ${data.description ? `<p><strong>Description:</strong> ${data.description}</p>` : ''}
                                ${data.caption ? `<p><strong>Caption:</strong> ${data.caption}</p>` : ''}
                                <small class="text-muted">Uploaded: ${new Date(data.upload_date).toLocaleDateString()}</small>
                            `;
                            break;
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
