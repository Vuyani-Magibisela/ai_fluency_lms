<?php
require_once '../config/init.php';
require_once '../classes/Auth.php';
require_once '../classes/Dashboard.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$dashboard = new Dashboard(Database::getInstance()->getConnection());
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Get appropriate stats based on user role
if ($isAdmin) {
    $stats = $dashboard->getAdminStats();
    $pageTitle = 'Admin Dashboard';
} else {
    $stats = $dashboard->getUserStats($_SESSION['user_id']);
    $pageTitle = 'Student Dashboard';
}

include '../includes/header.php';
?>

<main class="dashboard-container">
    <div class="dashboard-header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>

    <?php if ($isAdmin): ?>
        <!-- Admin Dashboard -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>User Statistics</h3>
                <div class="stat-group">
                    <div class="stat">
                        <span class="stat-label">Total Users</span>
                        <span class="stat-value"><?php echo $stats['total_users']; ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Active Users (7 days)</span>
                        <span class="stat-value"><?php echo $stats['active_users']; ?></span>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h3>Course Overview</h3>
                <div class="stat-group">
                    <div class="stat">
                        <span class="stat-label">Total Modules</span>
                        <span class="stat-value"><?php echo $stats['total_modules']; ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Average Quiz Score</span>
                        <span class="stat-value"><?php echo $stats['quiz_performance']; ?>%</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-card full-width">
                <h3>Recent Activities</h3>
                <div class="activity-list">
                    <?php foreach ($stats['recent_activities'] as $activity): ?>
                        <div class="activity-item">
                            <span class="user"><?php echo htmlspecialchars($activity['username']); ?></span>
                            <span class="action"><?php echo htmlspecialchars($activity['status']); ?></span>
                            <span class="module"><?php echo htmlspecialchars($activity['module_title']); ?></span>
                            <span class="date"><?php echo date('M d, Y H:i', strtotime($activity['updated_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Student Dashboard -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Your Progress</h3>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo $stats['total_progress']; ?>%">
                        <?php echo $stats['total_progress']; ?>%
                    </div>
                </div>
                <div class="stat-group">
                    <div class="stat">
                        <span class="stat-label">Completed Modules</span>
                        <span class="stat-value"><?php echo $stats['completed_modules']; ?>/<?php echo $stats['total_modules']; ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Quiz Performance</span>
                        <span class="stat-value"><?php echo $stats['quiz_performance']; ?>%</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-card full-width">
                <h3>Recent Activity</h3>
                <div class="activity-list">
                    <?php foreach ($stats['recent_activities'] as $activity): ?>
                        <div class="activity-item">
                            <span class="module"><?php echo htmlspecialchars($activity['module_title']); ?></span>
                            <span class="lesson"><?php echo htmlspecialchars($activity['lesson_title']); ?></span>
                            <span class="status"><?php echo htmlspecialchars($activity['status']); ?></span>
                            <span class="date"><?php echo date('M d, Y H:i', strtotime($activity['updated_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>