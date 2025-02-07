<?php
require_once '../config/init.php';
require_once '../classes/Auth.php';
require_once '../classes/Module.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: modules.php');
    exit();
}

$moduleObj = new Module(Database::getInstance()->getConnection());
$module = null;
$error = '';

// Check if editing existing module
if (isset($_GET['id'])) {
    $module = $moduleObj->getModuleWithLessons($_GET['id']);
    if (!$module) {
        header('Location: modules.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    try {
        if (empty($title)) {
            throw new Exception('Title is required');
        }

        if ($module) {
            // Update existing module
            $success = $moduleObj->updateModule($module['id'], $title, $description);
        } else {
            // Create new module
            $success = $moduleObj->createModule($couseId, $title, $description);
        }
        // $courseId, $title, $description
        if ($success) {
            header('Location: modules.php');
            exit();
        } else {
            throw new Exception('Failed to save module');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = $module ? 'Edit Module' : 'Create New Module';
include '../includes/header.php';
?>

<main class="form-container">
    <div class="form-header">
        <h1><?php echo $pageTitle; ?></h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="module-form">
        <div class="form-group">
            <label for="title">Module Title</label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                required 
                class="form-control"
                value="<?php echo htmlspecialchars($module['title'] ?? ''); ?>"
            >
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea 
                id="description" 
                name="description" 
                class="form-control" 
                rows="4"
            ><?php echo htmlspecialchars($module['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="order_number">Order Number</label>
            <input 
                type="number" 
                id="order_number" 
                name="order_number" 
                required 
                class="form-control"
                value="<?php echo htmlspecialchars($module['order_number'] ?? ''); ?>"
                min="1"
            >
        </div>

        <div class="form-actions">
            <a href="../public/module.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <?php echo $module ? 'Update Module' : 'Create Module'; ?>
            </button>
        </div>
    </form>
</main>

<?php include '../includes/footer.php'; ?>