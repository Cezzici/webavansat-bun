<?php
session_start();
include 'flickscore_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT m.* FROM movies m 
        INNER JOIN watched_movies w ON m.id = w.movie_id 
        WHERE w.user_id = ? 
        ORDER BY w.watched_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista mea - FlickScore</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <a href="index.php" style="text-decoration: none; color: inherit;">
            <h1>FlickScore</h1>
        </a>
        <div class="header-buttons">
            <a href="index.php" class="btn">Înapoi la filme</a>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </header>
    <main>
        <h2>Filmele mele vizionate</h2>
        <div class="movies-container">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="movie-card">';
                    echo '<img src="images/' . htmlspecialchars($row['poster']) . '" alt="' . htmlspecialchars($row['title']) . '">';
                    echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                    echo '<p>' . htmlspecialchars($row['genre']) . '</p>';
                    echo '<p>Rating: ' . number_format($row['avg_rating'], 1) . '/10</p>';
                    echo '<a href="movie.php?id=' . $row['id'] . '" class="btn">Detalii</a>';
                    echo '</div>';
                }
            } else {
                echo '<p>Nu ai marcat niciun film ca vizionat încă.</p>';
            }
            ?>
        </div>
    </main>
</body>
</html> 