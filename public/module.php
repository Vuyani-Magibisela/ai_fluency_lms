<?php
require_once '../config/init.php';
require_once '../classes/Auth.php';
require_once '../classes/Module.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$moduleObj = new Module(Database::getInstance()->getConnection());
$modules = $moduleObj->getAllModules();
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Get progress for each module if user is not admin
$moduleProgress = [];
if (!$isAdmin) {
    foreach ($modules as $module) {
        $moduleProgress[$module['id']] = $moduleObj->getModuleProgress($module['id'], $_SESSION['user_id']);
    }
}

$pageTitle = 'Course Modules';
include '../includes/header.php';
?>

<main class="modules-container">
    <div class="modules-header">
        <h1>Course Modules</h1>
        <?php if ($isAdmin): ?>
            <a href="../public/module-form.php" class="btn btn-primary">Add New Module</a>
        <?php endif; ?>
    </div>

    <div class="modules-grid">
        <?php foreach ($modules as $module): ?>
            <div class="module-card">
                <div class="module-header">
                    <h2 class="module-title">
                        <?php echo htmlspecialchars($module['title']); ?>
                    </h2>
                    <?php if (!$isAdmin && isset($moduleProgress[$module['id']])): ?>
                        <div class="progress-indicator">
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo $moduleProgress[$module['id']]['percentage']; ?>%">
                                </div>
                            </div>
                            <span class="progress-text">
                                <?php echo $moduleProgress[$module['id']]['percentage']; ?>% Complete
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <p class="module-description">
                    <?php echo htmlspecialchars($module['description']); ?>
                </p>

                <div class="module-footer">
                    <?php if ($isAdmin): ?>
                        <div class="admin-controls">
                            <a href="module-form.php?id=<?php echo $module['id']; ?>" class="btn btn-secondary">Edit</a>
                            <button class="btn btn-danger" 
                                    onclick="confirmDelete(<?php echo $module['id']; ?>)">Delete</button>
                        </div>
                    <?php endif; ?>
                    
                    <a href="module-view.php?id=<?php echo $module['id']; ?>" class="btn btn-primary">
                        <?php echo $isAdmin ? 'View Module' : 'Start Learning'; ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php if ($isAdmin): ?>
<script>
function confirmDelete(moduleId) {
    if (confirm('Are you sure you want to delete this module? This action cannot be undone.')) {
        window.location.href = `module-delete.php?id=${moduleId}`;
    }
}
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>