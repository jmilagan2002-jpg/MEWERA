<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uploadDir = "uploads/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

// Add hero
if (isset($_POST['add_hero'])) {
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $hero_type = $_POST['hero_type'];
    $imagePath = "";

    if (!empty($_FILES['background_image']['name'])) {
        $imageName = basename($_FILES['background_image']['name']);
        $targetFile = $uploadDir . time() . "_" . $imageName;
        if (move_uploaded_file($_FILES['background_image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    $stmt = $conn->prepare("INSERT INTO hero_section (title, subtitle, background_image, hero_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $subtitle, $imagePath, $hero_type);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('New hero added successfully!'); window.location='admin_hero.php';</script>";
    exit();
}

// Update hero
if (isset($_POST['update_hero'])) {
    $hero_id = intval($_POST['hero_id']);
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $hero_type = $_POST['hero_type'];
    $imagePath = $_POST['current_image'] ?? '';

    if (!empty($_FILES['background_image']['name'])) {
        $imageName = basename($_FILES['background_image']['name']);
        $targetFile = $uploadDir . time() . "_" . $imageName;
        if (move_uploaded_file($_FILES['background_image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    $stmt = $conn->prepare("UPDATE hero_section SET title=?, subtitle=?, background_image=?, hero_type=? WHERE id=?");
    $stmt->bind_param("ssssi", $title, $subtitle, $imagePath, $hero_type, $hero_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Hero updated successfully!'); window.location='admin_hero.php';</script>";
    exit();
}

// Delete hero
if (isset($_GET['delete_hero'])) {
    $hero_id = intval($_GET['delete_hero']);
    $stmt = $conn->prepare("DELETE FROM hero_section WHERE id=?");
    $stmt->bind_param("i", $hero_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Hero deleted successfully!'); window.location='admin_hero.php';</script>";
    exit();
}

// Fetch heroes
$heroesResult = $conn->query("SELECT * FROM hero_section ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Hero Section</title>
<style>
body { font-family: Arial; background: #f5f5f5; margin: 0; padding: 20px; }
h2 { margin-top: 30px; }
form { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; max-width: 700px; margin:auto; }
input, textarea, select { width: 100%; padding: 8px; margin-bottom: 10px; }
button { background: #007bff; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer; }
button.delete { background: #dc3545; }
.hero-preview { width: 100%; height: 300px; background-size: cover; background-position: center; border-radius: 10px; margin-top: 10px; }
</style>
</head>
<body>

<?php include __DIR__ . '/admin_navbar.php'; ?>

<h2>Add New Hero</h2>
<form method="POST" enctype="multipart/form-data">
    <label>Hero Type:</label>
    <select name="hero_type" required>
        <option value="homepage">Homepage</option>
        <option value="shop">Shop</option>
    </select>

    <label>Title:</label>
    <input type="text" name="title" required>

    <label>Subtitle:</label>
    <textarea name="subtitle"></textarea>

    <label>Hero Image:</label>
    <input type="file" name="background_image" accept="image/*" required>

    <button type="submit" name="add_hero">Add Hero</button>
</form>

<h2>Existing Heroes</h2>
<?php while($hero = $heroesResult->fetch_assoc()): ?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="hero_id" value="<?php echo $hero['id']; ?>">

    <label>Hero Type:</label>
    <select name="hero_type" required>
        <option value="homepage" <?php if($hero['hero_type']=='homepage') echo 'selected'; ?>>Homepage</option>
        <option value="shop" <?php if($hero['hero_type']=='shop') echo 'selected'; ?>>Shop</option>
    </select>

    <label>Title:</label>
    <input type="text" name="title" value="<?php echo htmlspecialchars($hero['title']); ?>" required>

    <label>Subtitle:</label>
    <textarea name="subtitle"><?php echo htmlspecialchars($hero['subtitle']); ?></textarea>

    <?php if (!empty($hero['background_image'])): ?>
        <div class="hero-preview" style="background-image:url('<?php echo $hero['background_image']; ?>');"></div>
    <?php endif; ?>

    <label>Change Hero Image:</label>
    <input type="file" name="background_image" accept="image/*">
    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($hero['background_image']); ?>">

    <button type="submit" name="update_hero">Update Hero</button>
    <a href="admin_hero.php?delete_hero=<?php echo $hero['id']; ?>" onclick="return confirm('Delete this hero?')">
        <button type="button" class="delete">Delete Hero</button>
    </a>
</form>
<?php endwhile; ?>

</body>
</html>
