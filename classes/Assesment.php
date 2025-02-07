<?php
class Assessment {
    private $db;
    private $courseExam;

    public function __construct($db) {
        $this->db = $db;
        $this->courseExam = new CourseExam($db);
    }

    public function createQuiz($moduleId, $title, $questions) {
        $this->db->beginTransaction();
        try {
            // Create quiz questions
            $questionQuery = "INSERT INTO quiz_questions 
                            (module_id, question_text, question_type) 
                            VALUES (?, ?, ?)";
            $answerQuery = "INSERT INTO quiz_answers 
                           (question_id, answer_text, is_correct) 
                           VALUES (?, ?, ?)";
            
            $questionStmt = $this->db->prepare($questionQuery);
            $answerStmt = $this->db->prepare($answerQuery);

            foreach ($questions as $question) {
                $questionStmt->execute([
                    $moduleId,
                    $question['text'],
                    $question['type']
                ]);
                $questionId = $this->db->lastInsertId();

                foreach ($question['answers'] as $answer) {
                    $answerStmt->execute([
                        $questionId,
                        $answer['text'],
                        $answer['is_correct']
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function submitQuizAttempt($userId, $questionId, $selectedAnswerId) {
        $this->db->beginTransaction();
        try {
            // Get correct answer
            $query = "SELECT is_correct FROM quiz_answers WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$selectedAnswerId]);
            $answer = $stmt->fetch(PDO::FETCH_ASSOC);

            // Create attempt record
            $attemptQuery = "INSERT INTO quiz_attempts 
                           (user_id, question_id, selected_answer_id, is_correct) 
                           VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($attemptQuery);
            $stmt->execute([
                $userId, 
                $questionId, 
                $selectedAnswerId,
                $answer['is_correct']
            ]);
            
            $attemptId = $this->db->lastInsertId();

            // Record result
            $this->recordQuizResult($userId, $questionId, $answer['is_correct']);

            $this->db->commit();
            return [
                'attempt_id' => $attemptId,
                'is_correct' => $answer['is_correct']
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function recordQuizResult($userId, $questionId, $isCorrect) {
        // Get module ID from question
        $query = "SELECT module_id FROM quiz_questions WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$questionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $moduleId = $result['module_id'];

        // Calculate overall score for the module
        $query = "SELECT 
                    COUNT(CASE WHEN is_correct = 1 THEN 1 END) * 100.0 / COUNT(*) as score
                 FROM quiz_attempts qa
                 JOIN quiz_questions qq ON qa.question_id = qq.id
                 WHERE qa.user_id = ? AND qq.module_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $moduleId]);
        $score = $stmt->fetch(PDO::FETCH_ASSOC)['score'];

        // Update or insert quiz result
        $query = "INSERT INTO quiz_results (
                    user_id, module_id, score, attempt_number
                ) VALUES (
                    ?, ?, ?, (
                        SELECT COALESCE(MAX(attempt_number), 0) + 1
                        FROM quiz_results
                        WHERE user_id = ? AND module_id = ?
                    )
                )";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $moduleId, $score, $userId, $moduleId]);
    }

    public function getQuizResults($userId, $moduleId = null) {
        $params = [$userId];
        $whereClause = "";
        
        if ($moduleId) {
            $whereClause = "AND module_id = ?";
            $params[] = $moduleId;
        }

        $query = "SELECT 
                    module_id,
                    MAX(score) as best_score,
                    AVG(score) as average_score,
                    COUNT(*) as attempts,
                    MAX(created_at) as last_attempt
                 FROM quiz_results
                 WHERE user_id = ? $whereClause
                 GROUP BY module_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Methods for handling course exams
    public function createCourseExam($courseId, $data) {
        return $this->courseExam->createExam($courseId, $data);
    }

    public function startExam($userId, $examId) {
        return $this->courseExam->startExamAttempt($userId, $examId);
    }

    public function submitExamAnswer($attemptId, $questionId, $answerId) {
        return $this->courseExam->submitExamResponse($attemptId, $questionId, $answerId);
    }

    public function completeExam($attemptId) {
        return $this->courseExam->completeExamAttempt($attemptId);
    }
}