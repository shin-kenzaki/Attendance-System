<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
// Check if user has faculty access
if ($_SESSION['usertype'] !== 'faculty') {
    header("Location: 404.php");
    exit();
}

// Include database connection
include 'db.php';

// Get subject_id parameter
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Validate subject_id is not zero
if ($subject_id === 0) {
    $_SESSION['error'] = "No subject selected. Please select a subject first.";
    header("Location: faculty_subjects.php");
    exit();
}

// Get subject information
$query = "SELECT * FROM subjects WHERE id = ? AND faculty_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $subject_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: faculty_subjects.php");
    exit();
}

$subject = $result->fetch_assoc();

// Include common header
include 'includes/header.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Take Attendance</h1>
            <p class="mb-0"><?php echo htmlspecialchars($subject['name']); ?> (<?php echo htmlspecialchars($subject['code']); ?>)</p>
        </div>
        <div>
            <a href="faculty_subjects.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Subjects
            </a>
        </div>
    </div>

    <!-- METHOD SELECTION SCREEN -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Choose Attendance Method</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 col-sm-12 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center py-4 py-md-5">
                                    <i class="fas fa-qrcode fa-5x mb-3 text-primary"></i>
                                    <h5 class="card-title">Display QR Code</h5>
                                    <p class="card-text">Generate QR code for students to scan with their devices</p>
                                    <a href="faculty_qr_generator.php?subject_id=<?php echo $subject_id; ?>" 
                                       class="btn btn-primary btn-lg mt-3">Select</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center py-4 py-md-5">
                                    <i class="fas fa-camera fa-5x mb-3 text-secondary"></i>
                                    <h5 class="card-title">Scan QR Codes</h5>
                                    <p class="card-text">Use your device to scan student QR codes</p>
                                    <a href="faculty_qr_scanner.php?subject_id=<?php echo $subject_id; ?>" 
                                       class="btn btn-secondary btn-lg mt-3">Select</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>
<!-- /.container-fluid -->

<?php 
include 'includes/footer.php'; 
$conn->close();
?>