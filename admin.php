<?php
session_start();

include 'flickscore_db.php';

if (!isset($_SESSION['admin'])) {
    if (isset($_COOKIE['admin_token'])) {
        $token = $_COOKIE['admin_token'];
        $stmt = $conn->prepare("SELECT * FROM admins WHERE session_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['admin'] = true;
        } else {
            header('Location: login.php');
            exit;
        }
        $stmt->close();
    } else {
        header('Location: login.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $genre = $_POST['genre'];
    $release_date = $_POST['release_date'];
    $poster = $_FILES['poster'];

    if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $release_date) || strtotime($release_date) === false) {
        $error = "Data lansării este invalidă!";
    } elseif ($poster['error'] === UPLOAD_ERR_OK) {
    
        $posterName = basename($poster['name']);
        $targetDir = "images/";
        $targetFile = $targetDir . $posterName;

        if (move_uploaded_file($poster['tmp_name'], $targetFile)) {
            
            $stmt = $conn->prepare("INSERT INTO movies (title, description, genre, release_date, poster, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $title, $description, $genre, $release_date, $posterName);

            if ($stmt->execute()) {
                $success = "Filmul a fost adăugat cu succes!";
            } else {
                $error = "Eroare la adăugarea filmului: " . $conn->error;
            }

            $stmt->close();
        } else {
            $error = "Eroare la încărcarea posterului.";
        }
    } else {
        $error = "Te rog să încarci un fișier valid pentru poster.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - FlickScore</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="header-container">
        <h1>Admin FlickScore</h1>
        <nav>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                <a href="index.php" class="btn">Pagina principală</a>
                <a href="logout.php" class="btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn">Admin Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
    <div class="admin-container">
        <h2>Filme existente</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titlu</th>
                    <th>Gen</th>
                    <th>Data lansării</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $moviesResult = $conn->query("SELECT * FROM movies ORDER BY created_at DESC");
                if ($moviesResult->num_rows > 0) {
                    while ($movie = $moviesResult->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($movie['id']) . '</td>';
                        echo '<td>' . htmlspecialchars($movie['title']) . '</td>';
                        echo '<td>' . htmlspecialchars($movie['genre']) . '</td>';
                        echo '<td>' . htmlspecialchars($movie['release_date']) . '</td>';
                        echo '<td>';
                        echo '<a href="edit_movie.php?id=' . $movie['id'] . '" class="btn small">Modifică</a>';
                        echo ' ';
                        echo '<a href="delete_movie.php?id=' . $movie['id'] . '" class="btn small danger" onclick="return confirm(\'Ești sigur că vrei să ștergi acest film?\')">Șterge</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">Nu există filme în baza de date.</td></tr>';
                }
                ?>
            </tbody>
        </table>

        <h2>Adaugă un film nou</h2>
        <?php if (isset($success)): ?>
            <p class="success-message"><?php echo $success; ?></p>
        <?php elseif (isset($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="form-group">
                <label for="title">Titlu:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="description">Descriere:</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="genre">Gen:</label>
                <input type="text" id="genre" name="genre" required>
            </div>
            <div class="form-group">
                <label for="release_date">Data lansării:</label>
                <input type="date" id="release_date" name="release_date" required>
            </div>
            <div class="form-group">
                <label for="poster">Poster:</label>
                <input type="file" id="poster" name="poster" accept="image/*" required>
            </div>
            <button type="submit" name="add_movie" class="btn">Adaugă Film</button>
        </form>
    </div>
</main>
</body>
</html>