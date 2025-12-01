<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = md5($_POST['password']); // match the hashed password in DB

    $sql = "SELECT * FROM admins WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $_SESSION['admin'] = $email; // store session
        header("Location: dashboard.php"); // redirect to dashboard
        exit();
    } else {
        echo "<script>alert('Invalid email or password'); window.location='index.html';</script>";
    }
}
?>
