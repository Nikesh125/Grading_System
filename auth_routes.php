<?php
require_once 'db.php';
session_start();

// ROUTE 1: TEACHER REGISTRATION
if (isset($_POST['Reg-teacher-Submit'])) {
    $symbol_no = $conn->real_escape_string(trim($_POST['symbol_no'])); 
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $role = 'teacher';

    $check = $conn->query("SELECT id FROM users WHERE symbol_no='$symbol_no'");
    if ($check->num_rows > 0) {
        header("Location: register-teacher.html?error=duplicate");
        exit();
    }

    $sql = "INSERT INTO users (symbol_no, username, password, role, phone, email_or_class) 
            VALUES ('$symbol_no', '$username', SHA2('$password', 256), '$role', '$phone', '$email')";

    if ($conn->query($sql)) {
        header("Location: index.html?success=registered");
    } else {
        echo "Registration error: " . $conn->error;
    }
}

// ROUTE 2: STUDENT REGISTRATION
if (isset($_POST['Reg-student-Submit'])) {
    $symbol_no = $conn->real_escape_string(trim($_POST['symbol_no'])); 
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    
    $class_input = isset($_POST['semester']) ? $_POST['semester'] : $_POST['class'];
    $class_value = $conn->real_escape_string(trim($class_input));
    $role = 'student';

    $check = $conn->query("SELECT id FROM users WHERE symbol_no='$symbol_no'");
    if ($check->num_rows > 0) {
        header("Location: register-student.html?error=duplicate");
        exit();
    }

    $sql = "INSERT INTO users (symbol_no, username, password, role, phone, email_or_class) 
            VALUES ('$symbol_no', '$username', SHA2('$password', 256), '$role', '$phone', '$class_value')";

    if ($conn->query($sql)) {
        header("Location: index.html?success=registered");
    } else {
        echo "Registration error: " . $conn->error;
    }
}

// ROUTE 3: CORRECTED SYSTEM LOGIN INTERACTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['symbol_no']) && isset($_POST['password'])) {
    $symbol_no = $conn->real_escape_string(trim($_POST['symbol_no']));
    $password = trim($_POST['password']);

    // Checking by strict unique symbol_no column instead of non-unique username string
    $sql = "SELECT id, username, role, email_or_class FROM users 
            WHERE symbol_no='$symbol_no' AND password=SHA2('$password', 256)";
    
    $result = $conn->query($sql);

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username']; // Keeps display name operational
        $_SESSION['role'] = $user['role'];
        $_SESSION['LAST_ACTIVITY'] = time();

        header("Location: dashboard.php");
        exit();
    } else {
        header("Location: index.html?error=invalid");
        exit();
    }
}
?>