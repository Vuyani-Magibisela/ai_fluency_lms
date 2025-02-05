<?php
require_once '../config/init.php';
require_once '../classes/Auth.php';
require_once '../classes/Course.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$courseObj = new Course(Database::getInstance()->getConnection());
$courses = $courseObj->getAllCourses();
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$pageTitle = 'Available Courses';
include '../includes/header.php';
?>

<main class="courses-container">
    <div class="courses-header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <?php if ($isAdmin): ?>
            <a href="course-form.php" class="btn btn-primary">Add New Course</a>
        <?php endif; ?>
    </div>

    <div class="courses-grid">
        <?php foreach ($courses as $course): ?>
            <div class="course-card">
                <div class="course-header">
                    <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                    <span class="module-count">
                        <?php echo $course['module_count']; ?> Modules
                    </span>
                </div>
                
                <p class="course-description">
                    <?php echo htmlspecialchars($course['description']); ?>
                </p>

                <div class="course-footer">
                    <?php if ($isAdmin): ?>
                        <div class="admin-controls">
                            <a href="course-form.php?id=<?php echo $course['id']; ?>" 
                               class="btn btn-secondary">Edit</a>
                            <button onclick="confirmDelete(<?php echo $course['id']; ?>)" 
                                    class="btn btn-danger">Delete</button>
                        </div>
                    <?php endif; ?>
                    <a href="course-view.php?id=<?php echo $course['id']; ?>" 
                       class="btn btn-primary">View Course</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php if ($isAdmin): ?>
<script>
function confirmDelete(courseId) {
    if (confirm('Are you sure you want to delete this course? All associated modules will be deleted.')) {
        window.location.href = `course-delete.php?id=${courseId}`;
    }
}
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>