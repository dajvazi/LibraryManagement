<?php
include __DIR__ . '/../config/dbcon.php';

function getAll($tablename){
    global $con;


    $userId = ($_SESSION['auth_user']['id'] ?? 0);

    if($_SESSION['auth_user']['role'] == "client"){
        $query = "SELECT * FROM $tablename WHERE user_id = ? ORDER BY id DESC";
        $stmt  = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
    }else{
        $query = "SELECT * FROM $tablename ORDER BY id DESC";
        $stmt  = mysqli_prepare($con, $query);

    }
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_get_result($stmt);
}

function getAllBooksForManage(){
    global $con;
    
    $query = "SELECT b.*, u.name AS posted_by
                      FROM books b
                      JOIN users u ON u.id = b.user_id
                      ORDER BY b.id DESC";

    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_get_result($stmt);
}

function getCountNo($tablename){
    global $con;

    $userId = ($_SESSION['auth_user']['id'] ?? 0);

    if($_SESSION['auth_user']['role'] == "client" && $tablename == "users"){
        $query = "SELECT COUNT(*) AS total FROM $tablename WHERE id = ?";
        $stmt  = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
    }elseif ($_SESSION['auth_user']['role'] == "client" && $tablename == "books") {
        $query = "SELECT COUNT(*) AS total FROM $tablename WHERE user_id = ?";
        $stmt  = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        }
    else{
        $query = "SELECT COUNT(*) AS total FROM $tablename";
        $stmt  = mysqli_prepare($con, $query);

    }
    
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return ($row['total'] ?? 0);
}

function getRecentBooks(){
    global $con;

    $userId = ($_SESSION['auth_user']['id'] ?? 0);

    if($_SESSION['auth_user']['role'] == "client"){
        $query = "SELECT * FROM books WHERE user_id = ? ORDER BY added_at DESC";
        $stmt  = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
    }else{
        $query = "SELECT b.*, u.name AS posted_by
                    FROM books b
                    INNER JOIN users u ON u.id = b.user_id
                    ORDER BY b.added_at DESC;";
        $stmt  = mysqli_prepare($con, $query);
    }


    
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_get_result($stmt);
}

function getTodaysBooksCount(){
    global $con;

    $userId = ($_SESSION['auth_user']['id'] ?? 0);
    $today = date('Y-m-d');

   if($_SESSION['auth_user']['role'] == "client"){
        $query = "SELECT COUNT(*) AS total FROM books WHERE user_id = ? AND DATE(added_at) = ?";
        $stmt  = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "is", $userId, $today);
    } else {
        $query = "SELECT COUNT(*) AS total FROM books WHERE DATE(added_at) = ?";
        $stmt  = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "s", $today);
    }
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return ($row['total'] ?? 0);
}

function getReadingBooksCount(){
    global $con;

    $userId = ($_SESSION['auth_user']['id'] ?? 0);
    $status = 'reading';

    if($_SESSION['auth_user']['role'] == "client"){
        $query = "SELECT COUNT(*) AS total FROM books WHERE status = ? AND user_id=?";
        $stmt  = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "si",$status, $userId);
    }else{
        $query = "SELECT COUNT(*) AS total FROM books WHERE status = ?";
        $stmt  = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "s", $status);

    }

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return ($row['total'] ?? 0);
}

function getCompletedBooksCount(){
    global $con;

    $userId = ($_SESSION['auth_user']['id'] ?? 0);
    $status = 'completed';

    if($_SESSION['auth_user']['role'] == "client"){
        $query = "SELECT COUNT(*) AS total FROM books WHERE status = ? AND user_id=?";
        $stmt  = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "si",$status, $userId);
    }else{
        $query = "SELECT COUNT(*) AS total FROM books WHERE status = ?";
        $stmt  = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "s", $status);

    }

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return ($row['total'] ?? 0);
}
?>
