<?php
session_start();
include 'flickscore_db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password FROM movie_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Parolă incorectă.";
        }
    } else {
        $error = "Username-ul nu există.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare - FlickScore</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <a href="index.php" style="text-decoration: none; color: inherit;">
            <h1>FlickScore</h1>
        </a>
    </header>
    <main>
        <div class="auth-container">
            <h2>Autentificare</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Parolă:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Autentificare</button>
            </form>
            <p>Nu ai cont? <a href="register.php">Înregistrează-te aici</a></p>
        </div>
    </main>
</body>
</html> 