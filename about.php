<?php
require_once 'db_con.php';

// Get statistics for the about page
$alumni_count = fetchRow("SELECT COUNT(*) as count FROM alumni WHERE is_active = 1")['count'];
$colleges_count = fetchRow("SELECT COUNT(*) as count FROM colleges WHERE is_active = 1")['count'];
$programs_count = fetchRow("SELECT COUNT(*) as count FROM programs WHERE is_active = 1")['count'];
$batches_count = fetchRow("SELECT COUNT(*) as count FROM batches")['count'];

// Get recent achievements or highlights (you can modify this query based on your database structure)
$recent_highlights = fetchAll("
    SELECT title, content, created_at, featured_image 
    FROM announcements 
    WHERE announcement_type = 'Achievement' 
    AND status = 'Published' 
    AND is_public = 1 
    ORDER BY created_at DESC 
    LIMIT 3
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - SCI Alumni System</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="default/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="style/main.css"/>
</head>
<body>
    <?php include 'include_homepage/navbar.php'; ?>
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

    <!-- Hero Section -->
    <section class="hero-section hero-watermark" id="home">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">About Sandigan Colleges, Inc.</h1>
            <p class="lead mb-0">Excellence in Education, Innovation, and Service</p>
        </div>
    </section>

    <!-- Mission, Vision, Goals Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="content-card h-100">
                        <h2 class="section-title mt-3">Vision</h2>
                        <p class="mt-3 muted">To be a leading educational institution that inspires innovation, fosters ethical leadership, and transforms lives through quality education and community engagement. We aim to create an environment that exemplifies unity and excellence, shaping future leaders who are committed to loyalty and service.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="content-card h-100">
                        <h2 class="section-title mt-3">Mission</h2>
                        <p class="mt-3 muted">Sandigan Colleges, Inc. is dedicated to providing accessible, holistic, and high-quality education that nurtures intellectual curiosity, critical thinking, and strong moral values.</p>
                    </div>
                </div>
            </div>
            <div class="row g-4 mt-1">
                <div class="col-12">
                    <div class="content-card h-100">
                        <h2 class="section-title mt-3">Goal & Objectives</h2>
                        <p class="mt-3 muted">Deliver Academic Excellence: Offer a well-rounded curriculum that promotes academic rigor, practical application, and moral development, while encouraging unity within the academic community.</p>
                        <p class="muted">Foster Holistic Growth: Create an environment that nurtures intellectual, emotional, social, and spiritual well-being, grounded in the core values of integrity, loyalty, and service.</p>
                        <p class="muted">Promote Lifelong Learning: Instill a passion for continuous learning and adaptability, preparing students to thrive in an ever-changing world and remain loyal to their personal and professional growth.</p>
                        <p class="muted">Engage the Community: Build strong partnerships with stakeholders to address societal challenges, foster collaboration, and contribute to the community through service-driven initiatives.</p>
                        <p class="muted">Champion Innovation and Leadership: Develop globally competitive graduates who embody leadership, creativity, and ethical responsibility, upholding the principles of excellence and loyalty.</p>
                        <p class="muted mb-0">Sustain Intellectual Growth: Invest in institutional resources, faculty development, and infrastructure to ensure sustainable programs that promote excellence and service for future generations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
        <section class="py-5">
        <div class="container">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-7">
                    <div class="content-card h-100">
                        <span class="pill">Quick Word From our President!</span>
                        <h2 class="section-title mt-3">President Message</h2>
                        <p class="muted">Sandigan Colleges, Inc., believes that education is the cornerstone of personal growth and societal transformation. By fostering a culture grounded in unity, integrity, and lifelong learning, we empower individuals to reach their fullest potential. Encouraging students to contribute meaningfully to the world while upholding the values of loyalty and service in both their personal and professional lives.</p>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="image-frame">
                        <img src="/placeholder.svg?height=520&width=520" alt="President" onerror="this.src='default/presidentpic.png'">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <h2 class="section-title">Our Impact in Numbers</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $alumni_count ?></div>
                        <div class="stat-label">Alumni Registered</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $colleges_count ?></div>
                        <div class="stat-label">Colleges</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $programs_count ?></div>
                        <div class="stat-label">Academic Programs</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= $batches_count ?></div>
                        <div class="stat-label">Graduation Batches</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- University History -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="section-title text-start"></h2>
                    <div class="history-timeline">
                        <div class="timeline-item">
                            <div class="timeline-year"></div>
                            <h5>testing</h5>
                            <p>testing</p>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year"></div>
                            <h5>testing</h5>
                            <p>testing</p>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year"></div>
                            <h5>testing</h5>
                            <p>testing</p>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year"></div>
                            <h5>testing</h5>
                            <p>testing</p>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year"></div>
                            <h5>testing</h5>
                            <p>testing</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <img src="/placeholder.svg?height=400&width=500" alt="SCI Campus" class="img-fluid rounded shadow"
                         onerror="this.src='default/logo.png'">
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values & Features -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">Our Core Values</h2>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h5>Integrity</h5>
                        <p>We uphold honesty, transparency, and ethical conduct in all our endeavors.</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h5>Excellence</h5>
                        <p>We strive for the highest standards in education, research, and service delivery.</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>Inclusivity</h5>
                        <p>We embrace diversity and provide equal opportunities for all members of our community.</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h5>Innovation</h5>
                        <p>We foster creativity and encourage innovative approaches to teaching and learning.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Leadership Team -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">University Leadership</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="team-card">
                        <img src="default/default-admin.jpg" alt="University President" class="team-avatar"
                             onerror="this.src='default/default-president.png'">
                        <h5>Dr. [President Name]</h5>
                        <p class="text-muted">University President</p>
                        <p class="small">Leading the university with vision and dedication to academic excellence and community service.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="team-card">
                        <img src="default/default-admin.jpg" alt="Vice President" class="team-avatar"
                             onerror="this.src='default/default-vp.png'">
                        <h5>Dr. [VP Name]</h5>
                        <p class="text-muted">Vice President for Academic Affairs</p>
                        <p class="small">Overseeing academic programs and ensuring quality education delivery across all campuses.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="team-card">
                        <img src="default/default-admin.jpg" alt="VP Admin" class="team-avatar"
                             onerror="this.src='default/default-admin.png'">
                        <h5>Dr. [Admin VP Name]</h5>
                        <p class="text-muted">Vice President for Administration</p>
                        <p class="small">Managing administrative operations and supporting institutional development initiatives.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Achievements -->
    <?php if (!empty($recent_highlights)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">Recent Achievements</h2>
            <div class="row">
                <?php foreach ($recent_highlights as $highlight): ?>
                <div class="col-md-4 mb-4">
                    <div class="highlight-card">
                        <?php if ($highlight['featured_image']): ?>
                        <img src="<?= htmlspecialchars($highlight['featured_image']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;"
                             onerror="this.src='default/default-achievement.png'">
                        <?php endif; ?>
                        <div class="card-body p-4">
                            <h5 class="card-title"><?= htmlspecialchars($highlight['title']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars(substr($highlight['content'], 0, 120)) ?>...</p>
                            <small class="text-muted">
                                <?= date('M j, Y', strtotime($highlight['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contact Information -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Get in Touch</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="feature-card text-start">
                        <h5><i class="fas fa-map-marker-alt contact-icon me-2"></i>Main Campus</h5>
                        <p><br>
                        </p>
                        
                        <h5 class="mt-4"><i class="fas fa-phone contact-icon me-2"></i>Contact Numbers</h5>
                        <p><br>
                        Mobile: +63 917-XXX-XXXX</p>
                        
                        <h5 class="mt-4"><i class="fas fa-envelope contact-icon me-2"></i>Email Address</h5>
                        <p>info@SCI.edu.ph<br>
                        alumni@SCI.edu.ph</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="feature-card text-start">
                        <h5><i class="fas fa-globe contact-icon me-2"></i>Online Presence</h5>
                        <div class="d-flex mb-3">
                            <a href="#" class="btn btn-outline-primary me-2">
                                <i class="fab fa-facebook"></i> Facebook
                            </a>
                            <a href="#" class="btn btn-outline-info me-2">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                        </div>
                        
                        <h5 class="mt-4"><i class="fas fa-clock contact-icon me-2"></i>Office Hours</h5>
                        <p>Monday - Friday: 8:00 AM - 5:00 PM<br>
                        Saturday: 8:00 AM - 12:00 PM<br>
                        Sunday: Closed</p>
                        
                        <h5 class="mt-4"><i class="fas fa-graduation-cap contact-icon me-2"></i>Alumni Relations</h5>
                        <p>For alumni-related inquiries, please contact our Alumni Relations Office during regular business hours.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'include_homepage/footer.php'; ?>

    <!-- Scripts -->
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

        // Add animation on scroll
        window.addEventListener('scroll', function() {
            const cards = document.querySelectorAll('.feature-card, .team-card, .highlight-card');
            cards.forEach(card => {
                const cardTop = card.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (cardTop < windowHeight * 0.8) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        });

        // Initialize cards with animation-ready state
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.feature-card, .team-card, .highlight-card');
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            });
        });
    </script>
</body>
</html>