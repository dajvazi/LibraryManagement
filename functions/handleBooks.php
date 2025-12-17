<?php
    session_start();
    require_once __DIR__ . '/../config/dbcon.php';

//Create Book
    if(isset($_POST['addBookBtn'])){
        $title  = $_POST['title'] ?? '';
        $author = $_POST['author'] ?? '';
        $genre  = $_POST['genre'] ?? '';
        $userId = $_SESSION['auth_user']['id'] ?? 0;

        //Validation
        if($title === "" || $author === "" || $genre === ""){
            $_SESSION['message']="All fields are required!";
            header('Location: ../mybooks.php');
            exit;
        }

        $query = "SELECT id FROM books WHERE user_id = ? AND title = ? AND author = ?";
        $stmt = mysqli_prepare($con,$query);        
        mysqli_stmt_bind_param($stmt, "iss", $userId, $title, $author);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if($result && mysqli_num_rows($result) > 0){
            $_SESSION['message'] = "This book already exists";
            header('Location: ../mybooks.php');
            exit;
        }

        $query = "INSERT INTO books (title, author, genre, user_id) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "sssi", $title, $author, $genre, $userId);

        if(mysqli_stmt_execute($stmt)){
            $_SESSION['message'] = "Book added successfully!";
            header("Location: ../mybooks.php");
            exit;
        } else {
            $_SESSION['message'] = "Failed to add book!";
            header("Location: ../mybooks.php");
            exit;
        }
    }
    

//Edit Book
    if(isset($_POST['updateBookBtn'])){
        $bookId = $_POST['book_id'] ?? 0;
        $title  = $_POST['title'] ?? '';
        $author = $_POST['author'] ?? '';
        $genre  = $_POST['genre'] ?? '';
        $status = $_POST['status'] ?? 'Reading';
        $userId = $_SESSION['auth_user']['id'] ?? 0;

        //Validations
        if($title === '' || $author === '' || $genre === ''){
            $_SESSION['message'] = "All fields are required!";
            header("Location: ../mybooks.php"); exit;
        }

        $query = "SELECT id FROM books WHERE user_id = ? AND title = ? AND author = ? AND id != ?";
        $stmt = mysqli_prepare($con,$query);        
        mysqli_stmt_bind_param($stmt, "issi", $userId, $title, $author, $bookId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if($result && mysqli_num_rows($result) > 0){
            $_SESSION['message'] = "This book doesn't exists";
            header('Location: ../mybooks.php');
            exit;
        }

        if ($_SESSION['auth_user']['role'] == "client") {
            $query = "SELECT id FROM books WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($con, $query);
            mysqli_stmt_bind_param($stmt,'ii', $bookId, $userId);
        }else{
            $query = "SELECT id FROM books WHERE id = ? ";
            $stmt = mysqli_prepare($con, $query);
            mysqli_stmt_bind_param($stmt,'i', $bookId);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) <= 0) {
            $_SESSION['message'] = "!";
            header('Location: ../mybooks.php');
            exit;
        }

        $query = "UPDATE books SET title = ?, author = ?, genre = ?, status = ? WHERE id = ?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "ssssi", $title, $author, $genre, $status, $bookId);

        if(mysqli_stmt_execute($stmt)){
            $_SESSION['message'] = "Book updated successfully!";
            if (($_SESSION['auth_user']['role'] ?? '') === 'admin') {
                header("Location: ../admin/manageBooks.php");
                exit;
            }
            header("Location: ../mybooks.php");
            exit;
        } else {
            $_SESSION['message'] = "Failed to update book!";
            if (($_SESSION['auth_user']['role'] ?? '') === 'admin') {
                header("Location: ../admin/manageBooks.php");
                exit;
            }
            header("Location: ../mybooks.php");
            exit;
        }
    }

//Delete Book
    if(isset($_POST['deleteBookBtn'])){
        $bookId = $_POST['book_id'] ?? 0;
        $userId = $_SESSION['auth_user']['id'] ?? 0;

        //Validations
        if ($_SESSION['auth_user']['role'] == "client") {
            $query = "SELECT id FROM books WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($con, $query);
            mysqli_stmt_bind_param($stmt, 'ii', $bookId, $userId);
        } else { 
            $query = "SELECT id FROM books WHERE id = ?";
            $stmt = mysqli_prepare($con, $query);
            mysqli_stmt_bind_param($stmt, 'i', $bookId);
        }
       
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if($result && mysqli_num_rows($result) <= 0){
            $_SESSION['message'] = "This book doesn't exists";
            header('Location: ../mybooks.php');
            exit;
        }

        if ($_SESSION['auth_user']['role'] == "client") {
            $query = "DELETE FROM books WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($con, $query);
            mysqli_stmt_bind_param($stmt,'ii', $bookId, $userId);
        }else{
            $query = "DELETE FROM books WHERE id = ? ";
            $stmt = mysqli_prepare($con, $query);
            mysqli_stmt_bind_param($stmt,'i', $bookId);
        }

        if(mysqli_stmt_execute($stmt)){
            $_SESSION['message'] = "Book deleted successfully!";
            if (($_SESSION['auth_user']['role'] ?? '') === 'admin') {
                header("Location: ../admin/manageBooks.php");
                exit;
            }
            header("Location: ../mybooks.php");
            exit;
        } else {
            $_SESSION['message'] = "Failed to delete book!";
            if (($_SESSION['auth_user']['role'] ?? '') === 'admin') {
                header("Location: ../admin/manageBooks.php");
                exit;
            }
            header("Location: ../mybooks.php");
            exit;
        }
    }
?>