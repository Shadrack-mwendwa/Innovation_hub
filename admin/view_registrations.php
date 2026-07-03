<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once '../config/database.php';

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Get class info
$stmt = $pdo->prepare("SELECT * FROM computer_classes WHERE id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if(!$class) {
    header("Location: classes.php");
    exit();
}

// Get registrations for this class
$stmt = $pdo->prepare("
    SELECT r.*, c.class_name 
    FROM class_registrations r 
    JOIN computer_classes c ON r.class_id = c.id 
    WHERE r.class_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$class_id]);
$registrations = $stmt->fetchAll();

// Calculate totals
$total_students = count($registrations);
$total_collected = 0;
$total_balance = 0;

foreach($registrations as $reg) {
    $total_collected += $reg['payment_amount'];
    $total_balance += (1000 - $reg['payment_amount']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrations - <?php echo htmlspecialchars($class['class_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin - Makueni Innovation Hub</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><?php echo htmlspecialchars($class['class_name']); ?></h1>
                <p class="text-muted">Schedule: <?php echo htmlspecialchars($class['schedule']); ?> | Starts: <?php echo date('M d, Y', strtotime($class['start_date'])); ?></p>
            </div>
            <a href="classes.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Classes
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card text-center bg-primary text-white">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h3><?php echo $total_students; ?></h3>
                        <p class="mb-0">Total Students</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <i class="fas fa-money-bill fa-2x mb-2"></i>
                        <h3>KES <?php echo number_format($total_collected, 2); ?></h3>
                        <p class="mb-0">Amount Collected</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center bg-warning text-white">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h3>KES <?php echo number_format($total_balance, 2); ?></h3>
                        <p class="mb-0">Outstanding Balance</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registrations Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Student Registrations</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Amount Paid (KES)</th>
                                <th>Balance (KES)</th>
                                <th>Status</th>
                                <th>Letter</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($registrations)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">No registrations yet for this class.</td>
                                </tr>
                            <?php else: ?>
                                <?php $i = 1; foreach($registrations as $reg): 
                                    $balance = 1000 - $reg['payment_amount'];
                                ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($reg['full_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">Reg: <?php echo date('M d', strtotime($reg['created_at'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                        <td class="text-success fw-bold"><?php echo number_format($reg['payment_amount'], 2); ?></td>
                                        <td class="<?php echo $balance > 0 ? 'text-warning' : 'text-success'; ?> fw-bold">
                                            <?php echo number_format($balance, 2); ?>
                                        </td>
                                        <td>
                                            <?php if($reg['payment_status'] == 'paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($reg['letter_downloaded']): ?>
                                                <span class="badge bg-info">Downloaded</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not Downloaded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="showStudentDetails(<?php echo $reg['id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-user-graduate me-2"></i>Student Registration Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success"></div>
                        <p class="mt-2">Loading...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showStudentDetails(id) {
            fetch(`get_registration_details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const s = data.data;
                        const balance = 1000 - s.payment_amount;
                        document.getElementById('modalContent').innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="border rounded p-3 mb-3">
                                        <h6 class="fw-bold text-success"><i class="fas fa-user me-2"></i>Personal Info</h6>
                                        <table class="table table-sm">
                                            <tr><td width="40%"><strong>Name:</strong></td><td>${s.full_name}</td></tr>
                                            <tr><td><strong>Phone:</strong></td><td>${s.phone}</td></tr>
                                            <tr><td><strong>Email:</strong></td><td>${s.email}</td></tr>
                                            <tr><td><strong>Registered:</strong></td><td>${new Date(s.created_at).toLocaleDateString()}</td></tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 mb-3">
                                        <h6 class="fw-bold text-success"><i class="fas fa-graduation-cap me-2"></i>Course Info</h6>
                                        <table class="table table-sm">
                                            <tr><td width="40%"><strong>Course:</strong></td><td>${s.class_name}</td></tr>
                                            <tr><td><strong>Reg Number:</strong></td><td><strong>REG-${String(s.id).padStart(6, '0')}</strong></td></tr>
                                            <tr><td><strong>Letter:</strong></td><td>${s.letter_downloaded ? '<span class="badge bg-success">Downloaded</span>' : '<span class="badge bg-secondary">Not Downloaded</span>'}</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="border rounded p-3">
                                <h6 class="fw-bold text-success"><i class="fas fa-money-bill me-2"></i>Payment Details</h6>
                                <table class="table table-sm">
                                    <tr><td width="40%"><strong>Total Fee:</strong></td><td><strong>KES 1,000.00</strong></td></tr>
                                    <tr><td><strong>Amount Paid:</strong></td><td class="text-success fw-bold">KES ${parseFloat(s.payment_amount).toFixed(2)}</td></tr>
                                    <tr><td><strong>Balance:</strong></td><td class="${balance > 0 ? 'text-warning fw-bold' : 'text-success fw-bold'}">KES ${balance.toFixed(2)}</td></tr>
                                    <tr><td><strong>Status:</strong></td><td><span class="badge ${s.payment_status == 'paid' ? 'bg-success' : 'bg-warning'}">${s.payment_status.toUpperCase()}</span></td></tr>
                                    <tr><td><strong>Transaction ID:</strong></td><td><code>${s.transaction_id || 'N/A'}</code></td></tr>
                                </table>
                            </div>
                        `;
                    } else {
                        document.getElementById('modalContent').innerHTML = '<div class="alert alert-danger">Error loading details</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('modalContent').innerHTML = '<div class="alert alert-danger">Error loading details</div>';
                });
        }
    </script>
</body>
</html>