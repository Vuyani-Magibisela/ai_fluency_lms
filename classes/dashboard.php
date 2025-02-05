<?php
class Dashboard {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getUserStats($userId) {
        $stats = [
            'total_progress' => 0,
            'completed_modules' => 0,
            'total_modules' => 0,
            'quiz_performance' => 0,
            'recent_activities' => []
        ];

        try {
            // Get total number of modules
            $query = "SELECT COUNT(*) FROM modules";
            $stmt = $this->db->query($query);
            $stats['total_modules'] = $stmt->fetchColumn();

            // Get user's completed modules count
            $query = "SELECT COUNT(DISTINCT module_id) as completed 
                     FROM user_progress 
                     WHERE user_id = ? 
                     AND status = 'completed'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $stats['completed_modules'] = $stmt->fetchColumn() ?: 0;

            // Calculate progress percentage
            if ($stats['total_modules'] > 0) {
                $stats['total_progress'] = round(($stats['completed_modules'] / $stats['total_modules']) * 100, 2);
            }

            // Get quiz performance
            $query = "SELECT AVG(score) as avg_score 
                     FROM quiz_results 
                     WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $stats['quiz_performance'] = round($stmt->fetchColumn() ?: 0, 2);

            // Get recent activities
            $query = "SELECT up.*, m.title as module_title, l.title as lesson_title 
                     FROM user_progress up 
                     LEFT JOIN modules m ON up.module_id = m.id 
                     LEFT JOIN lessons l ON up.lesson_id = l.id 
                     WHERE up.user_id = ? 
                     ORDER BY up.updated_at DESC 
                     LIMIT 5";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $stats['recent_activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $stats;

        } catch (PDOException $e) {
            // Log the error and return empty stats
            error_log("Dashboard error: " . $e->getMessage());
            return $stats;
        }
    }

    public function getAdminStats() {
        $stats = [
            'total_users' => 0,
            'active_users' => 0,
            'total_modules' => 0,
            'average_completion' => 0,
            'quiz_performance' => 0,
            'recent_activities' => []
        ];

        try {
            // Get total users
            $query = "SELECT COUNT(*) FROM users";
            $stats['total_users'] = $this->db->query($query)->fetchColumn();

            // Get total modules
            $query = "SELECT COUNT(*) FROM modules";
            $stats['total_modules'] = $this->db->query($query)->fetchColumn();

            // Get average quiz performance
            $query = "SELECT AVG(score) FROM quiz_results";
            $stats['quiz_performance'] = round($this->db->query($query)->fetchColumn() ?: 0, 2);

            // Get active users (with activity in last 7 days)
            $query = "SELECT COUNT(DISTINCT user_id) 
                     FROM user_progress 
                     WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stats['active_users'] = $this->db->query($query)->fetchColumn();

            // Get recent activities across all users
            $query = "SELECT up.*, u.username, m.title as module_title, l.title as lesson_title 
                     FROM user_progress up 
                     JOIN users u ON up.user_id = u.id 
                     LEFT JOIN modules m ON up.module_id = m.id 
                     LEFT JOIN lessons l ON up.lesson_id = l.id 
                     ORDER BY up.updated_at DESC 
                     LIMIT 10";
            $stats['recent_activities'] = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

            return $stats;

        } catch (PDOException $e) {
            error_log("Admin dashboard error: " . $e->getMessage());
            return $stats;
        }
    }
}