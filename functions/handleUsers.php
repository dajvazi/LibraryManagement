<?php
session_start();
require_once __DIR__ . '/../config/dbcon.php';

$currentUserId = ($_SESSION['auth_user']['id'] ?? 0);


// Edit User
if (isset($_POST['updateUserBtn'])) {

    $userId = $_POST['user_id'] ?? 0; 
    $name   = $_POST['name'] ?? '';
    $email  = $_POST['email'] ?? '';
    $role   = $_POST['role'] ?? '';

    // Validations
    if ($userId <= 0 || $name === '' || $email === '' || $role === '') {
        $_SESSION['message'] = "All fields are required!";
        header("Location: ../admin/manageUsers.php");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Please enter a valid email address!";
        header("Location: ../admin/manageUsers.php");
        exit;
    }

    $query = "SELECT id FROM users WHERE id = ?";
    $stmt  = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) === 0) {
        $_SESSION['message'] = "This user doesn't exist";
        header("Location: ../admin/manageUsers.php");
        exit;
    }

    $query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt  = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "si", $email, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $_SESSION['message'] = "This email is already used by another user!";
        header("Location: ../admin/manageUsers.php");
        exit;
    }

    
    if ($userId === $currentUserId && $role !== 'admin') {
        $_SESSION['message'] = "You cannot remove your own admin role!";
        header("Location: ../admin/manageUsers.php");
        exit;
    }

    $query = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
    $stmt  = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $role, $userId);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "User updated successfully!";
        header("Location: ../admin/manageUsers.php");
        exit;
    } else {
        $_SESSION['message'] = "Failed to update user!";
        header("Location: ../admin/manageUsers.php");
        exit;
    }
}


// Delete User
if (isset($_POST['deleteUserBtn'])) {

    $userId = $_POST['user_id'] ?? 0; 

    // Mos lejo admin-in të fshijë veten
    if ($userId === $currentUserId) {
        $_SESSION['message'] = "You cannot delete your own account!";
        header("Location: ../admin/manageUsers.php");
        exit;
    }

    $query = "SELECT id FROM users WHERE id = ?";
    $stmt  = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) ===0) {
        $_SESSION['message'] = "This user doesn't exist!";
        header("Location: ../admin/manageUsers.php");
        exit;
    }

    $query = "DELETE FROM users WHERE id = ?";
    $stmt  = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "User removed successfully!";
        header("Location: ../admin/manageUsers.php");
        exit;
    } else {
        $_SESSION['message'] = "Failed to delete user!";
        header("Location: ../admin/manageUsers.php");
        exit;
    }
}

?>
