<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $full_name = $_POST["full_name"];

    $sql = "INSERT INTO users (username, email, password_hash, full_name)
            VALUES ('$username', '$email', '$password', '$full_name')";

    if (mysqli_query($conn, $sql)) {
        echo "Registration successful";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <input type="text" name="full_name" placeholder="Full Name" required><br><br>
    <button type="submit">Register</button>
</form>
