<?php
include 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $available_to = $_POST['available_to'];
    $uploaded_by = $_SESSION['admin_id'];

    // File upload handling
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['resource_file'];
        
        // File validation
        $maxSize = 50 * 1024 * 1024; // 50MB in bytes
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'txt'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file['size'] > $maxSize) {
            $error = 'File size exceeds 50MB limit';
        } elseif (!in_array($fileExt, $allowedExtensions)) {
            $error = 'Invalid file extension. Allowed types: ' . implode(', ', $allowedExtensions);
        } else {
            // Create upload directory if it doesn't exist
            $uploadDir = 'uploads/resources/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $fileName = uniqid() . '_' . basename($file['name']);
            $filePath = $uploadDir . $fileName;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO resources 
                    (title, file_name, file_path, file_size, file_type, description, available_to, uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssisssi", 
                    $title, 
                    $file['name'], 
                    $filePath, 
                    $file['size'], 
                    $fileExt, // Using file extension instead of MIME type
                    $description, 
                    $available_to, 
                    $uploaded_by);
                
                if ($stmt->execute()) {
                    $success = 'Resource uploaded successfully!';
                } else {
                    $error = 'Database error: ' . $conn->error;
                    // Remove uploaded file if DB insert failed
                    unlink($filePath);
                }
            } else {
                $error = 'Error moving uploaded file';
            }
        }
    } else {
        $error = 'Please select a valid file to upload';
    }
}

