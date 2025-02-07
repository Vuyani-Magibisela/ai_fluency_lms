<?php
class AssessmentException extends Exception {}

class AssessmentValidator {
    private static function validateTitle($title) {
        if (empty($title) || strlen($title) < 3 || strlen($title) > 255) {
            throw new AssessmentException("Title must be between 3 and 255 characters");
        }
    }

    private static function validateQuestions($questions) {
        if (empty($questions)) {
            throw new AssessmentException("At least one question is required");
        }

        foreach ($questions as $question) {
            if (empty($question['text'])) {
                throw new AssessmentException("Question text cannot be empty");
            }

            if (empty($question['answers']) || count($question['answers']) < 2) {
                throw new AssessmentException("Each question must have at least 2 answers");
            }

            $hasCorrectAnswer = false;
            foreach ($question['answers'] as $answer) {
                if (empty($answer['text'])) {
                    throw new AssessmentException("Answer text cannot be empty");
                }
                if ($answer['is_correct']) {
                    $hasCorrectAnswer = true;
                }
            }

            if (!$hasCorrectAnswer) {
                throw new AssessmentException("Each question must have at least one correct answer");
            }
        }
    }

    private static function validateExamSettings($data) {
        if (isset($data['passing_score'])) {
            if (!is_numeric($data['passing_score']) || 
                $data['passing_score'] < 0 || 
                $data['passing_score'] > 100) {
                throw new AssessmentException("Passing score must be between 0 and 100");
            }
        }

        if (isset($data['time_limit'])) {
            if (!is_null($data['time_limit']) && 
                (!is_numeric($data['time_limit']) || $data['time_limit'] < 1)) {
                throw new AssessmentException("Time limit must be a positive number");
            }
        }
    }

    public static function validateExamData($data) {
        self::validateTitle($data['title']);
        self::validateQuestions($data['questions']);
        self::validateExamSettings($data);
    }

    public static function validateQuizData($data) {
        self::validateTitle($data['title']);
        self::validateQuestions($data['questions']);
    }
}

class CourseExam {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createExam($courseId, $data) {
        try {
            // Validate course exists
            $courseCheck = $this->db->prepare("SELECT id FROM courses WHERE id = ?");
            $courseCheck->execute([$courseId]);
            if (!$courseCheck->fetch()) {
                throw new AssessmentException("Course does not exist");
            }

            // Validate exam data
            AssessmentValidator::validateExamData($data);

            $this->db->beginTransaction();

            $query = "INSERT INTO course_exams (
                course_id, 
                title, 
                description, 
                passing_score,
                time_limit,
                status
            ) VALUES (?, ?, ?, ?, ?, ?)";

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

            if (!empty($data['questions'])) {
                $this->addQuestionsToExam($examId, $data['questions']);
            }

