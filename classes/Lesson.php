<?php
class Lesson {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getLesson($lessonId, $userId = null) {
        $query = "SELECT l.*, m.title as module_title, 
                 GROUP_CONCAT(DISTINCT sm.type, ':', sm.file_path) as supplementary_materials
                 FROM lessons l
                 JOIN modules m ON l.module_id = m.id
                 LEFT JOIN supplementary_materials sm ON l.id = sm.lesson_id
                 WHERE l.id = ?
                 GROUP BY l.id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lesson) return null;

        if ($userId) {
            $query = "SELECT status, last_accessed FROM user_progress 
                     WHERE user_id = ? AND lesson_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $lessonId]);
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            $lesson['user_progress'] = $progress ? $progress['status'] : 'not_started';
            $lesson['last_accessed'] = $progress ? $progress['last_accessed'] : null;
        }

        return $this->enrichLessonData($lesson);
    }

    public function createLesson($data) {
        $this->db->beginTransaction();
        try {
            $query = "INSERT INTO lessons (module_id, title, description, video_url, order_num) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['module_id'],
                $data['title'],
                $data['description'],
                $data['video_url'],
                $this->getNextOrderNum($data['module_id'])
            ]);
            
            $lessonId = $this->db->lastInsertId();

            if (!empty($data['supplementary_materials'])) {
                $this->addSupplementaryMaterials($lessonId, $data['supplementary_materials']);
            }

            $this->db->commit();
            return $lessonId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateLesson($id, $data) {
        $this->db->beginTransaction();
        try {
            $query = "UPDATE lessons 
                     SET title = ?, description = ?, video_url = ?, 
                         order_num = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['video_url'],
                $data['order_num'],
                $id
            ]);

            if (isset($data['supplementary_materials'])) {
                $this->updateSupplementaryMaterials($id, $data['supplementary_materials']);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function addSupplementaryMaterials($lessonId, $materials) {
        $query = "INSERT INTO supplementary_materials 
                 (lesson_id, type, format, file_path) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        
        foreach ($materials as $material) {
            $stmt->execute([
                $lessonId,
                $material['type'],
                $material['format'],
                $material['file_path']
            ]);
        }
    }

    private function updateSupplementaryMaterials($lessonId, $materials) {
        // Delete existing materials
        $query = "DELETE FROM supplementary_materials WHERE lesson_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$lessonId]);

        // Add new materials
        $this->addSupplementaryMaterials($lessonId, $materials);
    }

    private function getNextOrderNum($moduleId) {
        $query = "SELECT MAX(order_num) + 1 FROM lessons WHERE module_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$moduleId]);
        return $stmt->fetchColumn() ?: 1;
    }

    private function enrichLessonData($lesson) {
        if ($lesson['supplementary_materials']) {
            $materials = explode(',', $lesson['supplementary_materials']);
            $lesson['supplementary_materials'] = array_map(function($material) {
                list($type, $path) = explode(':', $material);
                return ['type' => $type, 'file_path' => $path];
            }, $materials);
        } else {
            $lesson['supplementary_materials'] = [];
        }
        
        return $lesson;
    }
}