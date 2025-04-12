<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DB connection
$host = 'localhost';
$dbname = 'movie-poll-db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Get current user info
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug avatar path (you can remove this after testing)
    // echo "<pre>Current Avatar Path: " . ($user['avatar'] ?? 'NULL') . "</pre>";
    // echo "<pre>File exists: " . (file_exists(__DIR__ . '/' . ($user['avatar'] ?? '')) ? 'YES' : 'NO') . "</pre>";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        $newBio = trim($_POST['bio'] ?? '');
        $avatar = $_FILES['avatar'] ?? null;

        $errors = [];

        if (empty($newUsername)) $errors[] = "Username is required.";
        if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        }

        if (empty($errors)) {
            $conn->beginTransaction();

            try {
                // Update profile fields
                $updateStmt = $conn->prepare("
                    UPDATE users 
                    SET username = :username, email = :email, bio = :bio 
                    WHERE id = :user_id
                ");
                $updateStmt->execute([
                    'username' => $newUsername,
                    'email' => $newEmail,
                    'bio' => $newBio,
                    'user_id' => $_SESSION['user_id']
                ]);

                // Handle avatar upload
                if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                    $type = $avatar['type'];

                    if (!isset($allowed[$type])) {
                        throw new Exception("Invalid image type. Only JPG, PNG, GIF allowed.");
                    }

                    $uploadDir = __DIR__ . '/uploads/avatars/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    if (!is_writable($uploadDir)) {
                        throw new Exception("Upload directory is not writable.");
                    }

                    // Delete old avatar if it exists
                    if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar'])) {
                        @unlink(__DIR__ . '/' . $user['avatar']);
                    }

                    // Save new avatar
                    $fileName = "user_" . $_SESSION['user_id'] . "_" . time() . "." . $allowed[$type];
                    $filePath = $uploadDir . $fileName;
                    $relativePath = "uploads/avatars/" . $fileName;

                    if (!move_uploaded_file($avatar['tmp_name'], $filePath)) {
                        throw new Exception("Failed to move uploaded avatar.");
                    }

                    // Update avatar path
                    $avatarStmt = $conn->prepare("UPDATE users SET avatar = :avatar WHERE id = :user_id");
                    $avatarStmt->execute([
                        'avatar' => $relativePath,
                        'user_id' => $_SESSION['user_id']
                    ]);
                }

                $conn->commit();
                $_SESSION['success_message'] = "Profile updated successfully!";
                header("Location: edit_profile.php");
                exit();

            } catch (Exception $e) {
                $conn->rollBack();
                $error = $e->getMessage();
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Determine the correct avatar path to display
$avatarPath = 'images/default-avatar.jpg'; // Default path
if (!empty($user['avatar'])) {
    $fullPath = __DIR__ . '/' . $user['avatar'];
    if (file_exists($fullPath)) {
        $avatarPath = $user['avatar'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - The Flixx</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #2e8b57; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"], textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;
        }
        textarea { min-height: 100px; resize: vertical; }
        .avatar-preview {
            width: 150px; height: 150px; border-radius: 50%; object-fit: cover;
            border: 3px solid #2e8b57; margin-bottom: 10px;
        }
        .btn {
            background: #2e8b57; color: white; padding: 10px 20px;
            border: none; border-radius: 5px; cursor: pointer; font-size: 16px;
        }
        .btn:hover { background: #3cb371; }
        .message { padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h1><i class='bx bx-edit'></i> Edit Profile</h1>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="bio">Bio</label>
                <textarea name="bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="avatar">Profile Picture</label>
                <img src="<?php echo htmlspecialchars($avatarPath); ?>" class="avatar-preview" id="avatarPreview">
                <input type="file" name="avatar" id="avatar" accept="image/jpeg, image/png, image/gif">
                <small>Max 2MB. JPG, PNG, GIF only.</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">Save Changes</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('avatarPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>