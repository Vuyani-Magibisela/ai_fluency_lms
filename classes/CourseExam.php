<?php
class CourseExam {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createExam($courseId, $data) {
        $query = "INSERT INTO course_exams (
            course_id, 
            title, 
            description, 
            passing_score,
            time_limit,
            status
        ) VALUES (?, ?, ?, ?, ?, ?)";

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $courseId,
                $data['title'],
                $data['description'],
                $data['passing_score'] ?? 70,
                $data['time_limit'] ?? null,
                $data['status'] ?? 'draft'
            ]);
            
            $examId = $this->db->lastInsertId();

            // If questions are provided, add them
            if (!empty($data['questions'])) {
                $this->addQuestionsToExam($examId, $data['questions']);
            }

            $this->db->commit();
            return $examId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateExam($examId, $data) {
        $query = "UPDATE course_exams SET 
            title = ?,
            description = ?,
            passing_score = ?,
            time_limit = ?,
            status = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['passing_score'] ?? 70,
                $data['time_limit'] ?? null,
                $data['status'] ?? 'draft',
                $examId
            ]);

            // Update questions if provided
            if (isset($data['questions'])) {
                $this->updateExamQuestions($examId, $data['questions']);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function addQuestionsToExam($examId, $questions) {
        try {
            // Prepare statements for questions and answers
            $questionQuery = "INSERT INTO quiz_questions (
                course_exam_id, 
                question_text, 
                question_type
            ) VALUES (?, ?, ?)";
            
            $answerQuery = "INSERT INTO quiz_answers (
                question_id, 
                answer_text, 
                is_correct
            ) VALUES (?, ?, ?)";
    
            $questionStmt = $this->db->prepare($questionQuery);
            $answerStmt = $this->db->prepare($answerQuery);
    
            foreach ($questions as $question) {
                // Validate question structure
                if (empty($question['text']) || empty($question['type'])) {
                    throw new AssessmentException("Invalid question format");
                }
    
                // Insert question
                $questionStmt->execute([
                    $examId,
                    $question['text'],
                    $question['type']
                ]);
                $questionId = $this->db->lastInsertId();
    
                // Validate answers exist
                if (empty($question['answers']) || !is_array($question['answers'])) {
                    throw new AssessmentException("Questions must have answers");
                }
    
                // Insert answers
                foreach ($question['answers'] as $answer) {
                    if (!isset($answer['text']) || !isset($answer['is_correct'])) {
                        throw new AssessmentException("Invalid answer format");
                    }
    
                    $answerStmt->execute([
                        $questionId,
                        $answer['text'],
                        $answer['is_correct']
                    ]);
                }
            }
            return true;
        } catch (PDOException $e) {
            throw new AssessmentException("Failed to add questions: " . $e->getMessage());
        }
    }

    private function updateExamQuestions($examId, $questions) {
        // Remove existing questions
        $query = "DELETE qa FROM quiz_answers qa 
                 JOIN quiz_questions qq ON qa.question_id = qq.id 
                 WHERE qq.course_exam_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$examId]);

        $query = "DELETE FROM quiz_questions WHERE course_exam_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$examId]);

        // Add new questions
        $this->addQuestionsToExam($examId, $questions);
    }

    public function getExam($examId) {
        $query = "SELECT e.*, c.title as course_title,
                    (SELECT COUNT(*) FROM quiz_questions WHERE course_exam_id = e.id) as question_count
                 FROM course_exams e
                 JOIN courses c ON e.course_id = c.id
                 WHERE e.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$examId]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exam) {
            $exam['questions'] = $this->getExamQuestions($examId);
        }

        return $exam;
    }

    private function getExamQuestions($examId) {
        $query = "SELECT q.*, GROUP_CONCAT(
                    CONCAT(a.id, ':', a.answer_text, ':', a.is_correct)
                    SEPARATOR '|'
                ) as answers
                FROM quiz_questions q
                LEFT JOIN quiz_answers a ON q.id = a.question_id
                WHERE q.course_exam_id = ?
                GROUP BY q.id
                ORDER BY q.id";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$examId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function startExamAttempt($userId, $examId) {
        $query = "INSERT INTO exam_attempts (
            user_id, 
            exam_id, 
            status
        ) VALUES (?, ?, 'in_progress')";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $examId]);
        return $this->db->lastInsertId();
    }

    public function submitExamResponse($attemptId, $questionId, $answerId) {
        // Get correct answer info
        $query = "SELECT is_correct FROM quiz_answers WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$answerId]);
        $answer = $stmt->fetch(PDO::FETCH_ASSOC);

        $query = "INSERT INTO exam_responses (
            attempt_id,
            question_id,
            answer_id,
            is_correct
        ) VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $attemptId,
            $questionId,
            $answerId,
            $answer['is_correct']
        ]);
    }

    public function completeExamAttempt($attemptId) {
        // Calculate score
        $query = "SELECT 
            COUNT(CASE WHEN is_correct = 1 THEN 1 END) * 100.0 / COUNT(*) as score
            FROM exam_responses
            WHERE attempt_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$attemptId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $score = round($result['score'], 2);

        // Get passing score
        $query = "SELECT e.passing_score 
                 FROM exam_attempts a
                 JOIN course_exams e ON a.exam_id = e.id
                 WHERE a.id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$attemptId]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        // Update attempt status
        $status = $score >= $exam['passing_score'] ? 'completed' : 'failed';
        $query = "UPDATE exam_attempts SET 
            score = ?,
            status = ?,
            completed_at = CURRENT_TIMESTAMP
            WHERE id = ?";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$score, $status, $attemptId]);

        return [
            'score' => $score,
            'status' => $status,
            'passing_score' => $exam['passing_score']
        ];
    }
}