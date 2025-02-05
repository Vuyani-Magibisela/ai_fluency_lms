<?php
require_once '../config/init.php';
require_once '../classes/Auth.php';
require_once '../classes/Course.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: courses.php');
    exit();
}

$courseObj = new Course(Database::getInstance()->getConnection());
$course = null;
$error = '';

if (isset($_GET['id'])) {
    $course = $courseObj->getCourse($_GET['id']);
    if (!$course) {
        header('Location: courses.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    try {
        if (empty($title)) {
            throw new Exception('Title is required');
        }

        if ($course) {
            $success = $courseObj->updateCourse($course['id'], $title, $description, $status);
        } else {
            $success = $courseObj->createCourse($title, $description, $status);
        }

        if ($success) {
            header('Location: courses.php');
            exit();
        } else {
            throw new Exception('Failed to save course');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = $course ? 'Edit Course' : 'Create New Course';
include '../includes/header.php';
?>

<main class="form-container">
    <div class="form-header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="course-form">
        <div class="form-group">
            <label for="title">Course Title</label>
            <input type="text" id="title" name="title" required class="form-control"
                   value="<?php echo htmlspecialchars($course['title'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" 
                      rows="4"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="draft" <?php echo ($course['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="published" <?php echo ($course['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
            </select>
        </div>

        <div class="form-actions">
            <a href="courses.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <?php echo $course ? 'Update Course' : 'Create Course'; ?>
            </button>
        </div>
    </form>
</main>

<?php include '../includes/footer.php'; ?>