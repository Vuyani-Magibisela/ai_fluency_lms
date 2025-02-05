<?php
class Course {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllCourses() {
        $query = "SELECT c.*, COUNT(m.id) as module_count 
                 FROM courses c 
                 LEFT JOIN modules m ON c.id = m.course_id 
                 GROUP BY c.id";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCourse($id) {
        $query = "SELECT * FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createCourse($title, $description, $status = 'draft') {
        $query = "INSERT INTO courses (title, description, status) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$title, $description, $status]);
    }

    public function updateCourse($id, $title, $description, $status) {
        $query = "UPDATE courses SET title = ?, description = ?, status = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$title, $description, $status, $id]);
    }

    public function deleteCourse($id) {
        // Delete associated modules first
        $query = "DELETE FROM modules WHERE course_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        // Then delete the course
        $query = "DELETE FROM courses WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getCourseModules($courseId) {
        $query = "SELECT * FROM modules WHERE course_id = ? ORDER BY order_number, id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}