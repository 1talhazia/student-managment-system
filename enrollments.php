<?php
session_start();
require 'db_connect.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// 2. UNENROLL LOGIC (Delete from enrollment table)
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM enrollments WHERE enrollment_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "<p style='color: #e74c3c; font-weight: bold; margin-bottom: 15px;'>Student unenrolled successfully!</p>";
    }
    $stmt->close();
}

// 3. ENROLLMENT LOGIC (Insert into enrollment table)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_enrollment'])) {
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];
    
    // Check if this student is already in this course to prevent duplicates
    $check = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND course_id = ?");
    $check->bind_param("ii", $student_id, $course_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $message = "<p style='color: #f39c12; font-weight: bold; margin-bottom: 15px;'>Warning: Student is already enrolled in this course.</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $student_id, $course_id);
        if ($stmt->execute()) {
            $message = "<p style='color: #2ecc71; font-weight: bold; margin-bottom: 15px;'>Enrollment completed successfully!</p>";
        }
        $stmt->close();
    }
    $check->close();
}

// 4. DATA FETCHING (Joining 3 tables to show names instead of just IDs)
$enrollment_list = $conn->query("
    SELECT e.enrollment_id, s.first_name, s.last_name, c.course_name, c.course_code 
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN courses c ON e.course_id = c.course_id
    ORDER BY e.enrollment_id DESC
");

// Get lists for the dropdown menus
$all_students = $conn->query("SELECT student_id, first_name, last_name FROM students ORDER BY first_name ASC");
$all_courses = $conn->query("SELECT course_id, course_name FROM courses ORDER BY course_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enrollment Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .enroll-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .enroll-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .enroll-form .group { flex: 1; min-width: 200px; }
        .enroll-form select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>🎓 SMS Admin</h2>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="students.php">👨‍🎓 Manage Students</a>
        <a href="courses.php">📚 Manage Courses</a>
        <a href="enrollments.php" class="active">📌 Enrollments</a>
        <a href="grades.php">📝 Manage Grades</a>
        <a href="reports.php">📜 Student Reports</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Course Enrollments</h1>
        </div>

        <?php echo $message; ?>

        <div class="enroll-container">
            <h3>📌 New Enrollment</h3>
            <form method="POST" action="enrollments.php" class="enroll-form">
                <input type="hidden" name="add_enrollment" value="1">
                
                <div class="group">
                    <label>Select Student</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php while($s = $all_students->fetch_assoc()): ?>
                            <option value="<?php echo $s['student_id']; ?>">
                                <?php echo htmlspecialchars($s['first_name'] . " " . $s['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="group">
                    <label>Select Course</label>
                    <select name="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php while($c = $all_courses->fetch_assoc()): ?>
                            <option value="<?php echo $c['course_id']; ?>">
                                <?php echo htmlspecialchars($c['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn">Confirm Enrollment</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Enrollment ID</th>
                    <th>Student Name</th>
                    <th>Course Name</th>
                    <th>Course Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $enrollment_list->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['enrollment_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                    <td>
                        <a href="enrollments.php?delete_id=<?php echo $row['enrollment_id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Remove student from this course?');"
                           style="padding: 5px 10px; font-size: 12px;">Unenroll</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>