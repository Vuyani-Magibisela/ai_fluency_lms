<?php
class Module {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllModules() {
        // Check if order_number column exists
        try {
            $query = "SELECT * FROM modules ORDER BY order_number, id";
            return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If order_number doesn't exist, fallback to ordering by ID
            if ($e->getCode() == '42S22') { // Column not found error
                $query = "SELECT * FROM modules ORDER BY id";
                return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
            }
            throw $e;
        }
    }

    public function getModuleWithLessons($moduleId) {
        // Get module details
        $query = "SELECT * FROM modules WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$moduleId]);
        $module = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$module) {
            return null;
        }

        // Get lessons for this module
        $query = "SELECT * FROM lessons WHERE module_id = ? ORDER BY id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$moduleId]);
        $module['lessons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $module;
    }

    public function createModule($courseId, $title, $description) {
        // Verify course exists first
        $courseCheck = "SELECT id FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($courseCheck);
        $stmt->execute([$courseId]);
        if (!$stmt->fetch()) {
            throw new Exception("Course with ID $courseId does not exist");
        }

        // Get the highest order number for this course
        $maxOrderQuery = "SELECT MAX(order_number) FROM modules WHERE course_id = ?";
        $stmt = $this->db->prepare($maxOrderQuery);
        $stmt->execute([$courseId]);
        $maxOrder = $stmt->fetchColumn();
        $nextOrder = $maxOrder ? $maxOrder + 1 : 1;

        $query = "INSERT INTO modules (course_id, title, description, order_number) 
                 VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$courseId, $title, $description, $nextOrder]);
    }

    public function getModulesByCourse($courseId) {
        $query = "SELECT m.*, 
                    (SELECT COUNT(*) FROM lessons WHERE module_id = m.id) as lesson_count
                 FROM modules m 
                 WHERE m.course_id = ? 
                 ORDER BY m.order_number, m.id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateModule($id, $title, $description, $orderNumber = null) {
        try {
            if ($orderNumber !== null) {
                $query = "UPDATE modules SET title = ?, description = ?, order_number = ? WHERE id = ?";
                $stmt = $this->db->prepare($query);
                return $stmt->execute([$title, $description, $orderNumber, $id]);
            } else {
                $query = "UPDATE modules SET title = ?, description = ? WHERE id = ?";
                $stmt = $this->db->prepare($query);
                return $stmt->execute([$title, $description, $id]);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '42S22') { // Column not found error
                $query = "UPDATE modules SET title = ?, description = ? WHERE id = ?";
                $stmt = $this->db->prepare($query);
                return $stmt->execute([$title, $description, $id]);
            }
            throw $e;
        }
    }

    public function deleteModule($id) {
        // First delete associated lessons
        $query = "DELETE FROM lessons WHERE module_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        // Then delete the module
        $query = "DELETE FROM modules WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getModuleProgress($moduleId, $userId) {
        $query = "SELECT 
                    COUNT(DISTINCT l.id) as total_lessons,
                    COUNT(DISTINCT CASE WHEN up.status = 'completed' THEN l.id END) as completed_lessons
                 FROM lessons l
                 LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
                 WHERE l.module_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $moduleId]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total' => $progress['total_lessons'],
            'completed' => $progress['completed_lessons'],
            'percentage' => $progress['total_lessons'] > 0 
                ? round(($progress['completed_lessons'] / $progress['total_lessons']) * 100) 
                : 0
        ];
    }
}