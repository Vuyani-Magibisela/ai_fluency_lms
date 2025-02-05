<?php
class Lesson {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getLesson($lessonId, $userId = null) {
        // Get lesson details
        $query = "SELECT l.*, m.title as module_title 
                 FROM lessons l
                 JOIN modules m ON l.module_id = m.id
                 WHERE l.id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lesson) {
            return null;
        }

        // If user ID is provided, get their progress
        if ($userId) {
            $query = "SELECT status FROM user_progress 
                     WHERE user_id = ? AND lesson_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $lessonId]);
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            $lesson['user_progress'] = $progress ? $progress['status'] : 'not_started';
        }

        // Get quiz questions if they exist
        $query = "SELECT q.*, GROUP_CONCAT(
                    CONCAT(a.id, ':', a.answer_text)
                    SEPARATOR '|'
                ) as answers
                FROM quiz_questions q
                LEFT JOIN quiz_answers a ON q.id = a.question_id
                WHERE q.lesson_id = ?
                GROUP BY q.id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$lessonId]);
        $lesson['quiz_questions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $lesson;
    }

    public function createLesson($moduleId, $title, $description, $content, $videoUrl, $orderNumber) {
        $query = "INSERT INTO lessons (module_id, title, description, content, video_url, order_number) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$moduleId, $title, $description, $content, $videoUrl, $orderNumber]);
    }

    public function updateLesson($id, $title, $description, $content, $videoUrl, $orderNumber) {
        $query = "UPDATE lessons 
                 SET title = ?, description = ?, content = ?, video_url = ?, order_number = ? 
                 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$title, $description, $content, $videoUrl, $orderNumber, $id]);
    }

    public function deleteLesson($id) {
        // First delete associated quiz questions and answers
        $query = "DELETE qa FROM quiz_answers qa
                 JOIN quiz_questions qq ON qa.question_id = qq.id
                 WHERE qq.lesson_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $query = "DELETE FROM quiz_questions WHERE lesson_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        // Then delete the lesson
        $query = "DELETE FROM lessons WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function updateProgress($userId, $lessonId, $status) {
        $query = "INSERT INTO user_progress (user_id, lesson_id, status) 
                 VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE status = ?, updated_at = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$userId, $lessonId, $status, $status]);
    }
}