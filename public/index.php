<!-- index.php -->
<?php
    require_once __DIR__ . '/../config/init.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Fluency Course - Learn AI Fundamentals</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header class="main-header">
        <nav class="nav-container">
            <div class="logo">AI Fluency LMS</div>
            <div class="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="cta-button"><span class="nav-cta">Login</span></a></li>
                    <li><a href="register.php" class="cta-button "><span class="nav-cta">Get Started</span></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h1>Master AI Fundamentals</h1>
                <p>Learn everything you need to know about AI through our comprehensive course with 44 bite-sized videos.</p>
                <a href="register.php" class="cta-button">Start Learning Now</a>
            </div>
        </section>

        <section class="course-modules">
            <h2>Course Modules</h2>
            <div class="module-grid">
                <?php
                require_once '../config/init.php';
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT * FROM modules ORDER BY order_num");
                while ($module = $stmt->fetch()) {
                    echo '<div class="module-card">';
                    echo '<h3>' . htmlspecialchars($module['title']) . '</h3>';
                    echo '<p>' . htmlspecialchars($module['description']) . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </section>

        <section class="features">
            <h2>What You'll Get</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📹</div>
                    <h3>2.5 Hours of Video Content</h3>
                    <p>44 carefully crafted videos breaking down complex AI concepts.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3>Interactive Quizzes</h3>
                    <p>Test your knowledge with comprehensive quizzes after each module.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3>Supplementary Materials</h3>
                    <p>Access transcripts, captions, and audio descriptions.</p>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <h2>Ready to Begin Your AI Journey?</h2>
            <p>Join thousands of students learning AI fundamentals.</p>
            <a href="register.php" class="cta-button">Enroll Now</a>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>About Us</h4>
                <p>Dedicated to making AI education accessible to everyone.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 AI Fluency LMS. All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>