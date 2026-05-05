<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// --- 1. DELETE LOGIC (Enhanced for Relational Safety) ---
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Start a transaction to ensure all or nothing is deleted
    $conn->begin_transaction();

    try {
        // Step A: Delete grades associated with this student's enrollments
        $stmt1 = $conn->prepare("DELETE grades FROM grades 
                                 INNER JOIN enrollments ON grades.enrollment_id = enrollments.enrollment_id 
                                 WHERE enrollments.student_id = ?");
        $stmt1->bind_param("i", $delete_id);
        $stmt1->execute();

        // Step B: Delete enrollments associated with this student
        $stmt2 = $conn->prepare("DELETE FROM enrollments WHERE student_id = ?");
        $stmt2->bind_param("i", $delete_id);
        $stmt2->execute();

        // Step C: Finally, delete the student
        $stmt3 = $conn->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt3->bind_param("i", $delete_id);
        $stmt3->execute();

        // Commit the changes
        $conn->commit();
        
        header("Location: students.php?msg=deleted");
        exit();

    } catch (Exception $e) {
        // If something goes wrong, undo everything
        $conn->rollback();
        $message = "<p style='color: red;'>Error: Could not delete student. " . $e->getMessage() . "</p>";
    }
}

// --- 2. NOTIFICATION LOGIC ---
if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $message = "<p style='color: #e74c3c; font-weight: bold;'>Record deleted successfully!</p>";
}

// --- 3. UPDATE & INSERT LOGIC (With 18+ Age Check) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $dob        = $_POST['dob'];
    $gender     = $_POST['gender'];
    $address    = trim($_POST['address']);

    $errors = [];

    // 1. Name Check
    if (!preg_match("/^[a-zA-Z ]*$/", $first_name) || !preg_match("/^[a-zA-Z ]*$/", $last_name)) {
        $errors[] = "Names should only contain letters and spaces.";
    }

    // 2. Email Check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // 3. Phone Check
    if (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $errors[] = "Phone number must be between 10 and 15 digits.";
    }

    // 4. FIXED AGE CHECK: Must be 18 or older
    $today = new DateTime();
    $birthDate = new DateTime($dob);
    $age = $today->diff($birthDate)->y;

    if ($birthDate > $today) {
        $errors[] = "Date of Birth cannot be in the future.";
    } elseif ($age < 18) {
        $errors[] = "Student must be at least 18 years old to register.";
    }

    // --- Process Database Actions ---
    if (empty($errors)) {
        if (isset($_POST['update_student'])) {
            // UPDATE ACTION
            $student_id = $_POST['student_id'];
            $stmt = $conn->prepare("UPDATE students SET first_name=?, last_name=?, email=?, phone=?, dob=?, gender=?, address=? WHERE student_id=?");
            $stmt->bind_param("sssssssi", $first_name, $last_name, $email, $phone, $dob, $gender, $address, $student_id);
            
            if ($stmt->execute()) {
                $message = "<p style='color: #3498db; font-weight: bold;'>✅ Student updated successfully!</p>";
            } else {
                $message = "<p style='color: #e74c3c; font-weight: bold;'>Error: " . $conn->error . "</p>";
            }
            $stmt->close();

        } else if (isset($_POST['add_student'])) {
            // INSERT ACTION
            $check_email = $conn->prepare("SELECT email FROM students WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            
            if ($check_email->get_result()->num_rows > 0) {
                $message = "<p style='color: #e74c3c; font-weight: bold;'>⚠️ This email is already registered!</p>";
            } else {
                $stmt = $conn->prepare("INSERT INTO students (first_name, last_name, email, phone, dob, gender, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $dob, $gender, $address);
                
                if ($stmt->execute()) {
                    $message = "<p style='color: #2ecc71; font-weight: bold;'>✅ Student added successfully!</p>";
                } else {
                    $message = "<p style='color: #e74c3c; font-weight: bold;'>Database Error: Unable to save.</p>";
                }
                $stmt->close();
            }
        }
    } else {
        // Display validation errors
        $message = "<div style='color: #e74c3c; font-weight: bold; background: #fdf2f2; padding: 10px; border: 1px solid #e74c3c; border-radius: 5px;'>";
        foreach ($errors as $error) {
            $message .= "• " . $error . "<br>";
        }
        $message .= "</div>";
    }
}

// --- 4. DATA FETCHING ---
$edit_mode = false;
$edit_data = ['first_name'=>'','last_name'=>'','email'=>'','phone'=>'','dob'=>'','gender'=>'','address'=>''];

if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = $_GET['edit_id'];
    $res = $conn->query("SELECT * FROM students WHERE student_id = $id");
    if ($row = $res->fetch_assoc()) $edit_data = $row;
}

$result = $conn->query("SELECT * FROM students ORDER BY student_id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Students</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .grid-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .input-group { display: flex; flex-direction: column; }
        .full-width { grid-column: 1 / -1; }
        input, select, textarea { padding: 8px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px; }
        .btn-del { background: #e74c3c; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; }
        .btn-edit { background: #f39c12; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; margin-right: 5px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>🎓 SMS Admin</h2>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="students.php" class="active">👨‍🎓 Manage Students</a>
        <a href="courses.php">📚 Manage Courses</a>
        <a href="enrollments.php">📌 Enrollments</a>
        <a href="grades.php">📝 Manage Grades</a>
        <a href="reports.php">📜 Student Reports</a> 
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <h1>Student Records</h1>
        <?php echo $message; ?>

        <div class="form-section">
            <h3><?php echo $edit_mode ? "✏️ Edit Student" : "➕ Add Student"; ?></h3>
            <form method="POST" class="grid-form">
                <input type="hidden" name="<?php echo $edit_mode ? 'update_student' : 'add_student'; ?>" value="1">
                <?php if($edit_mode): ?><input type="hidden" name="student_id" value="<?php echo $id; ?>"><?php endif; ?>

                <div class="input-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo $edit_data['first_name']; ?>" required>
                </div>
                <div class="input-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo $edit_data['last_name']; ?>" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $edit_data['email']; ?>" required>
                </div>
                <div class="input-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo $edit_data['phone']; ?>" required>
                </div>
                <div class="input-group">
                    <label>DOB</label>
                    <input type="date" name="dob" value="<?php echo $edit_data['dob']; ?>" required>
                </div>
                <div class="input-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="Male" <?php if($edit_data['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if($edit_data['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                        <option value="Other" <?php if($edit_data['gender'] == 'Other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>
                <div class="input-group full-width">
                    <label>Address</label>
                    <textarea name="address" required><?php echo $edit_data['address']; ?></textarea>
                </div>
                <div class="full-width">
                    <button type="submit" class="btn"><?php echo $edit_mode ? "Update" : "Add Student"; ?></button>
                    <?php if($edit_mode): ?><a href="students.php" style="margin-left:10px;">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>

        <table>
            <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Details</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo $row['first_name']." ".$row['last_name']; ?></strong></td>
                <td><?php echo $row['email']; ?><br><?php echo $row['phone']; ?></td>
                <td><?php echo $row['gender']; ?><br><?php echo $row['dob']; ?></td>
                <td><?php echo $row['address']; ?></td>
                <td>
                    <a href="students.php?edit_id=<?php echo $row['student_id']; ?>" class="btn-edit">Edit</a>
                    <a href="students.php?delete_id=<?php echo $row['student_id']; ?>" class="btn-del" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>