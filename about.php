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
    <link rel="stylesheet" href="style/main.css" />
    <link rel="stylesheet" href="style/navbar.css" />
    <link rel="stylesheet" href="style/about.css" />
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
                    <div class="content-card h-100 mvg-card">
                        <div class="mvg-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h2 class="section-title mt-3">Vision</h2>
                        <p class="mt-3 muted">To be a leading educational institution that inspires innovation, fosters ethical leadership, and transforms lives through quality education and community engagement. We aim to create an environment that exemplifies unity and excellence, shaping future leaders who are committed to loyalty and service.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="content-card h-100 mvg-card">
                        <div class="mvg-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h2 class="section-title mt-3">Mission</h2>
                        <p class="mt-3 muted">Sandigan Colleges, Inc. is dedicated to providing accessible, holistic, and high-quality education that nurtures intellectual curiosity, critical thinking, and strong moral values.</p>
                    </div>
                </div>
            </div>
            <div class="row g-4 mt-2">
                <div class="col-12">
                    <div class="content-card h-100 mvg-card">
                        <div class="mvg-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h2 class="section-title mt-3">Goal & Objectives</h2>
                        <div class="goals-grid mt-4">
                            <div class="goal-item">
                                <div class="goal-number">01</div>
                                <h6>Deliver Academic Excellence</h6>
                                <p>Offer a well-rounded curriculum that promotes academic rigor, practical application, and moral development, while encouraging unity within the academic community.</p>
                            </div>
                            <div class="goal-item">
                                <div class="goal-number">02</div>
                                <h6>Foster Holistic Growth</h6>
                                <p>Create an environment that nurtures intellectual, emotional, social, and spiritual well-being, grounded in the core values of integrity, loyalty, and service.</p>
                            </div>
                            <div class="goal-item">
                                <div class="goal-number">03</div>
                                <h6>Promote Lifelong Learning</h6>
                                <p>Instill a passion for continuous learning and adaptability, preparing students to thrive in an ever-changing world and remain loyal to their personal and professional growth.</p>
                            </div>
                            <div class="goal-item">
                                <div class="goal-number">04</div>
                                <h6>Engage the Community</h6>
                                <p>Build strong partnerships with stakeholders to address societal challenges, foster collaboration, and contribute to the community through service-driven initiatives.</p>
                            </div>
                            <div class="goal-item">
                                <div class="goal-number">05</div>
                                <h6>Champion Innovation and Leadership</h6>
                                <p>Develop globally competitive graduates who embody leadership, creativity, and ethical responsibility, upholding the principles of excellence and loyalty.</p>
                            </div>
                            <div class="goal-item">
                                <div class="goal-number">06</div>
                                <h6>Sustain Intellectual Growth</h6>
                                <p>Invest in institutional resources, faculty development, and infrastructure to ensure sustainable programs that promote excellence and service for future generations.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
        <section class="py-5">
        <div class="container">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-7">
                    <div class="content-card h-100 president-card">
                        <div class="president-header">
                            <span class="pill">Message from Leadership</span>
                            <h2 class="section-title mt-3 mb-0">President's Message</h2>
                        </div>
                        <div class="president-content">
                            <p class="muted lead-copy"><strong>Sandigan Colleges, Inc.</strong>, believes that education is the cornerstone of personal growth and societal transformation.</p>
                            <p class="muted">By fostering a culture grounded in unity, integrity, and lifelong learning, we empower individuals to reach their fullest potential. Encouraging students to contribute meaningfully to the world while upholding the values of loyalty and service in both their personal and professional lives.</p>
                            <div class="president-signature mt-4">
                                <strong>Dr. [President Name]</strong><br>
                                <span class="text-muted">President, Sandigan Colleges, Inc.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="image-frame president-image">
                        <img src="/placeholder.svg?height=520&width=520" alt="President" onerror="this.src='default/presidentpic.png'">
                        <div class="image-overlay"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section py-5">
        <div class="container">
            <h2 class="section-title mb-5">Our Impact in Numbers</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?= $alumni_count ?></div>
                        <div class="stat-label">Alumni Registered</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-number"><?= $colleges_count ?></div>
                        <div class="stat-label">Colleges</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-number"><?= $programs_count ?></div>
                        <div class="stat-label">Academic Programs</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card-modern">
                        <div class="stat-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
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
            <h2 class="section-title mb-5">Our Core Values</h2>
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card feature-card-modern">
                        <div class="feature-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h5>Integrity</h5>
                        <p>We uphold honesty, transparency, and ethical conduct in all our endeavors.</p>
                        <div class="feature-accent"></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card feature-card-modern">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h5>Excellence</h5>
                        <p>We strive for the highest standards in education, research, and service delivery.</p>
                        <div class="feature-accent"></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card feature-card-modern">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>Inclusivity</h5>
                        <p>We embrace diversity and provide equal opportunities for all members of our community.</p>
                        <div class="feature-accent"></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card feature-card-modern">
                        <div class="feature-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h5>Innovation</h5>
                        <p>We foster creativity and encourage innovative approaches to teaching and learning.</p>
                        <div class="feature-accent"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Achievements -->
    <?php if (!empty($recent_highlights)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title mb-5">Recent Achievements</h2>
            <div class="row g-4">
                <?php foreach ($recent_highlights as $highlight): ?>
                <div class="col-md-6 col-lg-4 mb-2">
                    <div class="highlight-card achievement-card">
                        <div class="achievement-image-wrapper">
                            <?php if ($highlight['featured_image']): ?>
                            <img src="<?= htmlspecialchars($highlight['featured_image']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;"
                                 onerror="this.src='default/default-achievement.png'">
                            <?php endif; ?>
                            <div class="achievement-overlay"></div>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="card-title"><?= htmlspecialchars($highlight['title']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars(substr($highlight['content'], 0, 120)) ?>...</p>
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i><?= date('M j, Y', strtotime($highlight['created_at'])) ?>
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
            <h2 class="section-title mb-5">Get in Touch</h2>
            <div class="row g-4">
                <div class="col-md-6 mb-4">
                    <div class="feature-card contact-card">
                        <div class="contact-section">
                            <div class="contact-icon-large">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h5>Main Campus</h5>
                            <p class="text-muted"><br></p>
                        </div>
                        
                        <div class="contact-section">
                            <div class="contact-icon-large">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h5>Contact Numbers</h5>
                            <p class="text-muted"><br>Mobile: +63 917-XXX-XXXX</p>
                        </div>
                        
                        <div class="contact-section">
                            <div class="contact-icon-large">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h5>Email Address</h5>
                            <p class="text-muted">info@SCI.edu.ph<br>alumni@SCI.edu.ph</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="feature-card contact-card">
                        <div class="contact-section">
                            <div class="contact-icon-large">
                                <i class="fas fa-globe"></i>
                            </div>
                            <h5>Online Presence</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="#" class="btn btn-outline-primary btn-sm">
                                    <i class="fab fa-facebook-f"></i> Facebook
                                </a>
                                <a href="#" class="btn btn-outline-info btn-sm">
                                    <i class="fab fa-twitter"></i> Twitter
                                </a>
                            </div>
                        </div>
                        
                        <div class="contact-section">
                            <div class="contact-icon-large">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h5>Office Hours</h5>
                            <p class="text-muted">Monday - Friday: 8:00 AM - 5:00 PM<br>Saturday: 8:00 AM - 12:00 PM<br>Sunday: Closed</p>
                        </div>
                        
                        <div class="contact-section">
                            <div class="contact-icon-large">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h5>Alumni Relations</h5>
                            <p class="text-muted">For alumni-related inquiries, please contact our Alumni Relations Office during regular business hours.</p>
                        </div>
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