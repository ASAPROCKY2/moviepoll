<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'movie-poll-db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $avatar = $_FILES['avatar'] ?? null;

        // Basic validation
        $errors = [];
        if (empty($username)) {
            $errors[] = "Username is required";
        }
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($errors)) {
            // Begin transaction
            $conn->beginTransaction();
            
            try {
                // Update user data
                $updateStmt = $conn->prepare("
                    UPDATE users 
                    SET username = :username, 
                        email = :email, 
                        bio = :bio 
                    WHERE id = :user_id
                ");
                $updateStmt->bindParam(':username', $username);
                $updateStmt->bindParam(':email', $email);
                $updateStmt->bindParam(':bio', $bio);
                $updateStmt->bindParam(':user_id', $_SESSION['user_id']);
                
                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update profile data");
                }

                // Handle avatar upload if provided
                if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
                    // Debug: Output file info
                    error_log("Avatar upload attempted: " . print_r($avatar, true));
                    
                    // Validate image file
                    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                    if (array_key_exists($avatar['type'], $allowedTypes)) {
                        $targetDir = __DIR__ . "/uploads/avatars/";
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($targetDir)) {
                            if (!mkdir($targetDir, 0777, true)) {
                                throw new Exception("Failed to create upload directory");
                            }
                        }
                        
                        // Delete old avatar if exists
                        if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                            if (!unlink($user['avatar'])) {
                                error_log("Failed to delete old avatar: " . $user['avatar']);
                            }
                        }
                        
                        $fileExt = $allowedTypes[$avatar['type']];
                        $fileName = "user_" . $_SESSION['user_id'] . "_" . time() . "." . $fileExt;
                        $targetFile = $targetDir . $fileName;
                        $relativePath = "uploads/avatars/" . $fileName;
                        
                        if (move_uploaded_file($avatar['tmp_name'], $targetFile)) {
                            // Update avatar path in database
                            $avatarStmt = $conn->prepare("UPDATE users SET avatar = :avatar WHERE id = :user_id");
                            $avatarStmt->bindParam(':avatar', $relativePath);
                            $avatarStmt->bindParam(':user_id', $_SESSION['user_id']);
                            
                            if (!$avatarStmt->execute()) {
                                throw new Exception("Failed to update avatar in database");
                            }
                            
                            // Verify the update
                            $checkStmt = $conn->prepare("SELECT avatar FROM users WHERE id = :user_id");
                            $checkStmt->bindParam(':user_id', $_SESSION['user_id']);
                            $checkStmt->execute();
                            $updatedAvatar = $checkStmt->fetchColumn();
                            
                            if ($updatedAvatar !== $relativePath) {
                                throw new Exception("Avatar path mismatch after update");
                            }
                        } else {
                            throw new Exception("Failed to move uploaded file");
                        }
                    } else {
                        throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
                    }
                }

                $conn->commit();
                $_SESSION['success_message'] = "Profile updated successfully!";
                
                // Refresh user data
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                header("Location: edit_profile.php");
                exit();
                
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Error: " . $e->getMessage();
                error_log($error);
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - The Flixx</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        
        .edit-profile-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            text-align: center;
            color: #2e8b57;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px 0;
            border: 3px solid #2e8b57;
        }
        
        .btn {
            padding: 10px 20px;
            background: #2e8b57;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background: #3cb371;
        }
        
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="edit-profile-container">
        <h1><i class='bx bx-edit'></i> Edit Profile</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="edit_profile.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="bio">Bio</label>
                <textarea id="bio" name="bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="avatar">Profile Picture</label>
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" class="avatar-preview" id="avatarPreview">
                <?php else: ?>
                    <img src="images/default-avatar.jpg" class="avatar-preview" id="avatarPreview">
                <?php endif; ?>
                <input type="file" id="avatar" name="avatar" accept="image/*">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Save Changes</button>
            </div>
        </form>
    </div>
    
    <script>
        // Preview avatar image before upload
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html><