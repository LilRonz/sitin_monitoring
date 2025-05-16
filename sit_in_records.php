<?php
include 'config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    echo "<p style='color: red;'>Error: Unauthorized access. Only administrators can view this page.</p>";
    header("Refresh: 2; url=index.php");
    exit();
}

// Initialize filter variables
$lab_filter = isset($_GET['lab_filter']) ? $_GET['lab_filter'] : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$purpose_filter = isset($_GET['purpose_filter']) ? $_GET['purpose_filter'] : '';

// Build the base query
$query = "
    SELECT h.id, h.student_idno, CONCAT(u.firstname, ' ', u.lastname) AS fullname, 
           h.lab_classroom, h.purpose, h.time_in, h.sitin_time
    FROM sitin_history h
    JOIN users u ON h.student_idno = u.idno";

// Add filters if they exist
$where_clauses = [];
if (!empty($lab_filter)) {
    $where_clauses[] = "h.lab_classroom = '" . $conn->real_escape_string($lab_filter) . "'";
}
if (!empty($date_filter)) {
    $where_clauses[] = "DATE(h.time_in) = '" . $conn->real_escape_string($date_filter) . "'";
}
if (!empty($purpose_filter)) {
    $where_clauses[] = "h.purpose = '" . $conn->real_escape_string($purpose_filter) . "'";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY h.sitin_time DESC";
$result = $conn->query($query);

// Handle export actions
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Reuse the same query with filters for export
    $export_result = $conn->query($query);
    $records = array();
    while ($row = $export_result->fetch_assoc()) {
        $records[] = $row;
    }
    
    switch ($export_type) {
        case 'csv':
            exportToCSV($records);
            break;
        case 'excel':
            exportToExcel($records);
            break;
        case 'pdf':
            exportToPDF($records);
            break;
        case 'print':
            generatePrintView($records);
            break;
        case 'docx':
            exportDOCX($records);
            break;
        default:
            header("Location: sit_in_records.php");
            exit();
    }
}

// Handle reset action
if (isset($_POST['reset_records'])) {
    $reset_query = "TRUNCATE TABLE sitin_history";
    if ($conn->query($reset_query)) {
        $success_msg = "All sit-in records have been reset successfully!";
    } else {
        $error_msg = "Error resetting records: " . $conn->error;
    }
    header("Refresh:0");
    exit();
}

// Export functions
function exportToCSV($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sit_in_records_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");
    
    // Add title headers
    fputcsv($output, ['University of Cebu-Main']);
    fputcsv($output, ['College of Computer Studies']);
    fputcsv($output, ['Computer Laboratory Sitin Monitoring System Report']);
    fputcsv($output, []); // Empty line
    
    // Add column headers
    fputcsv($output, ['Student Name', 'Purpose', 'Lab', 'Start Time', 'End Time']);
    
    // Add data rows
    foreach ($data as $row) {
        fputcsv($output, [
            $row['fullname'],
            $row['purpose'],
            'Lab ' . $row['lab_classroom'],
            date("F d, Y h:i A", strtotime($row['time_in'])),
            date("F d, Y h:i A", strtotime($row['sitin_time']))
        ]);
    }
    
    fclose($output);
    exit;
}

function exportToExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="sit_in_records_' . date('Y-m-d') . '.xls"');
    
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">
    <head>
        <meta charset="UTF-8">
        <style>
            .title { font-weight: bold; text-align: center; font-size: 16px; }
            .subtitle { text-align: center; }
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #f2f2f2; font-weight: bold; text-align: left; }
            th, td { border: 1px solid #dddddd; padding: 8px; }
        </style>
    </head>
    <body>
        <div class="title">University of Cebu-Main</div>
        <div class="title">College of Computer Studies</div>
        <div class="subtitle">Computer Laboratory Sitin Monitoring System Report</div>
        <br>
        
        <table>
            <tr>
                <th>Student Name</th>
                <th>Purpose</th>
                <th>Lab</th>
                <th>Start Time</th>
                <th>End Time</th>
            </tr>';
    
    foreach ($data as $row) {
        echo '<tr>
            <td>' . htmlspecialchars($row['fullname']) . '</td>
            <td>' . htmlspecialchars($row['purpose']) . '</td>
            <td>Lab ' . htmlspecialchars($row['lab_classroom']) . '</td>
            <td>' . date("F d, Y h:i A", strtotime($row['time_in'])) . '</td>
            <td>' . date("F d, Y h:i A", strtotime($row['sitin_time'])) . '</td>
        </tr>';
    }
    
    echo '</table>
    </body>
    </html>';
    exit;
}

