<?php
session_start();
include 'flickscore_db.php';

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;

if (!$id) {
    die("Filmul nu a fost găsit!");
}

$isWatched = false;
if (isset($_SESSION['user_id'])) {
    $checkWatched = $conn->prepare("SELECT 1 FROM watched_movies WHERE user_id = ? AND movie_id = ?");
    $checkWatched->bind_param("ii", $_SESSION['user_id'], $id);
    $checkWatched->execute();
    $isWatched = $checkWatched->get_result()->num_rows > 0;
}

$sql = "SELECT * FROM movies WHERE id = $id";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    die("Filmul nu a fost găsit!");
}

$sql = "SELECT comment FROM reviews WHERE movie_id = $id";

$movie = $result->fetch_assoc();

if (isset($_SESSION['user_id']) && isset($_POST['action']) && $_POST['action'] === 'mark_watched') {
    $stmt = $conn->prepare("INSERT IGNORE INTO watched_movies (user_id, movie_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $_SESSION['user_id'], $id);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) && is_numeric($_POST['rating']) ? intval($_POST['rating']) : null;
    $comment = isset($_POST['comment']) ? $_POST['comment'] : null;

    if (($rating && $rating >= 1 && $rating <= 10) || ($comment)) {
        $stmt = $conn->prepare("INSERT INTO reviews (movie_id, rating, created_at, comment) VALUES (?, ?, NOW(), ?)");
        $stmt->bind_param("iis", $id, $rating, $comment);

        if ($stmt->execute()) {
            
            $updateStmt = $conn->prepare("
                UPDATE movies 
                SET avg_rating = (SELECT AVG(rating) FROM reviews WHERE movie_id = ?) 
                WHERE id = ?
            ");
            $updateStmt->bind_param("ii", $id, $id);
            $updateStmt->execute();
            $updateStmt->close();

            echo "<p style='color: green; text-align: center;'>Rating-ul a fost adăugat cu succes!</p>";
            echo "<p style='color: green; text-align: center;'>Comentariul a fost adăugat cu succes!</p>";
        } else {
            echo "<p style='color: red; text-align: center;'>Eroare la adăugarea rating-ului: " . $conn->error . "</p>";
        }

        $stmt->close();
    } else {
        echo "<p style='color: red; text-align: center;'>Te rog să introduci un rating valid (1-10).</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - FlickScore</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <a href="index.php" style="text-decoration: none; color: inherit;">
        <h1>FlickScore</h1>
    </a>
</header>
<main>
    <div class="movie-details">
        <img src="images/<?php echo htmlspecialchars($movie['poster']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
        <h2><?php echo htmlspecialchars($movie['title']); ?></h2>
        <p><strong>Gen:</strong> <?php echo htmlspecialchars($movie['genre']); ?></p>
        <p><strong>Data lansării:</strong> <?php echo htmlspecialchars($movie['release_date']); ?></p>
        <p><strong>Rating:</strong> <?php echo number_format($movie['avg_rating'], 1); ?>/10</p>
        <p><strong>Descriere:</strong> <?php echo htmlspecialchars($movie['description']); ?></p>
        <p><strong>Comentarii:</strong> <ul style="list-style-type: none"> <?php  foreach ($conn->query($sql) as $comment): ?>
            
            <li>
                <?php echo ($comment['comment']); ?>
            </li>
            
            <?php endforeach?>
        </ul></p>
        <a href="index.php" class="btn">Înapoi la lista de filme</a>
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($isWatched): ?>
            <button class="btn" disabled>Vizionat ✓</button>
        <?php else: ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="mark_watched">
                <button type="submit" class="btn">Marchează ca vizionat</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <div class="rating-form">
        <h3>Lasă un rating:</h3>
        <form method="POST">
            <label for="rating">Rating (1-10):</label>
            <input type="number" id="rating" name="rating" min="1" max="10" required>
            <button type="submit">Trimite</button>
        </form>
    
    <form method="POST">
    <textarea name="comment" placeholder="Scrie un comentariu:" required></textarea>
    <button type="submit" name="add_comment">Trimite</button>
    </form>
    </div>

</main>
</body>
</html>