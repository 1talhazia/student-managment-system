<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch totals
$student_query = $conn->query("SELECT COUNT(*) AS total FROM students");
$total_students = $student_query->fetch_assoc()['total'];

$course_query = $conn->query("SELECT COUNT(*) AS total FROM courses");
$total_courses = $course_query->fetch_assoc()['total'];

// Fetch total enrollments
$enrollment_count_query = "SELECT COUNT(*) as total FROM enrollments";
$enrollment_count_result = $conn->query($enrollment_count_query);
$enrollment_total = $enrollment_count_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="sidebar">
        <h2>🎓 SMS Admin</h2>
        <a href="dashboard.php" class="active">📊 Dashboard</a> 
        <a href="students.php">👨‍🎓 Manage Students</a>
        <a href="courses.php">📚 Manage Courses</a>
        <a href="enrollments.php">📌 Enrollments</a>
        <a href="grades.php">📝 Manage Grades</a>
        <a href="reports.php">📜 Student Reports</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard Overview</h1>
            <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong></p>
        </div>
        
        <div class="card-container">
            <div class="card">
                <h3>Total Students</h3>
                <h2><?php echo $total_students; ?></h2>
            </div>
            <div class="card">
                <h3>Total Courses</h3>
                <h2><?php echo $total_courses; ?></h2>
            </div>
            <div class="card" style="border-top-color: #2ecc71;">
                <h3>System Status</h3>
                <h2>Online</h2>
            </div>
            <div class="card">
                <h3>Total Enrollments</h3>
                <h2><?php echo $enrollment_total; ?></p>
            </div>
        </div>
    </div>

</body>
</html>