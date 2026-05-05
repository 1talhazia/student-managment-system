<?php
session_start();
require 'db_connect.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// 2. SAVE/UPDATE GRADE LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_grade'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $marks = $_POST['marks'];
    $grade_letter = $_POST['grade_letter']; // Mapping to your 'grade' column

    // Check if a record already exists for this enrollment
    $check = $conn->prepare("SELECT grade_id FROM grades WHERE enrollment_id = ?");
    $check->bind_param("i", $enrollment_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE grades SET marks = ?, grade = ? WHERE enrollment_id = ?");
        $stmt->bind_param("isi", $marks, $grade_letter, $enrollment_id);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO grades (enrollment_id, marks, grade) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $enrollment_id, $marks, $grade_letter);
    }

    if ($stmt->execute()) {
        $message = "<p style='color: #2ecc71; font-weight: bold; margin-bottom: 15px;'>Grade updated successfully!</p>";
    } else {
        $message = "<p style='color: #e74c3c; font-weight: bold; margin-bottom: 15px;'>Error: " . $conn->error . "</p>";
    }
    $stmt->close();
}

// 3. FETCH DATA (Joining with Students and Courses)
// Note: We use g.marks and g.grade to match your XAMPP attributes
$query = "
    SELECT e.enrollment_id, s.first_name, s.last_name, c.course_name, g.marks, g.grade
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
    ORDER BY s.last_name ASC
";
$grades_list = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Grades</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .grade-form { display: flex; gap: 5px; }
        .input-small { width: 60px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; }
        .input-letter { width: 45px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-transform: uppercase; }
        .badge { padding: 3px 8px; border-radius: 10px; font-size: 11px; }
        .badge-null { background: #eee; color: #777; }
        .badge-set { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>🎓 SMS Admin</h2>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="students.php">👨‍🎓 Manage Students</a>
        <a href="courses.php">📚 Manage Courses</a>
        <a href="enrollments.php">📌 Enrollments</a>
        <a href="grades.php" class="active">📝 Manage Grades</a> 
        <a href="reports.php">📜 Student Reports</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Academic Grading</h1>
        </div>

        <?php echo $message; ?>

        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Marks</th>
                    <th>Grade</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $grades_list->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    
                    <form method="POST" action="grades.php">
                        <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                        
                        <td>
                            <input type="number" name="marks" placeholder="0-100" 
                                   value="<?php echo htmlspecialchars($row['marks'] ?? ''); ?>" 
                                   class="input-small" required>
                        </td>
                        <td>
                            <input type="text" name="grade_letter" placeholder="A" 
                                   value="<?php echo htmlspecialchars($row['grade'] ?? ''); ?>" 
                                   class="input-letter" maxlength="2" required>
                        </td>
                        <td>
                            <?php if($row['grade']): ?>
                                <span class="badge badge-set">Completed</span>
                            <?php else: ?>
                                <span class="badge badge-null">Not Set</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="submit" name="save_grade" class="btn" style="padding: 5px 10px; font-size: 12px;">Save</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>