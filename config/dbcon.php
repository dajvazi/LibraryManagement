<?php 

    // $host     = getenv('DB_HOST') ?: 'db';
    // $username = getenv('DB_USER') ?: 'root';
    // $password = getenv('DB_PASS') ?: '';
    // $database = getenv('DB_NAME') ?: 'libmanagement';

    // $con = mysqli_connect($host, $username, $password, $database);

    // if (!$con) {
    //     die("Connection failed: " . mysqli_connect_error());
    // }

    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "libmanagement";
    $port = 3306;

    //Db Connection
    $con  = mysqli_connect($host, $user, $password, $database, $port);

    if(!$con){
        die("Connection failed: " . mysqli_connect_error());
    }else{
        // echo "Connected successfully";
    }