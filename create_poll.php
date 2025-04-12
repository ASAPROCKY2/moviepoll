<?php
// Database connection with error handling
$db = null;
try {
    $db = new mysqli('localhost', 'root', '', 'movie-poll-db');
    
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    
    // Create tables if they don't exist
    $db->query("CREATE TABLE IF NOT EXISTS polls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question VARCHAR(255) NOT NULL,
        expiry_date DATETIME NULL,
        categories VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->query("CREATE TABLE IF NOT EXISTS poll_options (
        id INT AUTO_INCREMENT PRIMARY KEY,
        poll_id INT NOT NULL,
        text VARCHAR(255) NOT NULL,
        image TEXT NULL,
        trailer TEXT NULL,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
    )");
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Handle file uploads
function handleFileUpload($file, $uploadDir = 'uploads/') {
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = uniqid() . '-' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath;
    }
    return null;
}

// Process form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['question'])) {
            throw new Exception('Poll question is required');
        }
        
        if (empty($_POST['options']) || count($_POST['options']) < 2) {
            throw new Exception('At least two options are required');
        }
        
        // Insert poll
        $question = $db->real_escape_string($_POST['question']);
        $expiry_date = !empty($_POST['expiry_date']) 
            ? "'" . $db->real_escape_string($_POST['expiry_date']) . "'" 
            : 'NULL';
        $categories = !empty($_POST['categories']) 
            ? "'" . $db->real_escape_string($_POST['categories']) . "'" 
            : 'NULL';
        
        $query = "INSERT INTO polls (question, expiry_date, categories) 
                 VALUES ('$question', $expiry_date, $categories)";
        
        if (!$db->query($query)) {
            throw new Exception('Failed to create poll: ' . $db->error);
        }
        
        $poll_id = $db->insert_id;
        
        // Insert options with images and trailers
        foreach ($_POST['options'] as $index => $option) {
            $text = $db->real_escape_string($option['text']);
            $trailer = $db->real_escape_string($option['trailer']);
            
            // Handle image upload - correctly access the file array
            $imagePath = null;
            if (!empty($_FILES['options']['name'][$index]['image'])) {
                $fileArray = [
                    'name' => $_FILES['options']['name'][$index]['image'],
                    'type' => $_FILES['options']['type'][$index]['image'],
                    'tmp_name' => $_FILES['options']['tmp_name'][$index]['image'],
                    'error' => $_FILES['options']['error'][$index]['image'],
                    'size' => $_FILES['options']['size'][$index]['image']
                ];
                
                $imagePath = handleFileUpload($fileArray);
            }
            
            $query = "INSERT INTO poll_options 
                      (poll_id, text, image, trailer) 
                      VALUES ($poll_id, '$text', " . 
                      ($imagePath ? "'$imagePath'" : "NULL") . ", " .
                      (!empty($trailer) ? "'$trailer'" : "NULL") . ")";
            
            if (!$db->query($query)) {
                throw new Exception('Failed to add options: ' . $db->error);
            }
        }
        
        $success = 'Poll created successfully!';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Poll Creator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3f37c9;
            --secondary: #4895ef;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --success: #4cc9f0;
            --danger: #f72585;
            --gray: #adb5bd;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --radius: 12px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }

        .poll-creator {
            max-width: 900px;
            margin: 2rem auto;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .creator-header {
            background: var(--primary);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .creator-body {
            padding: 2rem;
        }

        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--light);
            border-radius: var(--radius);
        }

        .form-section h5 {
            color: var(--primary);
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .option-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .option-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .option-preview {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
            align-items: center;
        }

        .option-thumbnail {
            width: 120px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
        }

        .trailer-preview {
            flex: 1;
            height: 80px;
            border-radius: 8px;
            background: #f0f4f8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            overflow: hidden;
        }

        .trailer-preview iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .add-option-btn {
            width: 100%;
            padding: 1rem;
            border: 2px dashed var(--gray);
            border-radius: var(--radius);
            background: transparent;
            color: var(--primary);
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .add-option-btn:hover {
            border-color: var(--primary);
            background: rgba(67, 97, 238, 0.05);
        }

        .preview-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-top: 2rem;
        }

        .preview-poll {
            max-width: 600px;
            margin: 0 auto;
        }

        .preview-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            background: var(--light);
            transition: all 0.2s;
        }

        .preview-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .preview-option-img {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
        }

        .preview-option-details {
            flex: 1;
        }

        .preview-option-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .preview-option-trailer {
            font-size: 0.8rem;
            color: var(--primary);
        }

        .btn-trailer {
            background: var(--primary);
            color: white;
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }

        .btn-trailer:hover {
            background: var(--primary-dark);
            color: white;
        }

        .dark-mode {
            background-color: var(--dark);
            color: #e2e8f0;
        }

        .dark-mode .poll-creator,
        .dark-mode .form-section,
        .dark-mode .option-card,
        .dark-mode .preview-container {
            background-color: #1e293b;
            color: #e2e8f0;
        }

        .dark-mode .form-control,
        .dark-mode .form-select {
            background-color: #334155;
            border-color: #475569;
            color: #e2e8f0;
        }

        .dark-mode .form-control:focus,
        .dark-mode .form-select:focus {
            background-color: #334155;
            color: #e2e8f0;
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
    </style>
</head>
<body>
    <div class="poll-creator">
        <div class="creator-header">
            <h2><i class="fas fa-film me-2"></i> Movie Poll Creator</h2>
            <p class="mb-0">Create engaging movie polls with trailers and images</p>
        </div>

        <div class="creator-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form id="pollForm" method="POST" enctype="multipart/form-data">
                <!-- Poll Details Section -->
                <div class="form-section">
                    <h5><i class="fas fa-info-circle"></i> Poll Details</h5>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Poll Question</label>
                            <input type="text" class="form-control" name="question" required 
                                   placeholder="Which movie should we watch next?">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="categories" required>
                                <option value="">Select category</option>
                                <option value="Action">Action</option>
                                <option value="Comedy">Comedy</option>
                                <option value="Drama">Drama</option>
                                <option value="Sci-Fi">Sci-Fi</option>
                                <option value="Horror">Horror</option>
                                <option value="Documentary">Documentary</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="datetime-local" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="datetime-local" class="form-control" name="expiry_date" required>
                        </div>
                    </div>
                </div>

                <!-- Movie Options Section -->
                <div class="form-section">
                    <h5><i class="fas fa-list-ul"></i> Movie Options</h5>
                    <div id="optionsContainer">
                        <!-- Option 1 -->
                        <div class="option-card" data-index="0">
                            <div class="option-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeOption(this)" disabled>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Movie Title</label>
                                <input type="text" class="form-control" name="options[0][text]" required 
                                       placeholder="Enter movie title">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Trailer URL (YouTube)</label>
                                    <input type="url" class="form-control" name="options[0][trailer]" 
                                           placeholder="https://youtube.com/watch?v=...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Movie Poster</label>
                                    <input type="file" class="form-control" name="options[0][image]" accept="image/*">
                                </div>
                            </div>
                            <div class="option-preview">
                                <div class="option-thumbnail">
                                    <i class="fas fa-image"></i>
                                </div>
                                <div class="trailer-preview">
                                    <i class="fas fa-film"></i> No trailer added
                                </div>
                            </div>
                        </div>

                        <!-- Option 2 -->
                        <div class="option-card" data-index="1">
                            <div class="option-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeOption(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Movie Title</label>
                                <input type="text" class="form-control" name="options[1][text]" required 
                                       placeholder="Enter movie title">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Trailer URL (YouTube)</label>
                                    <input type="url" class="form-control" name="options[1][trailer]" 
                                           placeholder="https://youtube.com/watch?v=...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Movie Poster</label>
                                    <input type="file" class="form-control" name="options[1][image]" accept="image/*">
                                </div>
                            </div>
                            <div class="option-preview">
                                <div class="option-thumbnail">
                                    <i class="fas fa-image"></i>
                                </div>
                                <div class="trailer-preview">
                                    <i class="fas fa-film"></i> No trailer added
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="add-option-btn" onclick="addOption()">
                        <i class="fas fa-plus-circle"></i> Add Another Movie Option
                    </button>
                </div>

                <!-- Poll Settings -->
                <div class="form-section">
                    <h5><i class="fas fa-cog"></i> Poll Settings</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="allowMultiple" name="allow_multiple">
                                <label class="form-check-label" for="allowMultiple">Allow multiple selections</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max votes per user</label>
                            <input type="number" class="form-control" name="max_votes" min="1" value="1">
                        </div>
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="preview-container">
                    <h5 class="text-center mb-4"><i class="fas fa-eye me-2"></i>Live Preview</h5>
                    <div id="pollPreview" class="preview-poll"></div>
                </div>

                <!-- Submit Button -->
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg py-3">
                        <i class="fas fa-plus-circle me-2"></i> Create Movie Poll
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Dark Mode Toggle -->
    <button class="btn btn-dark position-fixed bottom-0 end-0 m-3 rounded-circle" style="width: 50px; height: 50px;" 
            onclick="toggleDarkMode()">
        <i class="fas fa-moon"></i>
    </button>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers
        flatpickr("input[type=datetime-local]", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today"
        });

        // Add new movie option
        function addOption() {
            const container = $('#optionsContainer');
            const optionCount = container.children().length;
            const newIndex = optionCount;
            
            const newOption = $(`
                <div class="option-card" data-index="${newIndex}">
                    <div class="option-actions">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeOption(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Movie Title</label>
                        <input type="text" class="form-control" name="options[${newIndex}][text]" required 
                               placeholder="Enter movie title">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trailer URL (YouTube)</label>
                            <input type="url" class="form-control trailer-input" name="options[${newIndex}][trailer]" 
                                   placeholder="https://youtube.com/watch?v=...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Movie Poster</label>
                            <input type="file" class="form-control image-input" name="options[${newIndex}][image]" accept="image/*">
                        </div>
                    </div>
                    <div class="option-preview">
                        <div class="option-thumbnail">
                            <i class="fas fa-image"></i>
                        </div>
                        <div class="trailer-preview">
                            <i class="fas fa-film"></i> No trailer added
                        </div>
                    </div>
                </div>
            `);
            
            container.append(newOption);
            
            // Add event listeners for the new inputs
            newOption.find('.trailer-input').on('input', updatePreview);
            newOption.find('.image-input').on('change', function() {
                const parentCard = $(this).closest('.option-card');
                const index = parentCard.data('index');
                const file = this.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        parentCard.find('.option-thumbnail').html(
                            `<img src="${e.target.result}" class="img-fluid" style="width:100%;height:100%;object-fit:cover;">`
                        );
                        updatePreview();
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            updatePreview();
        }

        // Remove option
        function removeOption(button) {
            if ($('#optionsContainer').children().length > 2) {
                $(button).closest('.option-card').remove();
                
                // Reindex remaining options
                $('#optionsContainer .option-card').each(function(index) {
                    $(this).attr('data-index', index);
                    $(this).find('[name^="options"]').each(function() {
                        const name = $(this).attr('name').replace(/\[\d+\]/, `[${index}]`);
                        $(this).attr('name', name);
                    });
                });
                
                updatePreview();
            }
        }

        // Update live preview
        function updatePreview() {
            const options = [];
            
            $('.option-card').each(function() {
                const title = $(this).find('input[name$="[text]"]').val() || 'Movie Title';
                const trailerUrl = $(this).find('input[name$="[trailer]"]').val();
                const imageSrc = $(this).find('.option-thumbnail img').attr('src') || 'https://via.placeholder.com/60?text=Poster';
                
                options.push({ title, trailerUrl, imageSrc });
            });
            
            let previewHtml = `
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">${$('input[name="question"]').val() || 'Which movie should we watch?'}</h5>
                        <p class="card-text text-muted mb-4">Select your favorite movie option below</p>
                        
                        <div class="options-list">
            `;
            
            options.forEach((option, index) => {
                const youtubeId = option.trailerUrl ? extractYouTubeId(option.trailerUrl) : null;
                
                previewHtml += `
                    <div class="preview-option">
                        <img src="${option.imageSrc}" class="preview-option-img">
                        <div class="preview-option-details">
                            <div class="preview-option-title">${option.title}</div>
                            ${youtubeId ? `
                                <a href="${option.trailerUrl}" target="_blank" class="preview-option-trailer">
                                    <span class="btn-trailer btn-sm">
                                        <i class="fas fa-play me-1"></i> Watch Trailer
                                    </span>
                                </a>
                            ` : '<div class="text-muted small">No trailer</div>'}
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="option${index}">
                        </div>
                    </div>
                `;
            });
            
            previewHtml += `
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button class="btn btn-primary">Submit Vote</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('#pollPreview').html(previewHtml);
        }

        // Extract YouTube ID from URL
        function extractYouTubeId(url) {
            if (!url) return null;
            
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            
            return (match && match[2].length === 11) ? match[2] : null;
        }

        // Handle image upload preview
        $(document).on('change', 'input[type="file"]', function() {
            const parentCard = $(this).closest('.option-card');
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    parentCard.find('.option-thumbnail').html(
                        `<img src="${e.target.result}" class="img-fluid" style="width:100%;height:100%;object-fit:cover;">`
                    );
                    updatePreview();
                }
                reader.readAsDataURL(file);
            }
        });

        // Handle trailer URL input
        $(document).on('input', 'input[name$="[trailer]"]', function() {
            const parentCard = $(this).closest('.option-card');
            const url = $(this).val();
            const youtubeId = extractYouTubeId(url);
            
            if (youtubeId) {
                parentCard.find('.trailer-preview').html(
                    `<iframe src="https://www.youtube.com/embed/${youtubeId}?autoplay=0&controls=0&showinfo=0" 
                      frameborder="0" allowfullscreen></iframe>`
                );
            } else {
                parentCard.find('.trailer-preview').html(
                    url ? '<i class="fas fa-exclamation-triangle text-danger"></i> Invalid URL' : 
                         '<i class="fas fa-film"></i> No trailer added'
                );
            }
            
            updatePreview();
        });

        // Toggle dark mode
        function toggleDarkMode() {
            $('body').toggleClass('dark-mode');
            $('.btn-dark i').toggleClass('fa-moon fa-sun');
        }

        // Initialize preview and event listeners
        $(document).ready(function() {
            updatePreview();
            
            // Add event listeners to initial inputs
            $('input[name="question"]').on('input', updatePreview);
            $('input[name$="[text]"]').on('input', updatePreview);
            $('input[name$="[trailer]"]').on('input', updatePreview);
        });
    </script>
</body>
</html>