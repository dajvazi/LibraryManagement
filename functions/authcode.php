<?php
session_start();
include_once('../config/dbcon.php');

?>
<script>
    console.log("test");
</script>
<?php
if(isset($_POST['registerBtn'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $role = 'client';

    if($name === "" || $email === "" || $password === ""){
        $_SESSION['message']="All fields are required!";
        header('Location: ../register.php');
        exit;
    }

    if (strpos($email, '@') === false || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Please enter a valid email (must contain @).";
        header('Location: ../register.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['message'] = "Password must be at least 6 characters.";
        header('Location: ../register.php');
        exit;
    }

    if ($password !== $confirmPassword) {
    $_SESSION['message'] = "Passwords do not match!";
    header('Location: ../register.php');
    exit;
}

    $query = "SELECT COUNT(*) AS total FROM users";
    $result = mysqli_query($con, $query);
    $totalUsers = mysqli_fetch_assoc($result);

    if ($totalUsers['total'] == 0) {
        $role= 'admin';
    }

    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($con,$query);        
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if($result && mysqli_num_rows($result) > 0){
        $_SESSION['message'] = "This user already exists";
        header('Location: ../register.php');
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($con, "INSERT INTO users (name, email, password,role) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $password_hash ,$role);

    if(mysqli_stmt_execute($stmt)){
        $_SESSION['message'] = "Registered successfully!";
        header("Location: ../login.php");
        exit;
    } else {
        $_SESSION['message'] = "Registration failed!";
        header("Location: ../register.php");
        exit;
    }
}

if (isset($_POST['loginBtn'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if($email === "" || $password === ""){
        $_SESSION['message']="All fields are required!";
        header('Location: ../login.php');
        exit;
    }
    
    $stmt = mysqli_prepare($con, "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    

    if(mysqli_stmt_num_rows($stmt) == 1){
        mysqli_stmt_bind_result($stmt, $id, $name, $email, $hashed_password, $role);
        mysqli_stmt_fetch($stmt);

        if(password_verify($password, $hashed_password)){
            $_SESSION['auth'] = true;
            $_SESSION['auth_user'] = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => $role
            ];

            $_SESSION['message'] = "Login successful!";

            if($_SESSION['auth_user']['role'] == "admin"){
                header("Location: ../admin/index.php");
                exit;
            }else{
                header("Location: ../index.php");
                exit;
            }
            
        } else {
            $_SESSION['message'] = "Invalid password!";
            header("Location: ../login.php");
            exit;
        }
    } else {
        $_SESSION['message'] = "No account found with that email!";
        header("Location: ../login.php");
        exit;
    }
}
?>
