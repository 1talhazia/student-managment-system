<?php
session_start();
require 'db_connect.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$edit_mode = false;
$edit_id = '';
$edit_name = '';
$edit_code = '';

// 2. DELETE LOGIC
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "<p style='color: #e74c3c; font-weight: bold; margin-bottom: 15px;'>Course deleted successfully!</p>";
    }
    $stmt->close();
}

// 3. UPDATE LOGIC: Check if the update form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_course'])) {
    $course_id = $_POST['course_id'];
    $course_name = $_POST['course_name'];
    $course_code = $_POST['course_code'];
    
    $stmt = $conn->prepare("UPDATE courses SET course_name = ?, course_code = ? WHERE course_id = ?");
    $stmt->bind_param("ssi", $course_name, $course_code, $course_id);
    
    if ($stmt->execute()) {
        $message = "<p style='color: #3498db; font-weight: bold; margin-bottom: 15px;'>Course updated successfully!</p>";
    } else {
        $message = "<p style='color: #c0392b; font-weight: bold; margin-bottom: 15px;'>Error updating course.</p>";
    }
    $stmt->close();
}

// 4. INSERT LOGIC: Check if the add form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $course_name = $_POST['course_name'];
    $course_code = $_POST['course_code'];
    
    $stmt = $conn->prepare("INSERT INTO courses (course_name, course_code) VALUES (?, ?)");
    $stmt->bind_param("ss", $course_name, $course_code);
    
    if ($stmt->execute()) {
        $message = "<p style='color: #2ecc71; font-weight: bold; margin-bottom: 15px;'>Course added successfully!</p>";
    } else {
        $message = "<p style='color: #e74c3c; font-weight: bold; margin-bottom: 15px;'>Error adding course.</p>";
    }
    $stmt->close();
}

// 5. FETCH FOR EDITING: If an edit button was clicked, get the data to populate the form
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $edit_id = $_GET['edit_id'];
    
    $stmt = $conn->prepare("SELECT course_name, course_code FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $edit_name = $row['course_name'];
        $edit_code = $row['course_code'];
    }
    $stmt->close();
}

// 6. FETCH ALL LOGIC: Get all courses to display in the table
$result = $conn->query("SELECT course_id, course_name, course_code FROM courses ORDER BY course_id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Courses</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .form-section form {
            box-shadow: none; padding: 0; max-width: 100%;
            display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;
        }
        .form-section .input-group { flex: 1; min-width: 200px; }
        .form-section input { margin-bottom: 0; width: 100%; }
        
        /* New styles for edit buttons */
        .btn-edit { background-color: #f39c12; }
        .btn-edit:hover { background-color: #e67e22; }
        .btn-cancel { background-color: #95a5a6; }
        .btn-cancel:hover { background-color: #7f8c8d; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>🎓 SMS Admin</h2>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="students.php">👨‍🎓 Manage Students</a>
        <a href="courses.php" class="active">📚 Manage Courses</a>
        <a href="enrollments.php">📌 Enrollments</a>
        <a href="grades.php">📝 Manage Grades</a>
        <a href="reports.php">📜 Student Reports</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Course Directory</h1>
        </div>

        <?php echo $message; ?>

        <div class="form-section">
            <h3 style="margin-bottom: 15px; color: #2c3e50;">
                <?php echo $edit_mode ? "✏️ Edit Course" : "Quick Add Course"; ?>
            </h3>
            
            <form method="POST" action="courses.php">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="update_course" value="1">
                    <input type="hidden" name="course_id" value="<?php echo $edit_id; ?>">
                <?php else: ?>
                    <input type="hidden" name="add_course" value="1">
                <?php endif; ?>
                
                <div class="input-group">
                    <label>Course Name:</label>
                    <input type="text" name="course_name" value="<?php echo htmlspecialchars($edit_name); ?>" required>
                </div>
                <div class="input-group">
                    <label>Course Code:</label>
                    <input type="text" name="course_code" value="<?php echo htmlspecialchars($edit_code); ?>" required>
                </div>
                
                <button type="submit" class="btn <?php echo $edit_mode ? 'btn-edit' : ''; ?>">
                    <?php echo $edit_mode ? "💾 Update" : "➕ Add Course"; ?>
                </button>
                
                <?php if ($edit_mode): ?>
                    <a href="courses.php" class="btn btn-cancel">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>Course Name</th>
                <th>Course Code</th>
                <th>Actions</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['course_id']; ?></td>
                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                <td>
                    <a href="courses.php?edit_id=<?php echo $row['course_id']; ?>" class="btn btn-edit" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                    <a href="courses.php?delete_id=<?php echo $row['course_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this course?');" style="padding: 5px 10px; font-size: 12px;">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

</body>
</html>