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
    <link rel="stylesheet" href="style/navbar.css" />
    <link rel="stylesheet" href="style/alumni-list.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
    <?php include 'include_homepage/navbar.php'; ?>

    <?php if ($search): ?>
    <section id="search-section">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-2">Search Results for "<?= htmlspecialchars($search) ?>"</h2>
                    <p class="text-muted"><?= $total_results ?> result<?= $total_results !== 1 ? 's' : '' ?> found</p>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-pills category-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $category === 'all' ? 'active' : '' ?>" 
                           href="?search=<?= urlencode($search) ?>&category=all">All</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $category === 'alumni' ? 'active' : '' ?>" 
                           href="?search=<?= urlencode($search) ?>&category=alumni"><i class="fas fa-users me-2"></i>Alumni</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $category === 'announcements' ? 'active' : '' ?>" 
                           href="?search=<?= urlencode($search) ?>&category=announcements"><i class="fas fa-bullhorn me-2"></i>Announcements</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $category === 'events' ? 'active' : '' ?>" 
                           href="?search=<?= urlencode($search) ?>&category=events"><i class="fas fa-calendar me-2"></i>Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $category === 'photos' ? 'active' : '' ?>" 
                           href="?search=<?= urlencode($search) ?>&category=photos"><i class="fas fa-images me-2"></i>Photos</a>
                    </li>
                </ul>
                
                
                <div class="row mt-5">
                    <?php foreach ($search_results as $result): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="result-card" onclick="showDetailModal('<?= $result['type'] ?>', <?= $result['id'] ?>)">
                            <div class="result-type">
                                <?php 
                                    $icons = [
                                        'alumni' => 'fas fa-user',
                                        'announcement' => 'fas fa-bullhorn',
                                        'event' => 'fas fa-calendar',
                                        'photo' => 'fas fa-image'
                                    ];
                                    $icon = $icons[$result['type']] ?? 'fas fa-file';
                                ?>
                                <i class="<?= $icon ?> me-1"></i><?= ucfirst($result['type']) ?>
                            </div>
                            <?php 
                            $image_src = getDefaultImage($result['type'], $result['image']);
                            if ($image_src): 
                            ?>
                            <img src="<?= htmlspecialchars($image_src) ?>" class="card-img-top" 
                                 onerror="this.src='<?= getDefaultImage($result['type'], '') ?>'">
                            <?php else: ?>
                            <div class="card-img-top default-image">
                                <i class="fas fa-<?= $result['type'] === 'alumni' ? 'user' : ($result['type'] === 'announcement' ? 'bullhorn' : ($result['type'] === 'event' ? 'calendar' : 'image')) ?> fa-3x"></i>
                            </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($result['title']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($result['description']) ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i><?= date('M j, Y', strtotime($result['date_created'])) ?>
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
     
    <style>
    .hero-watermark{position:relative;overflow:hidden}
    .hero-watermark::before{content:"";position:absolute;inset:0;background-image:url('default/logo.png');background-repeat:no-repeat;background-position:center;background-size:40%;opacity:0.08;pointer-events:none;z-index:0}
    .hero-watermark .container{position:relative;z-index:1}
    /* Center hero content */
    .hero-section{padding:80px 0;display:flex;align-items:center;justify-content:center}
    .hero-section .row{justify-content:center;align-items:center}
    .hero-section .col-lg-8{max-width:900px;text-align:center}
    .hero-section .search-container{max-width:720px;margin:0 auto;position:relative}
    @media (max-width: 992px){
        .hero-section{padding:60px 0}
        .hero-section .col-lg-8{padding:0 20px}
    }

    /* Responsive stats wrap */
    .stats-wrap{display:flex;flex-wrap:wrap;gap:18px;justify-content:center;align-items:stretch;margin:0 -10px}
    .stats-wrap .stat-item{flex:1 1 220px;max-width:260px;padding:18px;margin:10px;background:transparent;display:flex;flex-direction:column;align-items:center;text-align:center}
    .stats-wrap .stat-number{font-size:2rem;font-weight:700}

    /* Larger screens (desktop/laptop) - keep items on a single row when space allows */
    @media (min-width: 1100px){
        .stats-wrap{justify-content:space-between}
        .stats-wrap .stat-item{max-width:23%;flex:1 1 23%}
    }

    /* Tablet and small laptops */
    @media (max-width: 1099px) and (min-width: 768px){
        .stats-wrap .stat-item{flex:1 1 40%;max-width:45%}
    }

    /* Mobile */
    @media (max-width: 767px){
        .stats-wrap{gap:12px}
        .stats-wrap .stat-item{flex:1 1 100%;max-width:100%}
    }

    /* Force single-line layout on tablets/laptops and larger */
    @media (min-width: 768px){
        .stats-wrap{flex-wrap:nowrap;justify-content:space-between}
        .stats-wrap .stat-item{flex:1 1 25%;max-width:25%;margin:0 8px}
    }
    </style>

    <section class="hero-section hero-watermark" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">Sandigan Colleges Incorporated</h1>
                    <h2 class="h3 mb-4" style="font-weight: 300; letter-spacing: 0.5px;">Explore the Alumni Network of SCI</h2>
                    <div class="mt-5 pt-2">
                        <form method="GET" class="search-container" action="">
                            <input type="text" name="search" class="search-input" placeholder="Search alumni, announcements, events, and photos..." value="">
                            <button type="submit" style="position: absolute; right: 12px; top: 12px; border: none; background: none; cursor: pointer; color: #1f6d3a; font-size: 1.2rem;">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <div class="stats-wrap">
                <div class="stat-item">
                    <div class="stat-number"><?= fetchRow("SELECT COUNT(*) as count FROM alumni WHERE is_active = 1")['count'] ?></div>
                    <div class="stat-label">Alumni Registered</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= fetchRow("SELECT COUNT(*) as count FROM colleges WHERE is_active = 1")['count'] ?></div>
                    <div class="stat-label">Colleges</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= fetchRow("SELECT COUNT(*) as count FROM programs WHERE is_active = 1")['count'] ?></div>
                    <div class="stat-label">Programs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= fetchRow("SELECT COUNT(*) as count FROM batches")['count'] ?></div>
                    <div class="stat-label">Graduation Batches</div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" id="alumni">
        <div class="container">
            <h2 class="section-title">Recently Joined Alumni</h2>
            <div class="row mt-5">
                <?php foreach ($recent_alumni as $alumni): ?>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="alumni-card" onclick="showDetailModal('alumni', <?= $alumni['id'] ?>)">
                        <img src="<?= getDefaultImage('alumni', $alumni['profile_picture']) ?>" 
                             alt="<?= htmlspecialchars($alumni['name']) ?>" class="alumni-avatar"
                             onerror="this.src='default/default-alumni.png'">
                        <h5><?= htmlspecialchars($alumni['name']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($alumni['program']) ?></p>
                        <p class="text-muted small"><?= htmlspecialchars($alumni['college']) ?></p>
                        <span class="badge">Batch <?= $alumni['batch_year'] ?></span>
                        <?php if ($alumni['bio']): ?>
                        <p class="mt-3 small" style="color: #6c757d; line-height: 1.5;"><?= htmlspecialchars(substr($alumni['bio'], 0, 80)) ?>...</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: 1px solid rgba(31, 109, 58, 0.1); overflow: hidden;  box-shadow: 0 20px 60px rgba(31, 109, 58, 0.2);">
                <div class="modal-header" style="border-bottom: 2px solid rgba(31, 109, 58, 0.15); padding: 24px; background: linear-gradient(135deg, rgba(31, 109, 58, 0.08) 0%, rgba(11, 60, 38, 0.06) 100%); position: relative;">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--brand-green), var(--brand-gold));"></div>
                    <h5 class="modal-title" id="detailModalTitle" style="color: var(--brand-dark); font-weight: 600; margin-top: 3px;"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: opacity(0.6); transition: all 0.3s ease;" onmouseover="this.style.filter='opacity(1)'" onmouseout="this.style.filter='opacity(0.6)'"></button>
                </div>
                <div class="modal-body" id="detailModalBody" style="padding: 24px;">
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
        // Enhanced smooth scrolling for navigation links
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

        // Show detail modal function with better UX
        function showDetailModal(type, id) {
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            $('#detailModalTitle').text('Loading...');
            $('#detailModalBody').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x" style="color: #1f6d3a;"></i></div>');
            modal.show();
            
            // Fetch details based on type
            $.post('get_details.php', {type: type, id: id}, function(response) {
                if (response.success) {
                    const data = response.data;
                    let content = '';
                    
                    switch(type) {
                        case 'alumni':
                            $('#detailModalTitle').html(`
                                <div>
                                    <h5 style="margin: 0; color: #2d3748;">${data.name}</h5>
                                    <small class="text-muted">Alumni Profile</small>
                                </div>
                            `);
                            content = `
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <img src="${data.profile_picture || 'default/default-alumni.png'}" 
                                             class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #f0f0f0; box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);"
                                             onerror="this.src='default/default-alumni.png'">
                                        <h5 style="color: #2d3748; margin-top: 15px;">${data.name}</h5>
                                        <p class="text-muted mb-2">${data.program}</p>
                                        <span class="badge" style="background: linear-gradient(135deg, #1f6d3a 0%, #0b3c26 100%); padding: 8px 12px; border-radius: 20px; color: white;">Batch ${data.batch_year}</span>
                                    </div>
                                    <div class="col-md-8">
                                        <h6 style="color: #2d3748; font-weight: 600; margin-bottom: 15px;">Contact Information</h6>
                                        <p style="margin-bottom: 10px;"><i class="fas fa-envelope" style="color: #1f6d3a; width: 20px;"></i> ${data.email || 'Not provided'}</p>
                                        <p style="margin-bottom: 10px;"><i class="fas fa-phone" style="color: #1f6d3a; width: 20px;"></i> ${data.phone || 'Not provided'}</p>
                                        <p style="margin-bottom: 15px;"><i class="fas fa-map-marker-alt" style="color: #1f6d3a; width: 20px;"></i> ${data.present_address || 'Not provided'}</p>
                                        
                                        ${data.bio ? `<h6 style="color: #2d3748; font-weight: 600; margin-top: 15px; margin-bottom: 10px;">Bio</h6><p>${data.bio}</p>` : ''}
                                        
                                        <h6 style="color: #2d3748; font-weight: 600; margin-top: 15px; margin-bottom: 10px;">Academic Information</h6>
                                        <p style="margin-bottom: 5px;"><strong>College:</strong> ${data.college}</p>
                                        <p style="margin-bottom: 5px;"><strong>Program:</strong> ${data.program}</p>
                                        <p><strong>Graduation:</strong> ${data.batch_year} - ${data.semester} Semester</p>
                                    </div>
                                </div>
                            `;
                            break;
                            
                        case 'announcement':
                            $('#detailModalTitle').html(`
                                <div>
                                    <h5 style="margin: 0; color: #2d3748;">${data.title}</h5>
                                    <small class="text-muted">Announcement</small>
                                </div>
                            `);
                            content = `
                                <div class="mb-3">
                                    <span class="badge me-2" style="background: linear-gradient(135deg, #1f6d3a 0%, #0b3c26 100%); padding: 6px 12px; border-radius: 20px; color: white;">${data.announcement_type}</span>
                                    <span class="badge" style="background: ${data.priority === 'High' ? '#fbbf24' : (data.priority === 'Critical' ? '#ff0000' : '#1f6d3a')}; padding: 6px 12px; border-radius: 20px; color: white;">${data.priority}</span>
                                    <small class="text-muted ms-2"><i class="fas fa-calendar-alt me-1"></i>${new Date(data.created_at).toLocaleDateString()}</small>
                                </div>
                                ${data.featured_image ? `<img src="${data.featured_image}" class="img-fluid mb-3 rounded" style="max-height: 300px; object-fit: cover;" onerror="this.src='default/default-announcement.png'">` : ''}
                                <div class="content" style="line-height: 1.8; color: #2d3748;">${data.content.replace(/\n/g, '<br>')}</div>
                            `;
                            break;
                            
                        case 'event':
                            $('#detailModalTitle').html(`
                                <div>
                                    <h5 style="margin: 0; color: #2d3748;">${data.title}</h5>
                                    <small class="text-muted">Event</small>
                                </div>
                            `);
                            content = `
                                <div class="mb-3">
                                    <span class="badge me-2" style="background: linear-gradient(135deg, #1f6d3a 0%, #0b3c26 100%); padding: 6px 12px; border-radius: 20px; color: white;">${data.event_type}</span>
                                    <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i>${new Date(data.start_date).toLocaleDateString()}</small>
                                </div>
                                ${data.featured_image ? `<img src="${data.featured_image}" class="img-fluid mb-3 rounded" style="max-height: 300px; object-fit: cover;" onerror="this.src='default/default-event.png'">` : ''}
                                <p style="margin-bottom: 10px;"><i class="fas fa-calendar" style="color: #1f6d3a; margin-right: 10px;"></i> <strong>Date:</strong> ${new Date(data.start_date).toLocaleDateString()}</p>
                                ${data.venue ? `<p style="margin-bottom: 15px;"><i class="fas fa-map-marker-alt" style="color: #1f6d3a; margin-right: 10px;"></i> <strong>Venue:</strong> ${data.venue}</p>` : ''}
                                <div class="content" style="line-height: 1.8; color: #2d3748;">${data.content ? data.content.replace(/\n/g, '<br>') : 'No description available.'}</div>
                            `;
                            break;
                            
                        case 'photo':
                            $('#detailModalTitle').html(`
                                <div>
                                    <h5 style="margin: 0; color: #2d3748;">${data.title || 'Photo'}</h5>
                                    <small class="text-muted">Photo</small>
                                </div>
                            `);
                            content = `
                                <div class="text-center mb-3">
                                    <img src="${data.file_path}" class="img-fluid rounded" style="max-height: 400px; object-fit: cover;"
                                         onerror="this.src='default/default-photo.png'">
                                </div>
                                <p style="margin-bottom: 10px;"><strong>Album:</strong> ${data.album_title}</p>
                                ${data.description ? `<p style="margin-bottom: 10px;"><strong>Description:</strong> ${data.description}</p>` : ''}
                                ${data.caption ? `<p style="margin-bottom: 10px;"><strong>Caption:</strong> ${data.caption}</p>` : ''}
                                <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i>Uploaded: ${new Date(data.upload_date).toLocaleDateString()}</small>
                            `;
                            break;
                    }
                    
                    $('#detailModalBody').html(content);
                } else {
                    $('#detailModalTitle').text('Error');
                    $('#detailModalBody').html('<p class="text-danger"><i class="fas fa-exclamation-circle me-2"></i>Failed to load details.</p>');
                }
            }, 'json').fail(function() {
                $('#detailModalTitle').text('Error');
                $('#detailModalBody').html('<p class="text-danger"><i class="fas fa-exclamation-circle me-2"></i>Failed to load details.</p>');
            });
        }
    </script>
</body>
</html>