function exportToPDF($data) {
    // Prepare data for JavaScript
    $jsData = [
        'title' => 'Sit-in Records',
        'headers' => ['University of Cebu-Main', 'College of Computer Studies', 
                     'Computer Laboratory Sitin Monitoring System Report'],
        'columns' => ['Student Name', 'Purpose', 'Lab', 'Start Time', 'End Time'],
        'rows' => []
    ];
    
    foreach ($data as $row) {
        $jsData['rows'][] = [
            htmlspecialchars($row['fullname']),
            htmlspecialchars($row['purpose']),
            'Lab ' . htmlspecialchars($row['lab_classroom']),
            date("F d, Y h:i A", strtotime($row['time_in'])),
            date("F d, Y h:i A", strtotime($row['sitin_time']))
        ];
    }
    
    // Return JSON data for client-side processing
    header('Content-Type: application/json');
    echo json_encode($jsData);
    exit;
}

function generatePrintView($data) {
    header('Content-Type: text/html');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Sit-in Records - Print View</title>
        <style>
            body { font-family: Arial; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; }
            .title { font-weight: bold; font-size: 18px; }
            .subtitle { font-size: 14px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            @media print {
                @page { size: A4 landscape; margin: 10mm; }
                body { margin: 0; padding: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="title">University of Cebu-Main</div>
            <div class="title">College of Computer Studies</div>
            <div class="subtitle">Computer Laboratory Sitin Monitoring System Report</div>
        </div>
        
        <button class="no-print" onclick="window.print()" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Print Report
        </button>
        <button class="no-print" onclick="window.close()" style="padding: 8px 16px; background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Close Window
        </button>
        
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data as $row) {
        echo '<tr>
            <td>' . htmlspecialchars($row['fullname']) . '</td>
            <td>' . htmlspecialchars($row['purpose']) . '</td>
            <td>Lab ' . htmlspecialchars($row['lab_classroom']) . '</td>
            <td>' . date("F d, Y h:i A", strtotime($row['time_in'])) . '</td>
            <td>' . date("F d, Y h:i A", strtotime($row['sitin_time'])) . '</td>
        </tr>';
    }
    
    echo '</tbody>
        </table>
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>';
    exit;
}

function exportDOCX($data) {
    require_once('PhpOffice/PhpWord/Autoloader.php');
    \PhpOffice\PhpWord\Autoloader::register();
    
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();
    $section->addTitle('Sit-in Records', 1);
    $section->addText('Generated on ' . date('F j, Y'));
    
    $table = $section->addTable();
    $table->addRow();
    $table->addCell(2000)->addText('Student Name', array('bold' => true));
    $table->addCell(2000)->addText('Purpose', array('bold' => true));
    $table->addCell(1000)->addText('Lab', array('bold' => true));
    $table->addCell(2000)->addText('Start Time', array('bold' => true));
    $table->addCell(2000)->addText('End Time', array('bold' => true));
    
    foreach ($data as $row) {
        $table->addRow();
        $table->addCell()->addText($row['fullname']);
        $table->addCell()->addText($row['purpose']);
        $table->addCell()->addText('Lab ' . $row['lab_classroom']);
        $table->addCell()->addText(date("F d, Y - h:i A", strtotime($row['time_in'])));
        $table->addCell()->addText(date("F d, Y - h:i A", strtotime($row['sitin_time'])));
    }
    
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="sit_in_records_' . date('Y-m-d') . '.docx"');
    $objWriter->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Records</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Global Styling */
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

        /* Sidebar Styling */
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
        }

        /* Page Header */
        .page-header {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .page-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #4cc9f0, #4895ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .page-header p {
            color: #aaa;
            font-size: 14px;
            width: 100%;
        }

        /* Reset Button */
        .reset-btn {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.5);
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .reset-btn:hover {
            background: rgba(220, 53, 69, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.2);
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 3px solid;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border-left-color: #28a745;
            color: #28a745;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border-left-color: #dc3545;
            color: #dc3545;
        }

        /* Filter Controls */
        .filter-controls {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #aaa;
        }

        .filter-group select, 
        .filter-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 14px;
            transition: all 0.3s;
        }

        .filter-group select:focus, 
        .filter-group input:focus {
            outline: none;
            border-color: #4cc9f0;
            box-shadow: 0 0 0 2px rgba(76, 201, 240, 0.2);
        }

        /* Export Buttons */
        .export-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .export-btn {
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .export-btn.excel {
            background: linear-gradient(90deg, rgba(40, 167, 69, 0.8), rgba(33, 136, 56, 0.8));
        }

        .export-btn.pdf {
            background: linear-gradient(90deg, rgba(220, 53, 69, 0.8), rgba(200, 35, 51, 0.8));
        }

        .export-btn.docx {
            background: linear-gradient(90deg, rgba(23, 162, 184, 0.8), rgba(19, 132, 150, 0.8));
        }

        .export-btn.print {
            background: linear-gradient(90deg, rgba(108, 117, 125, 0.8), rgba(90, 98, 104, 0.8));
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            background: rgba(0, 0, 0, 0.3);
            color: #4cc9f0;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        /* No Records Message */
        .no-records {
            text-align: center;
            padding: 20px;
            color: #aaa;
            font-size: 16px;
        }

        /* Hidden form for filter submission */
        #filter-form {
            display: none;
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
                content: "SM";
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
            table {
                display: block;
                overflow-x: auto;
            }
            
            .filter-controls {
                flex-direction: column;
                gap: 15px;
            }
            
            .export-buttons {
                flex-direction: column;
            }
            
            .export-btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- Updated Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <ul>
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        <li><a href="admin_student_list.php"><i class="fas fa-users"></i> <span>Students</span></a></li>
        <li><a href="admin_announcement.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a></li>
        <li><a href="manage_sitins.php"><i class="fas fa-user-clock"></i> <span>Manage Sit-ins</span></a></li>
        <li><a href="sit_in_records.php"  class="active"><i class="fas fa-history"></i> <span>Sit-in Records</span></a></li>
        <li><a href="leaderboard.php"><i class="fas fa-trophy"></i> <span>Leaderboard</span></a></li>
        <li><a href="feedback.php"><i class="fas fa-comment-alt"></i> <span>Feedback</span></a></li>
        <li><a href="lab_schedule.php"><i class="fas fa-calendar-alt"></i> <span>Lab Schedule</span></a></li>
        <li><a href="admin_upload_resource.php"><i class="fas fa-upload"></i> <span>Resources</span></a></li>
        <li><a href="admin_reservations.php"><i class="fas fa-calendar-check"></i> <span>Reservations</span></a></li>
        <li><a href="admin_lab_management.php"><i class="fas fa-laptop-house"></i> <span>Lab Management</span></a></li>
        <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="page-header">
        <div>
            <h1>All Sit-in Records</h1>
            <p>View all students' sit-in sessions.</p>
        </div>
        <div>
            <form method="post" onsubmit="return confirm('Are you sure you want to reset ALL sit-in records? This action cannot be undone.');">
                <button type="submit" name="reset_records" class="reset-btn">
                    <i class="fas fa-trash-alt"></i> Reset All Records
                </button>
            </form>
        </div>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php elseif (isset($error_msg)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <!-- Filter Controls -->
    <div class="filter-controls">
        <div class="filter-group">
            <label for="date_filter">Date</label>
            <input 
                type="date" 
                name="date_filter" 
                id="date_filter"
                value="<?= htmlspecialchars($date_filter) ?>"
                onchange="this.form.submit()"
                form="filter-form"
            >
        </div>
        
        <div class="filter-group">
            <label for="lab_filter">Lab Room</label>
            <select 
                name="lab_filter" 
                id="lab_filter"
                onchange="this.form.submit()"
                form="filter-form"
            >
                <option value="">All Labs</option>
                <option value="517" <?= $lab_filter == '517' ? 'selected' : '' ?>>Lab 517</option>
                <option value="524" <?= $lab_filter == '524' ? 'selected' : '' ?>>Lab 524</option>
                <option value="526" <?= $lab_filter == '526' ? 'selected' : '' ?>>Lab 526</option>
                <option value="528" <?= $lab_filter == '528' ? 'selected' : '' ?>>Lab 528</option>
                <option value="530" <?= $lab_filter == '530' ? 'selected' : '' ?>>Lab 530</option>
                <option value="542" <?= $lab_filter == '542' ? 'selected' : '' ?>>Lab 542</option>
                <option value="544" <?= $lab_filter == '544' ? 'selected' : '' ?>>Lab 544</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="purpose_filter">Purpose</label>
            <select 
                name="purpose_filter" 
                id="purpose_filter"
                onchange="this.form.submit()"
                form="filter-form"
            >
                <option value="">All Purposes</option>
                <option value="C Programming" <?= $purpose_filter == 'C Programming' ? 'selected' : '' ?>>C Programming</option>
                <option value="Java Programming" <?= $purpose_filter == 'Java Programming' ? 'selected' : '' ?>>Java Programming</option>
                <option value="C# Programming" <?= $purpose_filter == 'C# Programming' ? 'selected' : '' ?>>C# Programming</option>
                <option value="Systems Integration & Architecture" <?= $purpose_filter == 'Systems Integration & Architecture' ? 'selected' : '' ?>>Systems Integration & Architecture</option>
                <option value="Embedded Systems & IoT" <?= $purpose_filter == 'Embedded Systems & IoT' ? 'selected' : '' ?>>Embedded Systems & IoT</option>
                <option value="Computer Application" <?= $purpose_filter == 'Computer Application' ? 'selected' : '' ?>>Computer Application</option>
                <option value="Database" <?= $purpose_filter == 'Database' ? 'selected' : '' ?>>Database</option>
                <option value="Project Management" <?= $purpose_filter == 'Project Management' ? 'selected' : '' ?>>Project Management</option>
                <option value="Python Programming" <?= $purpose_filter == 'Python Programming' ? 'selected' : '' ?>>Python Programming</option>
                <option value="Mobile Application" <?= $purpose_filter == 'Mobile Application' ? 'selected' : '' ?>>Mobile Application</option>
                <option value="Web Design" <?= $purpose_filter == 'Web Design' ? 'selected' : '' ?>>Web Design</option>
                <option value="Php Programming" <?= $purpose_filter == 'Php Programming' ? 'selected' : '' ?>>Php Programming</option>
                <option value="Other" <?= $purpose_filter == 'Other' ? 'selected' : '' ?>>Others...</option>
            </select>
        </div>
    </div>
    
    <!-- Hidden form for filter submission -->
    <form id="filter-form" method="get" class="hidden"></form>

    <!-- Export Buttons -->
    <div class="export-buttons">
        <a href="?export=csv<?= !empty($lab_filter) ? '&lab_filter=' . urlencode($lab_filter) : '' ?><?= !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : '' ?><?= !empty($purpose_filter) ? '&purpose_filter=' . urlencode($purpose_filter) : '' ?>" class="export-btn excel">
            <i class="fas fa-file-excel"></i> Export to Excel (CSV)
        </a>
        <a href="#" onclick="exportToPDF(event)" class="export-btn pdf">
            <i class="fas fa-file-pdf"></i> Export to PDF
        </a>
        <a href="?export=docx<?= !empty($lab_filter) ? '&lab_filter=' . urlencode($lab_filter) : '' ?><?= !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : '' ?><?= !empty($purpose_filter) ? '&purpose_filter=' . urlencode($purpose_filter) : '' ?>" class="export-btn docx">
            <i class="fas fa-file-word"></i> Export to Word (DOCX)
        </a>
        <a href="?export=print<?= !empty($lab_filter) ? '&lab_filter=' . urlencode($lab_filter) : '' ?><?= !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : '' ?><?= !empty($purpose_filter) ? '&purpose_filter=' . urlencode($purpose_filter) : '' ?>" target="_blank" class="export-btn print">
            <i class="fas fa-print"></i> Print View
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Purpose</th>
                <th>Lab</th>
                <th>Start Time</th>
                <th>End Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                        <td><?= htmlspecialchars($row['purpose']) ?></td>
                        <td>Lab <?= htmlspecialchars($row['lab_classroom']) ?></td>
                        <td><?= htmlspecialchars(date("F d, Y - h:i A", strtotime($row['time_in']))) ?></td>
                        <td><?= htmlspecialchars(date("F d, Y - h:i A", strtotime($row['sitin_time']))) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="no-records">
                        <i class="fas fa-info-circle"></i> No sit-in records found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- PDF Generation Script -->
<script>
    // Function to handle PDF export
    function exportToPDF(event) {
        event.preventDefault();
        
        // Get current filters
        const labFilter = document.querySelector('[name="lab_filter"]').value;
        const dateFilter = document.querySelector('[name="date_filter"]').value;
        const purposeFilter = document.querySelector('[name="purpose_filter"]').value;
        
        // Build export URL
        let url = '?export=pdf';
        if (labFilter) url += '&lab_filter=' + encodeURIComponent(labFilter);
        if (dateFilter) url += '&date_filter=' + encodeURIComponent(dateFilter);
        if (purposeFilter) url += '&purpose_filter=' + encodeURIComponent(purposeFilter);
        
        // Check if jsPDF is already loaded
        if (window.jspdf) {
            fetchAndGeneratePDF(url);
        } else {
            // Load jsPDF dynamically
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            script.onload = function() {
                const autoTableScript = document.createElement('script');
                autoTableScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js';
                autoTableScript.onload = function() {
                    fetchAndGeneratePDF(url);
                };
                document.head.appendChild(autoTableScript);
            };
            document.head.appendChild(script);
        }
    }

    function fetchAndGeneratePDF(url) {
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                generatePDF(data);
            })
            .catch(error => {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
            });
    }

    // Function to generate PDF from data
    function generatePDF(data) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({
            orientation: 'landscape'
        });

        // Add headers
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text(data.headers[0], doc.internal.pageSize.width / 2, 15, { align: 'center' });
        doc.text(data.headers[1], doc.internal.pageSize.width / 2, 22, { align: 'center' });
        
        doc.setFontSize(12);
        doc.setFont('helvetica', 'normal');
        doc.text(data.headers[2], doc.internal.pageSize.width / 2, 29, { align: 'center' });

        // AutoTable configuration
        doc.autoTable({
            head: [data.columns],
            body: data.rows,
            startY: 40,
            theme: 'grid',
            headStyles: {
                fillColor: [61, 71, 79], // Dark gray color
                textColor: 255, // White text
                fontStyle: 'bold'
            },
            styles: {
                fontSize: 9,
                cellPadding: 2,
                overflow: 'linebreak'
            },
            margin: { left: 10, right: 10 }
        });

        // Save the PDF
        doc.save('sit_in_records_' + new Date().toISOString().slice(0, 10) + '.pdf');
    }
</script>
</body>
</html>