            $this->db->commit();
            return $examId;

        } catch (AssessmentException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new AssessmentException("Database error: " . $e->getMessage());
        }
    }

    public function startExamAttempt($userId, $examId) {
        try {
            // Check if user exists
            $userCheck = $this->db->prepare("SELECT id FROM users WHERE id = ?");
            $userCheck->execute([$userId]);
            if (!$userCheck->fetch()) {
                throw new AssessmentException("User does not exist");
            }

            // Check if exam exists and is published
            $examCheck = $this->db->prepare(
                "SELECT id, status, time_limit FROM course_exams WHERE id = ?"
            );
            $examCheck->execute([$examId]);
            $exam = $examCheck->fetch();
            
            if (!$exam) {
                throw new AssessmentException("Exam does not exist");
            }
            if ($exam['status'] !== 'published') {
                throw new AssessmentException("Exam is not available");
            }

            // Check for existing incomplete attempts
            $attemptCheck = $this->db->prepare(
                "SELECT id FROM exam_attempts 
                WHERE user_id = ? AND exam_id = ? AND status = 'in_progress'"
            );
            $attemptCheck->execute([$userId, $examId]);
            if ($attemptCheck->fetch()) {
                throw new AssessmentException("You have an incomplete exam attempt");
            }

            // Create new attempt
            $this->db->beginTransaction();
            
            $query = "INSERT INTO exam_attempts (
                user_id, 
                exam_id, 
                status,
                time_limit,
                started_at
            ) VALUES (?, ?, 'in_progress', ?, CURRENT_TIMESTAMP)";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $examId, $exam['time_limit']]);
            $attemptId = $this->db->lastInsertId();

            $this->db->commit();
            return $attemptId;

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new AssessmentException("Database error: " . $e->getMessage());
        }
    }

    public function submitExamResponse($attemptId, $questionId, $answerId) {
        try {
            // Validate attempt exists and is in progress
            $attemptCheck = $this->db->prepare(
                "SELECT a.*, e.time_limit 
                FROM exam_attempts a
                JOIN course_exams e ON a.exam_id = e.id
                WHERE a.id = ?"
            );
            $attemptCheck->execute([$attemptId]);
            $attempt = $attemptCheck->fetch();

            if (!$attempt) {
                throw new AssessmentException("Invalid attempt");
            }
            if ($attempt['status'] !== 'in_progress') {
                throw new AssessmentException("This attempt is already completed");
            }

            // Check time limit if exists
            if ($attempt['time_limit']) {
                $startTime = strtotime($attempt['started_at']);
                $currentTime = time();
                $timeElapsed = ($currentTime - $startTime) / 60; // Convert to minutes

                if ($timeElapsed > $attempt['time_limit']) {
                    // Auto-complete the exam if time is up
                    $this->completeExamAttempt($attemptId);
                    throw new AssessmentException("Time limit exceeded");
                }
            }

            // Validate question belongs to exam
            $questionCheck = $this->db->prepare(
                "SELECT q.id FROM quiz_questions q
                JOIN course_exams e ON q.course_exam_id = e.id
                JOIN exam_attempts a ON e.id = a.exam_id
                WHERE q.id = ? AND a.id = ?"
            );
            $questionCheck->execute([$questionId, $attemptId]);
            if (!$questionCheck->fetch()) {
                throw new AssessmentException("Invalid question for this exam");
            }

            // Validate answer belongs to question
            $answerCheck = $this->db->prepare(
                "SELECT id FROM quiz_answers WHERE id = ? AND question_id = ?"
            );
            $answerCheck->execute([$answerId, $questionId]);
            if (!$answerCheck->fetch()) {
                throw new AssessmentException("Invalid answer for this question");
            }

            // Record response
            $this->db->beginTransaction();

            $query = "INSERT INTO exam_responses (
                attempt_id,
                question_id,
                answer_id,
                response_time
            ) VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                answer_id = ?,
                response_time = CURRENT_TIMESTAMP";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$attemptId, $questionId, $answerId, $answerId]);

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new AssessmentException("Database error: " . $e->getMessage());
        }
    }

    public function completeExamAttempt($attemptId) {
        try {
            // Validate attempt exists and is in progress
            $attemptCheck = $this->db->prepare(
                "SELECT * FROM exam_attempts WHERE id = ?"
            );
            $attemptCheck->execute([$attemptId]);
            $attempt = $attemptCheck->fetch();

            if (!$attempt) {
                throw new AssessmentException("Invalid attempt");
            }
            if ($attempt['status'] !== 'in_progress') {
                throw new AssessmentException("This attempt is already completed");
            }

            $this->db->beginTransaction();

            // Calculate score
            $query = "SELECT 
                COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) * 100.0 / COUNT(*) as score
                FROM exam_responses er
                JOIN quiz_answers a ON er.answer_id = a.id
                WHERE er.attempt_id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$attemptId]);
            $result = $stmt->fetch();
            $score = round($result['score'] ?? 0, 2);

            // Get passing score
            $query = "SELECT e.passing_score 
                     FROM exam_attempts a
                     JOIN course_exams e ON a.exam_id = e.id
                     WHERE a.id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$attemptId]);
            $exam = $stmt->fetch();

            // Update attempt
            $status = $score >= $exam['passing_score'] ? 'completed' : 'failed';
            $query = "UPDATE exam_attempts SET 
                score = ?,
                status = ?,
                completed_at = CURRENT_TIMESTAMP
                WHERE id = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$score, $status, $attemptId]);

            $this->db->commit();
            return [
                'score' => $score,
                'status' => $status,
                'passing_score' => $exam['passing_score']
            ];

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new AssessmentException("Database error: " . $e->getMessage());
        }
    }
}