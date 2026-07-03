<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get class details
$stmt = $pdo->prepare("SELECT * FROM computer_classes WHERE id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if(!$class) {
    header("Location: register_class.php");
    exit();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$is_registered = false;

if($is_logged_in) {
    // Check if already registered for this class
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM class_registrations WHERE user_id = ? AND class_id = ?");
    $stmt->execute([$_SESSION['user_id'], $class_id]);
    $is_registered = $stmt->fetchColumn() > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($class['class_name']); ?> - Makueni County Innovation Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; }
        .class-card { background: white; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; }
        .class-header { background: linear-gradient(135deg, #1a5c3a, #0d3d2a); color: white; padding: 30px; }
        .btn-register { background: #FFD700; color: #1a5c3a; font-weight: bold; padding: 12px 30px; border-radius: 30px; border: none; }
        .btn-register:hover { background: #e6c200; transform: translateY(-2px); }
        .btn-login { background: #1a5c3a; color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; }
        .btn-login:hover { background: #0d3d2a; color: white; }
        .feature-list { list-style: none; padding: 0; }
        .feature-list li { padding: 8px 0; border-bottom: 1px solid #eee; }
        .feature-list li i { color: #1a5c3a; width: 25px; margin-right: 10px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="/innovation_hub/index.php">
                <i class="fas fa-lightbulb me-2"></i>Makueni County Innovation Hub
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/innovation_hub/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="/innovation_hub/ideas.php">Ideas</a></li>
                    <li class="nav-item"><a class="nav-link" href="/innovation_hub/trainings.php">Trainings</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Classes</a>
                        <ul class="dropdown-menu">
                            <?php
                            $all_classes = $pdo->query("SELECT * FROM computer_classes WHERE status IN ('active', 'upcoming') LIMIT 5")->fetchAll();
                            foreach($all_classes as $c):
                            ?>
                            <li><a class="dropdown-item" href="/innovation_hub/class-details.php?id=<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/innovation_hub/register_class.php">View All Classes</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/innovation_hub/mentors.php">Mentors</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="/innovation_hub/user/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/innovation_hub/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/innovation_hub/login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="/innovation_hub/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="class-card">
                    <div class="class-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-warning text-dark mb-2 px-3 py-1 rounded-pill">
                                    <?php echo ucfirst($class['status']); ?>
                                </span>
                                <h1 class="display-5 fw-bold mb-2"><?php echo htmlspecialchars($class['class_name']); ?></h1>
                                <p class="mb-0 opacity-75">
                                    <i class="fas fa-calendar me-1"></i> Starts: <?php echo date('F d, Y', strtotime($class['start_date'])); ?>
                                    | <i class="fas fa-clock me-1"></i> <?php echo $class['duration_weeks']; ?> weeks
                                </p>
                            </div>
                            <div class="text-center">
                                <div class="bg-white rounded-circle d-inline-flex p-3">
                                    <i class="fas fa-laptop-code fa-3x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="fw-bold">About This Course</h4>
                                <p><?php echo nl2br(htmlspecialchars($class['description'])); ?></p>
                                
                                <h4 class="fw-bold mt-4">What You'll Learn</h4>
                                <ul class="feature-list">
                                    <li><i class="fas fa-check-circle"></i> Practical hands-on training</li>
                                    <li><i class="fas fa-check-circle"></i> Expert instructors with industry experience</li>
                                    <li><i class="fas fa-check-circle"></i> Certificate upon completion</li>
                                    <li><i class="fas fa-check-circle"></i> Access to learning materials</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light p-4 rounded-3">
                                    <h5 class="fw-bold text-center">Course Details</h5>
                                    <hr>
                                    <p><i class="fas fa-clock me-2 text-success"></i> Duration: <strong><?php echo $class['duration_weeks']; ?> weeks</strong></p>
                                    <p><i class="fas fa-calendar-week me-2 text-success"></i> Schedule: <strong><?php echo htmlspecialchars($class['schedule']); ?></strong></p>
                                    <p><i class="fas fa-money-bill me-2 text-success"></i> Total Fee: <strong>KES <?php echo number_format($class['fee_amount'] ?? 1000, 2); ?></strong></p>
                                    <p><i class="fas fa-users me-2 text-success"></i> Max Students: <strong><?php echo $class['max_students']; ?></strong></p>
                                    <hr>
                                    
                                    <?php if($is_logged_in): ?>
                                        <?php if($is_registered): ?>
                                            <div class="alert alert-success text-center">
                                                <i class="fas fa-check-circle me-2"></i> You are already registered for this class!
                                            </div>
                                            <a href="/innovation_hub/user/my_class_portal.php" class="btn btn-success w-100">
                                                <i class="fas fa-laptop-code me-2"></i>Go to My Classes
                                            </a>
                                        <?php else: ?>
                                            <a href="/innovation_hub/class-registration.php?class_id=<?php echo $class['id']; ?>" class="btn-register w-100">
                                                <i class="fas fa-credit-card me-2"></i>Register Now (from KES 200)
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info text-center small">
                                            <i class="fas fa-info-circle me-2"></i> Please login to register for this class
                                        </div>
                                        <a href="/innovation_hub/login.php?redirect=class-details.php?id=<?php echo $class['id']; ?>" class="btn-login w-100">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login to Register
                                        </a>
                                        <div class="text-center mt-3">
                                            <small>Don't have an account? <a href="/innovation_hub/register.php">Register here</a></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>