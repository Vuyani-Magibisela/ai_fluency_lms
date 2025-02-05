<?php
class Quiz {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getQuizQuestions($lessonId) {
        $query = "SELECT q.*, GROUP_CONCAT(
                    JSON_OBJECT(
                        'id', a.id,
                        'text', a.answer_text,
                        'is_correct', a.is_correct
                    )
                ) as answers
                FROM quiz_questions q
                LEFT JOIN quiz_answers a ON q.id = a.question_id
                WHERE q.lesson_id = ?
                GROUP BY q.id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$lessonId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addQuestion($lessonId, $questionText, $questionType, $answers) {
        $this->db->beginTransaction();
        
        try {
            // Insert question
            $query = "INSERT INTO quiz_questions (lesson_id, question_text, question_type) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$lessonId, $questionText, $questionType]);
            $questionId = $this->db->lastInsertId();

            // Insert answers
            $query = "INSERT INTO quiz_answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            
            foreach ($answers as $answer) {
                $stmt->execute([
                    $questionId,
                    $answer['text'],
                    $answer['is_correct'] ?? false
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function submitQuiz($userId, $lessonId, $answers) {
        $score = 0;
        $totalQuestions = 0;

        foreach ($answers as $questionId => $answerId) {
            $query = "SELECT is_correct FROM quiz_answers 
                     WHERE id = ? AND question_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$answerId, $questionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['is_correct']) {
                $score++;
            }
            $totalQuestions++;
        }

        $finalScore = ($totalQuestions > 0) ? ($score / $totalQuestions) * 100 : 0;

        // Record the quiz result
        $query = "INSERT INTO quiz_results (user_id, lesson_id, score) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $lessonId, $finalScore]);

        // Update lesson progress if score is passing (e.g., > 70%)
        if ($finalScore >= 70) {
            $query = "INSERT INTO user_progress (user_id, lesson_id, status) 
                     VALUES (?, ?, 'completed') 
                     ON DUPLICATE KEY UPDATE status = 'completed'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $lessonId]);
        }

        return [
            'score' => $finalScore,
            'correct_answers' => $score,
            'total_questions' => $totalQuestions,
            'passed' => $finalScore >= 70
        ];
    }
}