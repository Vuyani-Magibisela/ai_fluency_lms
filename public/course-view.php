<?php
// view_course.php
require_once '../config/database.php';
require_once '../classes/Course.php';
require_once '../classes/Module.php';
require_once '../classes/Lesson.php';

session_start();

if (!isset($_GET['id'])) {
    header('Location: courses.php');
    exit();
}

$courseId = $_GET['id'];
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$userId = $_SESSION['user_id'] ?? null;

$db = Database::getInstance()->getConnection();
$course = new Course($db);
$module = new Module($db);

$courseDetails = $course->getCourse($courseId);
$modules = $module->getModulesByCourse($courseId);

// Handle module creation if admin
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_module':
                $module->createModule(
                    $courseId,
                    $_POST['title'],
                    $_POST['description']
                );
                header("Location: view_course.php?id=$courseId&success=module_added");
                exit();
                
            
            case 'delete_module':
                $module->deleteModule($_POST['module_id']);
                header("Location: view_course.php?id=$courseId&success=module_deleted");
                exit();

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($courseDetails['title']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'module_added':
                        echo "Module added successfully!";
                        break;
                    case 'module_deleted':
                        echo "Module deleted successfully!";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="course-header">
            <h1><?php echo htmlspecialchars($courseDetails['title']); ?></h1>
            <?php if ($isAdmin): ?>
                <div class="admin-controls">
                    <a href="edit_course.php?id=<?php echo $courseId; ?>" class="btn btn-primary">Edit Course</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="course-details">
            <div class="course-description">
                <h2>Course Description</h2>
                <p><?php echo nl2br(htmlspecialchars($courseDetails['description'])); ?></p>
            </div>

            <?php if (!empty($courseDetails['requirements'])): ?>
            <div class="course-requirements">
                <h2>Course Requirements</h2>
                <p><?php echo nl2br(htmlspecialchars($courseDetails['requirements'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="course-content">
            <h2>Course Modules</h2>
            
            <?php if ($isAdmin): ?>
            <div class="module-form">
                <h3>Add New Module</h3>
                <form action="" method="POST" class="add-module-form">
                    <input type="hidden" name="action" value="add_module">
                    <div class="form-group">
                        <label for="title">Module Title</label>
                        <input type="text" id="title" name="title" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="description">Module Description</label>
                        <textarea id="description" name="description" required class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Module</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="modules-list">
                <?php if (empty($modules)): ?>
                    <p>No modules available for this course yet.</p>
                <?php else: ?>
                    <?php foreach ($modules as $mod): ?>
                        <div class="module-card">
                            <div class="module-header">
                                <h3><?php echo htmlspecialchars($mod['title']); ?></h3>
                                <?php if ($isAdmin): ?>
                                    <div class="module-actions">
                                        <a href="edit_module.php?id=<?php echo $mod['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <form action="" method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this module?');">
                                            <input type="hidden" name="action" value="delete_module">
                                            <input type="hidden" name="module_id" value="<?php echo $mod['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($mod['description'])); ?></p>
                            
                            <?php if ($isAdmin): ?>
                                <div class="module-content-actions">
                                    <a href="add_lesson.php?module_id=<?php echo $mod['id']; ?>" class="btn btn-sm btn-success">Add Lesson</a>
                                    <a href="add_quiz.php?module_id=<?php echo $mod['id']; ?>" class="btn btn-sm btn-info">Add Quiz</a>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Get lessons for this module
                            $lessons = $module->getModuleWithLessons($mod['id'])['lessons'] ?? [];
                            if (!empty($lessons)):
                            ?>
                                <div class="lesson-list">
                                    <?php foreach ($lessons as $lesson): ?>
                                        <div class="lesson-item">
                                            <span class="lesson-title">
                                                <?php if ($isAdmin): ?>
                                                    <a href="edit_lesson.php?id=<?php echo $lesson['id']; ?>">
                                                        <?php echo htmlspecialchars($lesson['title']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="view_lesson.php?id=<?php echo $lesson['id']; ?>">
                                                        <?php echo htmlspecialchars($lesson['title']); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </span>
                                            <?php if (isset($lesson['has_quiz']) && $lesson['has_quiz']): ?>
                                                <span class="badge badge-info">Quiz</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>