// Fetch all resources for display
$resources_query = "SELECT * FROM resources ORDER BY upload_date DESC";
$resources_result = mysqli_query($conn, $resources_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Resource</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, rgb(43, 77, 74) 0%, #16213e 100%);
            color: white;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 100;
        }

        .sidebar h2 {
            text-align: center;
            color: white;
            margin-bottom: 20px;
            font-size: 22px;
            border-bottom: 2px solid #4cc9f0;
            padding-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 15px 0;
            position: relative;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: white;
            display: block;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar ul li a:hover {
            background: rgba(76, 201, 240, 0.2);
            transform: translateX(5px);
        }

        .sidebar ul li a.active {
            background: linear-gradient(90deg, rgba(76, 201, 240, 0.3) 0%, transparent 100%);
            border-left: 3px solid #4cc9f0;
        }

        /* Main Content */
        .main-content {
            margin-left: 270px;
            padding: 40px;
            width: calc(100% - 270px);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Content Box */
        .content-box {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .content-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        h1 {
            font-size: 24px;
            margin-bottom: 25px;
            text-align: center;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
            width: 100%;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #b0c4de;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(76, 201, 240, 0.3);
            border-radius: 8px;
            font-size: 15px;
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #4cc9f0;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 201, 240, 0.2);
        }

        /* Drop Zone */
        .drop-zone {
            border: 2px dashed rgba(76, 201, 240, 0.5);
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.03);
            color: #bbb;
            transition: all 0.3s;
        }

        .drop-zone.highlight {
            border-color: #4cc9f0;
            background-color: rgba(76, 201, 240, 0.15);
            color: #fff;
        }

        .drop-zone button {
            background: rgba(76, 201, 240, 0.2);
            color: white;
            border: 1px solid rgba(76, 201, 240, 0.5);
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: 500;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .drop-zone button:hover {
            background: rgba(76, 201, 240, 0.3);
        }

        /* Upload Button */
        .upload-btn {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
            font-family: 'Poppins', sans-serif;
        }

        .upload-btn:hover {
            background: linear-gradient(135deg, #4895ef, #4cc9f0);
            box-shadow: 0 5px 20px rgba(76, 201, 240, 0.5);
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .success {
            background: rgba(40, 167, 69, 0.2);
            color: #fff;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 3px solid #28a745;
        }

        .error {
            background: rgba(220, 53, 69, 0.2);
            color: #fff;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 3px solid #dc3545;
        }

        /* Resource Grid */
        .resource-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .resource-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(76, 201, 240, 0.3);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }

        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border-color: #4cc9f0;
        }

        .file-icon {
            font-size: 40px;
            text-align: center;
            margin-bottom: 15px;
            color: #4cc9f0;
        }

        .resource-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .download-btn, .delete-btn {
            padding: 8px 12px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .download-btn {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.5);
        }

        .download-btn:hover {
            background: rgba(40, 167, 69, 0.3);
        }

        .delete-btn {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
        }

        .delete-btn:hover {
            background: rgba(220, 53, 69, 0.3);
        }

        /* Form Layout */
        .form-flex {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .form-flex .form-group {
            flex: 1;
            min-width: 200px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #aaa;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: rgba(76, 201, 240, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                padding: 15px 10px;
            }
            
            .sidebar h2 {
                font-size: 0;
                padding-bottom: 0;
                border: none;
            }
            
            .sidebar h2:after {
                content: "AR";
                font-size: 18px;
                display: block;
            }
            
            .sidebar ul li a span {
                display: none;
            }
            
            .sidebar ul li a i {
                margin-right: 0;
                font-size: 18px;
            }
            
            .main-content {
                margin-left: 90px;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .resource-grid {
                grid-template-columns: 1fr;
            }
            
            .form-flex {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<!-- Updated Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <ul>
        <li><a href="admin_dashboard.php" ><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li><a href="admin_student_list.php"><i class="fas fa-users"></i> <span>Students</span></a></li>
        <li><a href="admin_announcement.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
        <li><a href="manage_sitins.php"><i class="fas fa-user-clock"></i> <span>Manage Sit-ins</span></a></li>
        <li><a href="sit_in_records.php"><i class="fas fa-history"></i> <span>Sit-in Records</span></a></li>
        <li><a href="leaderboard.php"><i class="fas fa-trophy"></i> <span>Leaderboard</span></a></li>
        <li><a href="feedback.php"><i class="fas fa-comment-alt"></i> <span>Feedback</span></a></li>
        <li><a href="lab_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Lab Schedule</span></a></li>
        <li><a href="admin_upload_resource.php" class="active"><i class="fas fa-upload"></i> <span>Resources</span></a></li>
        <li><a href="admin_reservations.php"><i class="fas fa-calendar-check"></i> <span>Reservations</span></a></li>
        <li><a href="admin_lab_management.php"><i class="fas fa-laptop-house"></i> <span>Lab Management</span></a></li>
        <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="content-box">
        <h1><i class="fas fa-folder-open"></i> Resource Management</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form action="admin_upload_resource.php" method="post" enctype="multipart/form-data">
            <div class="form-flex">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="available_to">Available To *</label>
                    <select id="available_to" name="available_to" required>
                        <option value="all">All Users</option>
                        <option value="students">Students Only</option>
                        <option value="admins">Admins Only</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Write a short description of the resource..."></textarea>
            </div>

            <div class="form-group">
                <label for="resource_file">Upload File *</label>
                <div class="drop-zone" id="dropZone">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 40px; margin-bottom: 10px; color: #4cc9f0;"></i>
                    <p>Drag & drop files here or click to browse</p>
                    <input type="file" id="resource_file" name="resource_file" required style="display: none;">
                    <button type="button" onclick="document.getElementById('resource_file').click()">
                        <i class="fas fa-upload"></i> Select File
                    </button>
                    <p>Max file size: 50MB</p>
                    <p id="fileName" style="margin-top: 10px; display: none; font-weight: 500; color: #4cc9f0;"></p>
                </div>
            </div>

            <button type="submit" class="upload-btn">
                <i class="fas fa-upload"></i> Upload Resource
            </button>
        </form>

        <!-- Resource Display Section -->
        <div class="resource-grid">
            <?php if(mysqli_num_rows($resources_result) > 0): ?>
                <?php while($resource = mysqli_fetch_assoc($resources_result)): ?>
                    <?php
                    // Get appropriate icon based on file type
                    $file_ext = pathinfo($resource['file_name'], PATHINFO_EXTENSION);
                    $icon = 'fa-file';
                    
                    if (in_array($file_ext, ['pdf'])) {
                        $icon = 'fa-file-pdf';
                    } elseif (in_array($file_ext, ['doc', 'docx'])) {
                        $icon = 'fa-file-word';
                    } elseif (in_array($file_ext, ['xls', 'xlsx'])) {
                        $icon = 'fa-file-excel';
                    } elseif (in_array($file_ext, ['ppt', 'pptx'])) {
                        $icon = 'fa-file-powerpoint';
                    } elseif (in_array($file_ext, ['zip', 'rar'])) {
                        $icon = 'fa-file-archive';
                    } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $icon = 'fa-file-image';
                    } elseif (in_array($file_ext, ['txt'])) {
                        $icon = 'fa-file-alt';
                    }
                    
                    // Format file size
                    $file_size = $resource['file_size'];
                    if ($file_size >= 1073741824) {
                        $file_size = number_format($file_size / 1073741824, 2) . ' GB';
                    } elseif ($file_size >= 1048576) {
                        $file_size = number_format($file_size / 1048576, 2) . ' MB';
                    } elseif ($file_size >= 1024) {
                        $file_size = number_format($file_size / 1024, 2) . ' KB';
                    } else {
                        $file_size = $file_size . ' bytes';
                    }
                    ?>
                    <div class="resource-card">
                        <div class="file-icon">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($resource['title']); ?></h3>
                        <p style="font-size: 12px; color: #aaa; margin-bottom: 10px;"><?php echo $file_size; ?></p>
                        <p style="font-size: 13px; color: #ddd; margin-bottom: 15px;"><?php echo htmlspecialchars(substr($resource['description'], 0, 50)); ?>...</p>
                        <div class="resource-actions">
                            <a href="download_resource.php?id=<?php echo $resource['id']; ?>" class="download-btn">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <a href="delete_resource.php?id=<?php echo $resource['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this resource?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No resources uploaded yet</h3>
                    <p>Upload your first resource using the form above</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Drag and drop functionality
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('resource_file');
    const fileNameElement = document.getElementById('fileName');
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('highlight');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('highlight');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('highlight');
        
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            fileNameElement.textContent = e.dataTransfer.files[0].name;
            fileNameElement.style.display = 'block';
        }
    });
    
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            fileNameElement.textContent = fileInput.files[0].name;
            fileNameElement.style.display = 'block';
        } else {
            fileNameElement.style.display = 'none';
        }
    });
</script>
</body>
</html>