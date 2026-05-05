<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$selected_student = null;
$report_data = [];

// 1. Handle Search/Select Student
if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $student_id = $_GET['student_id'];

    // Fetch Student Info
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $selected_student = $stmt->get_result()->fetch_assoc();

    // Fetch Course Results
    $query = "
        SELECT c.course_name, c.course_code, g.marks, g.grade 
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id
        WHERE e.student_id = ?
    ";
    $stmt2 = $conn->prepare($query);
    $stmt2->bind_param("i", $student_id);
    $stmt2->execute();
    $report_data = $stmt2->get_result();
}

// Get list of all students for the dropdown
$students_list = $conn->query("SELECT student_id, first_name, last_name FROM students ORDER BY first_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Reports</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .report-header { 
            background: #fff; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            margin-bottom: 20px; 
            border-left: 5px solid #3498db; 
        }
        .info-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 10px; 
            margin-top: 15px; 
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .status-pill {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>🎓 SMS Admin</h2>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="students.php">👨‍🎓 Manage Students</a>
        <a href="courses.php">📚 Manage Courses</a>
        <a href="enrollments.php">📌 Enrollments</a>
        <a href="grades.php">📝 Manage Grades</a> 
        <a href="reports.php" class="active">📜 Student Reports</a> 
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <h1>Student Performance Reports</h1>

        <div class="search-box" style="background:white; padding:20px; border-radius:8px; margin-bottom:20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <form method="GET" action="reports.php">
                <label><strong>Filter by Student:</strong></label><br><br>
                <select name="student_id" onchange="this.form.submit()" style="padding:10px; width:100%; max-width:400px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">-- Select Student to View Result --</option>
                    <?php while($s = $students_list->fetch_assoc()): ?>
                        <option value="<?php echo $s['student_id']; ?>" <?php if(isset($_GET['student_id']) && $_GET['student_id'] == $s['student_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($s['first_name'] . " " . $s['last_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <?php if ($selected_student): ?>
            <div class="report-header">
                <h2>Transcript for <?php echo htmlspecialchars($selected_student['first_name'] . " " . $selected_student['last_name']); ?></h2>
                <div class="info-grid">
                    <div><strong>Email:</strong> <?php echo htmlspecialchars($selected_student['email']); ?></div>
                    <div><strong>Phone:</strong> <?php echo htmlspecialchars($selected_student['phone']); ?></div>
                    <div><strong>Gender:</strong> <?php echo htmlspecialchars($selected_student['gender']); ?></div>
                    <div><strong>DOB:</strong> <?php echo htmlspecialchars($selected_student['dob']); ?></div>
                    <div style="grid-column: span 2;"><strong>Address:</strong> <?php echo htmlspecialchars($selected_student['address']); ?></div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Marks</th>
                        <th>Grade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($report_data->num_rows > 0):
                        while($row = $report_data->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                        <td><?php echo $row['marks'] !== null ? $row['marks'] : '—'; ?></td>
                        <td><strong><?php echo $row['grade'] ? htmlspecialchars($row['grade']) : 'Pending'; ?></strong></td>
                        <td>
                            <?php if($row['grade']): ?>
                                <span class="status-pill" style="background:#d4edda; color:#155724;">Finalized</span>
                            <?php else: ?>
                                <span class="status-pill" style="background:#fff3cd; color:#856404;">Awaiting Grade</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px; color: #777;">No active enrollments found for this student.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align:center; padding:50px; background: #fdfdfd; border: 2px dashed #eee; border-radius: 8px;">
                <p style="color: #888; font-size: 18px;">Select a student from the dropdown above to generate their academic report.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>