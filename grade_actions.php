<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.html");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// ACTION 1: BATCH CHECKBOX SUBJECT ENROLLMENT
if (isset($_POST['enroll_students'])) {
    $subject = $conn->real_escape_string($_POST['subject']);
    $student_ids = isset($_POST['students']) ? $_POST['students'] : [];

    if (!empty($subject)) {
        foreach ($student_ids as $id) {
            $student_id = (int)$id;
            $conn->query("INSERT IGNORE INTO subject_enrollments (student_id, subject) VALUES ($student_id, '$subject')");
        }
        header("Location: dashboard.php?tab=enroll&status=enrolled");
        exit();
    }
}

/**
 * HELPER FUNCTION: Calculates mathematically precise GPA and Letter Grade
 */
function calculateAcademicMetrics($percentage) {
    // 1. Precise Sliding-Scale Formula Calculation
    $gpa = ($percentage / 100) * 4.00;
    $gpa = round($gpa, 2); // Round cleanly to two decimal places

    // 2. Map the calculated scale to standard letter grade benchmarks
    if ($percentage >= 90)       { $letter = "A+"; }
    elseif ($percentage >= 80) { $letter = "A";  }
    elseif ($percentage >= 70) { $letter = "B+"; }
    elseif ($percentage >= 60) { $letter = "B";  }
    elseif ($percentage >= 50) { $letter = "C+"; }
    elseif ($percentage >= 40) { $letter = "C";  }
    else { 
        $gpa = 0.00; // Hard cap for unearned marks below passing
        $letter = "NG"; 
    }

    return ['gpa' => $gpa, 'letter' => $letter];
}

// ACTION 2: INDIVIDUAL TERMINAL GRADING SUBMISSION
if (isset($_POST['add_grade'])) {
    $student_id = (int)$_POST['student_id'];
    $subject = $conn->real_escape_string($_POST['subject']);
    $term = $conn->real_escape_string($_POST['term']);
    $percentage = (float)$_POST['percentage'];

    // Run our updated calculation logic
    $metrics = calculateAcademicMetrics($percentage);
    $gpa = $metrics['gpa'];
    $grade = $metrics['letter'];

    $sql = "INSERT INTO grades (student_id, teacher_id, subject, term, percentage, gpa, letter_grade) 
            VALUES ($student_id, $teacher_id, '$subject', '$term', $percentage, $gpa, '$grade')
            ON DUPLICATE KEY UPDATE percentage=$percentage, gpa=$gpa, letter_grade='$grade'";

    if ($conn->query($sql)) {
        header("Location: dashboard.php?tab=marking&status=graded");
    } else {
        echo "Error saving entry: " . $conn->error;
    }
    exit();
}

// ACTION 3: INLINE RECORD MODIFICATION UPDATES
if (isset($_POST['update_grade'])) {
    $grade_id = (int)$_POST['grade_id'];
    $percentage = (float)$_POST['percentage'];

    // Run our updated calculation logic
    $metrics = calculateAcademicMetrics($percentage);
    $gpa = $metrics['gpa'];
    $grade = $metrics['letter'];

    $conn->query("UPDATE grades SET percentage=$percentage, gpa=$gpa, letter_grade='$grade' WHERE id=$grade_id AND teacher_id=$teacher_id");
    header("Location: dashboard.php?tab=view&status=updated");
    exit();
}

// ACTION 4: RECORD REMOVAL / DELETION
if (isset($_GET['delete_id'])) {
    $grade_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM grades WHERE id=$grade_id AND teacher_id=$teacher_id");
    header("Location: dashboard.php?tab=view&status=deleted");
    exit();
}
?>