<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

// Get current view
$current_view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

// ========== FUNCTION TO AUTO-UPDATE EVENT STATUS ==========
function autoUpdateEventStatuses($pdo) {
    $now = date('Y-m-d H:i:s');
    
    $pdo->prepare("
        UPDATE events 
        SET status = 'ongoing' 
        WHERE deleted_at IS NULL 
        AND status != 'ongoing'
        AND start_datetime <= ? 
        AND end_datetime >= ?
    ")->execute([$now, $now]);
    
    $pdo->prepare("
        UPDATE events 
        SET status = 'upcoming' 
        WHERE deleted_at IS NULL 
        AND status != 'upcoming'
        AND start_datetime > ?
    ")->execute([$now]);
    
    $pdo->prepare("
        UPDATE events 
        SET status = 'completed' 
        WHERE deleted_at IS NULL 
        AND status != 'completed'
        AND end_datetime < ?
    ")->execute([$now]);
}

autoUpdateEventStatuses($pdo);

// ========== NEWS MANAGEMENT ==========

// Handle Add News
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $title_color = trim($_POST['title_color']);
    $title_font_size = trim($_POST['title_font_size']);
    $image_height = trim($_POST['image_height']);
    $summary = trim($_POST['summary']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    if(empty($title) || empty($content)) {
        $_SESSION['error_message'] = "Please fill in at least the title and content.";
    } else {
        $featured_image = '';
        if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            $target_dir = "../assets/images/news/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['featured_image']['name']);
            $target_file = $target_dir . $file_name;
            $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            
            if(in_array($image_file_type, $allowed_types)) {
                if(move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                    $featured_image = 'assets/images/news/' . $file_name;
                } else {
                    $_SESSION['error_message'] = "Failed to upload image.";
                }
            } else {
                $_SESSION['error_message'] = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
            }
        }
        
        if(empty($_SESSION['error_message'])) {
            $user_id = $_SESSION['user_id'];
            $published_at = ($status == 'published') ? date('Y-m-d H:i:s') : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO news (title, title_color, title_font_size, image_height, slug, summary, content, featured_image, category, status, created_by, created_at, published_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            
            if($stmt->execute([$title, $title_color, $title_font_size, $image_height, $slug, $summary, $content, $featured_image, $category, $status, $user_id, $published_at])) {
                $news_id = $pdo->lastInsertId();
                
                // Handle gallery images
                if(isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
                    $gallery_dir = "../assets/images/news/gallery/";
                    if(!is_dir($gallery_dir)) {
                        mkdir($gallery_dir, 0777, true);
                    }
                    
                    $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                    $sort_order = 0;
                    
                    foreach($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                        if($_FILES['gallery_images']['error'][$key] == 0) {
                            $file_name = time() . '_' . $key . '_' . basename($_FILES['gallery_images']['name'][$key]);
                            $target_file = $gallery_dir . $file_name;
                            $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                            
                            if(in_array($image_file_type, $allowed_types)) {
                                if(move_uploaded_file($tmp_name, $target_file)) {
                                    $gallery_path = 'assets/images/news/gallery/' . $file_name;
                                    $caption = trim($_POST['gallery_caption'][$key] ?? '');
                                    
                                    $stmt2 = $pdo->prepare("
                                        INSERT INTO news_gallery (news_id, image_path, caption, sort_order) 
                                        VALUES (?, ?, ?, ?)
                                    ");
                                    $stmt2->execute([$news_id, $gallery_path, $caption, $sort_order]);
                                    $sort_order++;
                                }
                            }
                        }
                    }
                }
                
                $_SESSION['success_message'] = "News article added successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to add news.";
            }
        }
    }
    header("Location: dashboard.php?view=news");
    exit();
}

// Handle Edit News
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news'])) {
    $news_id = (int)$_POST['news_id'];
    $title = trim($_POST['title']);
    $title_color = trim($_POST['title_color']);
    $title_font_size = trim($_POST['title_font_size']);
    $image_height = trim($_POST['image_height']);
    $summary = trim($_POST['summary']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    $featured_image = $_POST['existing_image'] ?? '';
    if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $target_dir = "../assets/images/news/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['featured_image']['name']);
        $target_file = $target_dir . $file_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if(in_array($image_file_type, $allowed_types)) {
            if(move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                $featured_image = 'assets/images/news/' . $file_name;
            }
        }
    }
    
    $published_at = ($status == 'published') ? date('Y-m-d H:i:s') : null;
    
    $stmt = $pdo->prepare("
        UPDATE news SET 
            title = ?, title_color = ?, title_font_size = ?, image_height = ?,
            slug = ?, summary = ?, content = ?, featured_image = ?,
            category = ?, status = ?, updated_at = NOW(), published_at = ?
        WHERE id = ?
    ");
    
    if($stmt->execute([$title, $title_color, $title_font_size, $image_height, $slug, $summary, $content, $featured_image, $category, $status, $published_at, $news_id])) {
        // Handle gallery images
        if(isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
            $gallery_dir = "../assets/images/news/gallery/";
            if(!is_dir($gallery_dir)) {
                mkdir($gallery_dir, 0777, true);
            }
            
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $max_sort = $pdo->prepare("SELECT MAX(sort_order) FROM news_gallery WHERE news_id = ?");
            $max_sort->execute([$news_id]);
            $sort_order = ($max_sort->fetchColumn() ?? 0) + 1;
            
            foreach($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                if($_FILES['gallery_images']['error'][$key] == 0) {
                    $file_name = time() . '_' . $key . '_' . basename($_FILES['gallery_images']['name'][$key]);
                    $target_file = $gallery_dir . $file_name;
                    $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    
                    if(in_array($image_file_type, $allowed_types)) {
                        if(move_uploaded_file($tmp_name, $target_file)) {
                            $gallery_path = 'assets/images/news/gallery/' . $file_name;
                            $caption = trim($_POST['gallery_caption'][$key] ?? '');
                            
                            $stmt2 = $pdo->prepare("
                                INSERT INTO news_gallery (news_id, image_path, caption, sort_order) 
                                VALUES (?, ?, ?, ?)
                            ");
                            $stmt2->execute([$news_id, $gallery_path, $caption, $sort_order]);
                            $sort_order++;
                        }
                    }
                }
            }
        }
        
        // Handle delete gallery images
        if(isset($_POST['delete_gallery']) && !empty($_POST['delete_gallery'])) {
            foreach($_POST['delete_gallery'] as $gallery_id) {
                $stmt = $pdo->prepare("SELECT image_path FROM news_gallery WHERE id = ?");
                $stmt->execute([$gallery_id]);
                $img = $stmt->fetch();
                if($img) {
                    $file_path = "../" . $img['image_path'];
                    if(file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                $pdo->prepare("DELETE FROM news_gallery WHERE id = ?")->execute([$gallery_id]);
            }
        }
        
        $_SESSION['success_message'] = "News updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update news.";
    }
    header("Location: dashboard.php?view=news");
    exit();
}

// Handle Delete News (Soft Delete)
if(isset($_GET['action']) && $_GET['action'] == 'delete_news' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE news SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "News moved to trash!";
    header("Location: dashboard.php?view=news");
    exit();
}

// Handle Restore News
if(isset($_GET['action']) && $_GET['action'] == 'restore_news' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE news SET deleted_at = NULL WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "News restored!";
    header("Location: dashboard.php?view=news");
    exit();
}

// Handle Permanent Delete News
if(isset($_GET['action']) && $_GET['action'] == 'permanent_delete_news' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "News permanently deleted!";
    header("Location: dashboard.php?view=news");
    exit();
}

// ========== EVENT MANAGEMENT ==========

// Handle Add Event
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $end_date = $_POST['end_date'] ?? $start_date;
    $venue = trim($_POST['venue']);
    $max_participants = (int)($_POST['max_participants'] ?? 100);
    $organizer_name = trim($_POST['organizer_name'] ?? '');
    
    $start_datetime = $start_date . ' ' . $start_time;
    $end_datetime = $end_date . ' ' . $end_time;
    $now = date('Y-m-d H:i:s');
    
    if($now < $start_datetime) {
        $status = 'upcoming';
    } elseif($now >= $start_datetime && $now <= $end_datetime) {
        $status = 'ongoing';
    } else {
        $status = 'completed';
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO events (
            title, description, start_date, start_time, end_time, end_date, 
            start_datetime, end_datetime, venue, max_participants, organizer_name, status, deleted_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
    ");
    
    $stmt->execute([$title, $description, $start_date, $start_time, $end_time, $end_date, 
        $start_datetime, $end_datetime, $venue, $max_participants, $organizer_name, $status]);
    
    $_SESSION['success_message'] = "Event added successfully!";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Edit Event
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_event'])) {
    $event_id = (int)$_POST['event_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $end_date = $_POST['end_date'] ?? $start_date;
    $venue = trim($_POST['venue']);
    $max_participants = (int)($_POST['max_participants'] ?? 100);
    $organizer_name = trim($_POST['organizer_name'] ?? '');
    
    $start_datetime = $start_date . ' ' . $start_time;
    $end_datetime = $end_date . ' ' . $end_time;
    $now = date('Y-m-d H:i:s');
    
    if($now < $start_datetime) {
        $status = 'upcoming';
    } elseif($now >= $start_datetime && $now <= $end_datetime) {
        $status = 'ongoing';
    } else {
        $status = 'completed';
    }
    
    $stmt = $pdo->prepare("
        UPDATE events SET 
            title = ?, description = ?, 
            start_date = ?, start_time = ?, end_time = ?, end_date = ?,
            start_datetime = ?, end_datetime = ?,
            venue = ?, max_participants = ?, organizer_name = ?, status = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$title, $description, $start_date, $start_time, $end_time, $end_date,
        $start_datetime, $end_datetime, $venue, $max_participants, $organizer_name, $status, $event_id]);
    
    $_SESSION['success_message'] = "Event updated successfully!";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Confirm Booking
if(isset($_GET['action']) && $_GET['action'] == 'confirm_booking' && isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    $pdo->prepare("UPDATE event_bookings SET status = 'confirmed' WHERE id = ?")->execute([$booking_id]);
    $_SESSION['success_message'] = "Booking confirmed!";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Add Booking as New Event
if(isset($_POST['add_booking_as_event']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM event_bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if($booking && $booking['status'] == 'confirmed') {
        $title = $booking['event_name'];
        $description = "Event organized by " . $booking['organizer_name'];
        if(!empty($booking['expected_attendees'])) {
            $description .= ". Expected attendees: " . $booking['expected_attendees'];
        }
        
        $start_date = $booking['event_date'];
        $start_time = $booking['start_time'] ?? '10:00:00';
        $end_time = $booking['end_time'] ?? '17:00:00';
        $start_datetime = $start_date . ' ' . $start_time;
        $end_datetime = $start_date . ' ' . $end_time;
        $venue = "Greenpark, Makueni";
        $max_participants = 100;
        $organizer_name = $booking['organizer_name'];
        
        $now = date('Y-m-d H:i:s');
        
        if($now < $start_datetime) {
            $status = 'upcoming';
        } elseif($now >= $start_datetime && $now <= $end_datetime) {
            $status = 'ongoing';
        } else {
            $status = 'completed';
        }
        
        $stmt2 = $pdo->prepare("
            INSERT INTO events (title, description, start_date, start_time, end_time, start_datetime, end_datetime, venue, max_participants, organizer_name, status, deleted_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
        ");
        
        $stmt2->execute([$title, $description, $start_date, $start_time, $end_time, $start_datetime, $end_datetime, $venue, $max_participants, $organizer_name, $status]);
        $pdo->prepare("UPDATE event_bookings SET status = 'completed' WHERE id = ?")->execute([$booking_id]);
        
        $_SESSION['success_message'] = "Event has been added to the public events list!";
    }
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Edit Booking
if(isset($_POST['update_booking_details']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $organizer_name = trim($_POST['organizer_name']);
    $organizer_type = $_POST['organizer_type'];
    $phone = trim($_POST['phone']);
    $alt_phone = trim($_POST['alt_phone']);
    $email = trim($_POST['email']);
    $number_of_people = (int)$_POST['number_of_people'];
    $expected_attendees = trim($_POST['expected_attendees']);
    $duration_hours = (int)$_POST['duration_hours'];
    $special_requests = trim($_POST['special_requests']);
    
    $stmt = $pdo->prepare("
        UPDATE event_bookings SET 
            event_name = ?, event_date = ?, start_time = ?, end_time = ?,
            organizer_name = ?, organizer_type = ?, 
            phone = ?, alt_phone = ?, email = ?,
            number_of_people = ?, expected_attendees = ?,
            duration_hours = ?, special_requests = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$event_name, $event_date, $start_time, $end_time, $organizer_name, $organizer_type, $phone, $alt_phone, $email, $number_of_people, $expected_attendees, $duration_hours, $special_requests, $booking_id]);
    
    $_SESSION['success_message'] = "Booking details updated successfully!";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Delete Event
if(isset($_GET['action']) && $_GET['action'] == 'delete_event' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE events SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Event moved to trash!";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Restore Event
if(isset($_GET['action']) && $_GET['action'] == 'restore_event' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE events SET deleted_at = NULL WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Event restored!";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Permanent Delete Event
if(isset($_GET['action']) && $_GET['action'] == 'permanent_delete_event' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Event permanently deleted!";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Delete Booking
if(isset($_GET['action']) && $_GET['action'] == 'delete_booking' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM event_bookings WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Booking deleted successfully!";
    header("Location: dashboard.php?view=events");
    exit();
}

// ========== CLASS MANAGEMENT WITH TRASH ==========

// Handle Add Class
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $duration_weeks = (int)$_POST['duration_weeks'];
    $fee_amount = (float)$_POST['fee_amount'];
    
    $stmt = $pdo->prepare("
        INSERT INTO computer_classes (class_name, description, start_date, duration_weeks, fee_amount, status, deleted_at) 
        VALUES (?, ?, ?, ?, ?, 'active', NULL)
    ");
    
    if($stmt->execute([$class_name, $description, $start_date, $duration_weeks, $fee_amount])) {
        $_SESSION['success_message'] = "Class added successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to add class.";
    }
    header("Location: dashboard.php?view=classes");
    exit();
}

// Handle Edit/Update Active Class
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_class'])) {
    $class_id = (int)$_POST['class_id'];
    $class_name = trim($_POST['class_name']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $duration_weeks = (int)$_POST['duration_weeks'];
    $fee_amount = (float)$_POST['fee_amount'];
    
    $stmt = $pdo->prepare("
        UPDATE computer_classes 
        SET class_name = ?, description = ?, start_date = ?, duration_weeks = ?, fee_amount = ?
        WHERE id = ? AND deleted_at IS NULL
    ");
    
    if($stmt->execute([$class_name, $description, $start_date, $duration_weeks, $fee_amount, $class_id])) {
        $_SESSION['success_message'] = "Class updated successfully! Changes are now visible to users.";
    } else {
        $_SESSION['error_message'] = "Failed to update class.";
    }
    header("Location: dashboard.php?view=classes");
    exit();
}

// Handle Soft Delete (Move to Trash)
if(isset($_GET['action']) && $_GET['action'] == 'delete_class' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE computer_classes SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Class moved to trash!";
    header("Location: dashboard.php?view=classes");
    exit();
}

// Handle Restore from Trash
if(isset($_GET['action']) && $_GET['action'] == 'restore_class' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE computer_classes SET deleted_at = NULL WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Class restored successfully!";
    header("Location: dashboard.php?view=classes");
    exit();
}

// Handle Restore & Add as New Class (from trash after editing)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_and_add_class'])) {
    $old_class_id = (int)$_POST['class_id'];
    $class_name = trim($_POST['class_name']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $duration_weeks = (int)$_POST['duration_weeks'];
    $fee_amount = (float)$_POST['fee_amount'];
    
    // Insert as new class
    $stmt = $pdo->prepare("
        INSERT INTO computer_classes (class_name, description, start_date, duration_weeks, fee_amount, status, deleted_at) 
        VALUES (?, ?, ?, ?, ?, 'active', NULL)
    ");
    
    if($stmt->execute([$class_name, $description, $start_date, $duration_weeks, $fee_amount])) {
        $_SESSION['success_message'] = "Class restored and added as a new class successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to restore class.";
    }
    header("Location: dashboard.php?view=classes");
    exit();
}

// Handle Permanent Delete
if(isset($_GET['action']) && $_GET['action'] == 'permanent_delete_class' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM computer_classes WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Class permanently deleted!";
    header("Location: dashboard.php?view=classes");
    exit();
}

// Handle Update Payment Amount
if(isset($_POST['update_payment']) && isset($_POST['registration_id'])) {
    $registration_id = (int)$_POST['registration_id'];
    $additional_payment = (float)$_POST['payment_amount'];
    $class_id = (int)$_POST['class_id'];
    
    $stmt = $pdo->prepare("SELECT payment_amount FROM class_registrations WHERE id = ?");
    $stmt->execute([$registration_id]);
    $current_payment = $stmt->fetchColumn();
    
    $stmt2 = $pdo->prepare("SELECT fee_amount FROM computer_classes WHERE id = ?");
    $stmt2->execute([$class_id]);
    $class_fee = $stmt2->fetchColumn();
    
    $new_payment = $current_payment + $additional_payment;
    $payment_status = ($new_payment >= $class_fee) ? 'paid' : 'pending';
    
    $stmt = $pdo->prepare("
        UPDATE class_registrations 
        SET payment_amount = ?, payment_status = ?
        WHERE id = ?
    ");
    $stmt->execute([$new_payment, $payment_status, $registration_id]);
    
    $balance = $class_fee - $new_payment;
    $_SESSION['success_message'] = "Payment updated! New balance: KES " . number_format($balance, 2);
    header("Location: dashboard.php?view=classes");
    exit();
}

// ========== HANDLE ISSUE CERTIFICATE ==========
if(isset($_POST['action']) && $_POST['action'] == 'issue_certificate') {
    header('Content-Type: application/json');
    
    $registration_id = (int)$_POST['registration_id'];
    $completion_date = $_POST['completion_date'];
    
    $stmt = $pdo->prepare("
        SELECT cr.*, c.class_name 
        FROM class_registrations cr
        JOIN computer_classes c ON cr.class_id = c.id
        WHERE cr.id = ?
    ");
    $stmt->execute([$registration_id]);
    $student = $stmt->fetch();
    
    if(!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }
    
    $certificate_number = 'MCIH-' . date('Y') . '-' . str_pad($registration_id, 5, '0', STR_PAD_LEFT);
    
    try {
        // Update class_registrations
        $pdo->prepare("
            UPDATE class_registrations 
            SET certificate_issued = 1, 
                certificate_number = ?, 
                completion_date = ?,
                graduation_status = 'graduated'
            WHERE id = ?
        ")->execute([$certificate_number, $completion_date, $registration_id]);
        
        // Insert into certificates table
        try {
            $stmt = $pdo->prepare("
                INSERT INTO certificates (registration_id, student_name, course_name, certificate_number, issue_date, completion_date)
                VALUES (?, ?, ?, ?, CURDATE(), ?)
            ");
            $stmt->execute([
                $registration_id,
                $student['full_name'],
                $student['class_name'] ?? 'N/A',
                $certificate_number,
                $completion_date
            ]);
        } catch(PDOException $e) {
            // Certificates table might not exist - log error but continue
            error_log("Certificate insert error: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'certificate_number' => $certificate_number,
            'message' => 'Certificate issued successfully',
            'registration_id' => $registration_id,
            'student_name' => $student['full_name'],
            'course_name' => $student['class_name'] ?? 'N/A'
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== AJIRY MANAGEMENT ==========

if(isset($_POST['update_ajiry_status']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE ajiry_bookings SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $booking_id]);
    
    $_SESSION['success_message'] = "Booking updated to " . ucfirst($new_status);
    header("Location: dashboard.php?view=ajiry");
    exit();
}

if(isset($_GET['action']) && $_GET['action'] == 'delete_ajiry_booking' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM ajiry_bookings WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "AJIRY booking deleted!";
    header("Location: dashboard.php?view=ajiry");
    exit();
}

// ========== FEEDBACK MANAGEMENT ==========

// Handle Approve Feedback
if(isset($_GET['action']) && $_GET['action'] == 'approve_feedback' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE feedback SET status = 'approved', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = "Feedback approved successfully!";
    header("Location: dashboard.php?view=feedback");
    exit();
}

// Handle Delete Feedback
if(isset($_GET['action']) && $_GET['action'] == 'delete_feedback' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = "Feedback deleted successfully!";
    header("Location: dashboard.php?view=feedback");
    exit();
}

// ========== USER MANAGEMENT ==========

// Handle Make Admin
if(isset($_GET['action']) && $_GET['action'] == 'make_admin' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "User promoted to Admin!";
    header("Location: dashboard.php?view=users");
    exit();
}

// Handle Remove Admin
if(isset($_GET['action']) && $_GET['action'] == 'remove_admin' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Admin privileges removed!";
    header("Location: dashboard.php?view=users");
    exit();
}

// Handle Delete User
if(isset($_GET['action']) && $_GET['action'] == 'delete_user' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "User deleted successfully!";
    header("Location: dashboard.php?view=users");
    exit();
}

// ========== GET STATISTICS ==========

// News Statistics
$total_news = $pdo->query("SELECT COUNT(*) FROM news WHERE deleted_at IS NULL")->fetchColumn();
$published_news = $pdo->query("SELECT COUNT(*) FROM news WHERE deleted_at IS NULL AND status = 'published'")->fetchColumn();
$draft_news = $pdo->query("SELECT COUNT(*) FROM news WHERE deleted_at IS NULL AND status = 'draft'")->fetchColumn();

// Event Statistics
$total_events = $pdo->query("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL")->fetchColumn();
$upcoming_events = $pdo->query("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL AND status = 'upcoming' AND start_datetime > NOW()")->fetchColumn();
$ongoing_events = $pdo->query("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL AND status = 'ongoing' AND start_datetime <= NOW() AND end_datetime >= NOW()")->fetchColumn();

// Event Bookings Statistics
$total_event_bookings = $pdo->query("SELECT COUNT(*) FROM event_bookings")->fetchColumn();
$pending_event_bookings = $pdo->query("SELECT COUNT(*) FROM event_bookings WHERE status = 'pending'")->fetchColumn();

// AJIRY Statistics
$total_ajiry_bookings = $pdo->query("SELECT COUNT(*) FROM ajiry_bookings")->fetchColumn();
$pending_ajiry = $pdo->query("SELECT COUNT(*) FROM ajiry_bookings WHERE status = 'pending'")->fetchColumn();
$confirmed_ajiry = $pdo->query("SELECT COUNT(*) FROM ajiry_bookings WHERE status = 'confirmed'")->fetchColumn();

// Class Statistics
$total_class_students = $pdo->query("SELECT COUNT(*) FROM class_registrations")->fetchColumn();
$total_graduates = $pdo->query("SELECT COUNT(*) FROM class_registrations WHERE certificate_issued = 1")->fetchColumn();
$total_paid = $pdo->query("SELECT SUM(payment_amount) FROM class_registrations")->fetchColumn();

// Other Statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_feedback = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$pending_feedback = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status='pending'")->fetchColumn();
$total_mentors = $pdo->query("SELECT COUNT(*) FROM mentors")->fetchColumn();

// ========== FETCH ALL DATA ==========

// News
$all_news = $pdo->query("SELECT n.*, u.username as author FROM news n LEFT JOIN users u ON n.created_by = u.id WHERE n.deleted_at IS NULL ORDER BY n.created_at DESC")->fetchAll();
$trashed_news = $pdo->query("SELECT * FROM news WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC")->fetchAll();

// Events
$all_events = $pdo->query("SELECT * FROM events WHERE deleted_at IS NULL ORDER BY start_datetime ASC")->fetchAll();
$trashed_events = $pdo->query("SELECT * FROM events WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC")->fetchAll();

// Event Bookings
$event_bookings = $pdo->query("SELECT * FROM event_bookings ORDER BY created_at DESC")->fetchAll();

// AJIRY Bookings
$ajiry_bookings = $pdo->query("SELECT * FROM ajiry_bookings ORDER BY created_at DESC")->fetchAll();

// Classes - Active and Trashed
$all_classes = $pdo->query("SELECT * FROM computer_classes WHERE deleted_at IS NULL ORDER BY class_name")->fetchAll();
$trashed_classes = $pdo->query("SELECT * FROM computer_classes WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC")->fetchAll();

// Class Registrations with full details
$class_registrations = $pdo->query("
    SELECT r.*, c.class_name, c.fee_amount as class_fee 
    FROM class_registrations r 
    JOIN computer_classes c ON r.class_id = c.id 
    ORDER BY r.created_at DESC
")->fetchAll();

// Graduates with certificate details
$graduates = $pdo->query("
    SELECT cr.*, c.class_name, cert.certificate_number, cert.issue_date, cert.completion_date
    FROM class_registrations cr
    LEFT JOIN certificates cert ON cr.id = cert.registration_id
    LEFT JOIN computer_classes c ON cr.class_id = c.id
    WHERE cr.certificate_issued = 1
    ORDER BY cert.issue_date DESC
")->fetchAll();

// Other data
$all_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$all_feedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC")->fetchAll();
$mentors_list = $pdo->query("SELECT m.*, u.full_name, u.email FROM mentors m JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Makueni County Innovation Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-brand { font-weight: bold; display: flex; align-items: center; }
        .bg-success-custom { background: linear-gradient(135deg, #0a3d2a, #1a5c3a); }
        .county-logo-nav {
            height: 45px; width: auto; margin-right: 12px; object-fit: contain;
            background: white; border-radius: 50%; padding: 5px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .dashboard-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }
        .card-header-custom {
            padding: 25px;
            background: linear-gradient(135deg, #1a5c3a, #0a3d2a);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-icon { width: 65px; height: 65px; background: rgba(255,255,255,0.2); border-radius: 15px; display: flex; align-items: center; justify-content: center; }
        .card-icon i { font-size: 32px; }
        .card-stats { font-size: 36px; font-weight: 800; }
        .card-label { font-size: 14px; opacity: 0.9; }
        
        .detail-view {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .back-btn {
            background: #1a5c3a;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            margin-bottom: 20px;
        }
        .back-btn:hover { background: #0a3d2a; transform: translateX(-5px); }
        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: #1a5c3a;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #FFD700;
            display: inline-block;
        }
        .summary-stats { background: #e8f5e9; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .table-custom { width: 100%; font-size: 14px; border-collapse: collapse; }
        .table-custom th { background: #f8f9fa; padding: 12px; font-weight: 600; text-align: left; border-bottom: 2px solid #dee2e6; }
        .table-custom td { padding: 10px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .btn-action { padding: 4px 10px; font-size: 12px; border-radius: 15px; margin: 2px; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-pending { background: #ffc107; color: #856404; }
        .status-confirmed { background: #28a745; color: white; }
        .status-paid { background: #28a745; color: white; }
        .status-completed { background: #6c757d; color: white; }
        .status-upcoming { background: #17a2b8; color: white; }
        .status-ongoing { background: #28a745; color: white; }
        .status-enrolled { background: #ffc107; color: #856404; }
        .status-graduated { background: #28a745; color: white; }
        .status-published { background: #28a745; color: white; }
        .status-draft { background: #ffc107; color: #856404; }
        .status-archived { background: #6c757d; color: white; }
        
        .modal-content { border-radius: 20px; overflow: hidden; }
        .modal-header { background: linear-gradient(135deg, #0a3d2a, #1a5c3a); color: white; border: none; padding: 20px; }
        .form-label { font-weight: 600; color: #1a5c3a; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #ddd; padding: 10px 15px; }
        .trash-item { background: #fff3cd; }
        .nav-tabs .nav-link { color: #1a5c3a; font-weight: 500; }
        .nav-tabs .nav-link.active { background: #1a5c3a; color: white; border-color: #1a5c3a; }
        .ql-container { min-height: 150px; }
        .ql-editor { min-height: 150px; }
        .news-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .color-dot { display: inline-block; width: 16px; height: 16px; border-radius: 50%; border: 1px solid #ddd; vertical-align: middle; margin-right: 5px; }
        
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .card-stats { font-size: 28px; }
            .table-custom { font-size: 12px; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success-custom">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/images/logo/makueni-logo.png" alt="Makueni County Logo" class="county-logo-nav" onerror="this.style.display='none'">
                <div><div>MAKUENI COUNTY</div><small>INNOVATION HUB - ADMIN</small></div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="bg-success text-white p-4 rounded-4">
                    <h2 class="fw-bold mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>!</h2>
                    <p class="mb-0">Manage Makueni County Innovation Hub - Click on any card to view details</p>
                </div>
            </div>
        </div>

        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if($current_view == 'dashboard'): ?>
        <div class="dashboard-grid">
            <div class="dashboard-card" onclick="window.location.href='?view=news'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-newspaper"></i></div>
                    <div class="text-end">
                        <div class="card-stats"><?php echo $total_news; ?></div>
                        <div class="card-label">News Articles</div>
                        <small><?php echo $published_news; ?> Published | <?php echo $draft_news; ?> Draft</small>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card" onclick="window.location.href='?view=events'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="text-end">
                        <div class="card-stats"><?php echo $total_events; ?></div>
                        <div class="card-label">Total Events</div>
                        <small><?php echo $total_event_bookings; ?> Bookings | <?php echo $pending_event_bookings; ?> Pending</small>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card" onclick="window.location.href='?view=ajiry'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-microphone-alt"></i></div>
                    <div class="text-end">
                        <div class="card-stats"><?php echo $total_ajiry_bookings; ?></div>
                        <div class="card-label">AJIRY Bookings</div>
                        <?php if($pending_ajiry > 0): ?>
                            <small class="text-warning"><?php echo $pending_ajiry; ?> pending</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card" onclick="window.location.href='?view=classes'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-laptop-code"></i></div>
                    <div class="text-end">
                        <div class="card-stats"><?php echo $total_class_students; ?></div>
                        <div class="card-label">Students Enrolled</div>
                        <small><?php echo $total_graduates; ?> Graduates</small>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card" onclick="window.location.href='?view=users'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="text-end"><div class="card-stats"><?php echo $total_users; ?></div><div class="card-label">Total Users</div></div>
                </div>
            </div>
            
            <div class="dashboard-card" onclick="window.location.href='?view=feedback'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-comment"></i></div>
                    <div class="text-end"><div class="card-stats"><?php echo $total_feedback; ?></div><div class="card-label">Feedback (<?php echo $pending_feedback; ?> pending)</div></div>
                </div>
            </div>
            
            <div class="dashboard-card" onclick="window.location.href='?view=mentors'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-user-tie"></i></div>
                    <div class="text-end"><div class="card-stats"><?php echo $total_mentors; ?></div><div class="card-label">Mentors</div></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==================== NEWS MANAGEMENT ==================== -->
        <?php if($current_view == 'news'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</button>
            
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="section-title"><i class="fas fa-newspaper me-2"></i>News Management</h3>
                <button class="btn btn-success mb-3" onclick="window.location.href='add_news.php'">
                    <i class="fas fa-plus me-2"></i> Add New Article
                </button>
            </div>
            
            <div class="summary-stats">
                <div class="row text-center">
                    <div class="col-md-3"><h5>Total Articles</h5><h3 class="text-primary"><?php echo $total_news; ?></h3></div>
                    <div class="col-md-3"><h5>Published</h5><h3 class="text-success"><?php echo $published_news; ?></h3></div>
                    <div class="col-md-3"><h5>Draft</h5><h3 class="text-warning"><?php echo $draft_news; ?></h3></div>
                    <div class="col-md-3"><h5>Trash</h5><h3 class="text-danger"><?php echo count($trashed_news); ?></h3></div>
                </div>
            </div>
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#newsList">📰 All News (<?php echo count($all_news); ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#trashedNews">🗑️ Trash (<?php echo count($trashed_news); ?>)</button></li>
            </ul>
            
            <div class="tab-content">
                <div class="tab-pane fade show active" id="newsList">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Author</th>
                                    <th>Views</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_news as $news): ?>
                                <tr>
                                    <td><?php echo $news['id']; ?></td>
                                    <td>
                                        <?php if($news['featured_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($news['featured_image']); ?>" class="news-img" alt="Image">
                                        <?php else: ?>
                                            <div class="news-img d-flex align-items-center justify-content-center bg-light">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="color: <?php echo htmlspecialchars($news['title_color'] ?? '#1a5c3a'); ?>; font-size: <?php echo htmlspecialchars($news['title_font_size'] ?? '16px'); ?>;">
                                            <?php echo htmlspecialchars($news['title']); ?>
                                        </strong>
                                        <br>
                                        <small class="text-muted">
                                            <span class="color-dot" style="background-color: <?php echo htmlspecialchars($news['title_color'] ?? '#1a5c3a'); ?>;"></span>
                                            <?php echo htmlspecialchars($news['title_color'] ?? '#1a5c3a'); ?> | 
                                            <?php echo htmlspecialchars($news['title_font_size'] ?? '28px'); ?>
                                        </small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $news['category'] ?? 'General')); ?></span></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $news['status']; ?>">
                                            <?php echo ucfirst($news['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($news['author'] ?? 'Unknown'); ?></td>
                                    <td><?php echo $news['views']; ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="edit_news.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-warning btn-action">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../news_detail.php?id=<?php echo $news['id']; ?>" target="_blank" class="btn btn-sm btn-success btn-action">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?view=news&action=delete_news&id=<?php echo $news['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Move to trash?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($all_news)): ?>
                                <tr><td colspan="8" class="text-center py-4">No news articles found. <a href="add_news.php">Add your first article</a></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="trashedNews">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Deleted On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($trashed_news as $news): ?>
                                <tr class="trash-item">
                                    <td><?php echo $news['id']; ?></td>
                                    <td>
                                        <?php if($news['featured_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($news['featured_image']); ?>" class="news-img" alt="Image">
                                        <?php else: ?>
                                            <div class="news-img d-flex align-items-center justify-content-center bg-light">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($news['title']); ?></strong></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($news['deleted_at'])); ?></td>
                                    <td>
                                        <a href="?view=news&action=restore_news&id=<?php echo $news['id']; ?>" class="btn btn-sm btn-success btn-action">
                                            <i class="fas fa-trash-restore"></i> Restore
                                        </a>
                                        <a href="?view=news&action=permanent_delete_news&id=<?php echo $news['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Permanently delete this news? This cannot be undone!')">
                                            <i class="fas fa-trash-alt"></i> Permanent Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($trashed_news)): ?>
                                <tr><td colspan="5" class="text-center py-4">Trash is empty</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==================== EVENTS MANAGEMENT ==================== -->
        <?php if($current_view == 'events'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</button>
            
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="section-title"><i class="fas fa-calendar-alt me-2"></i>Events & Bookings Management</h3>
                <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="fas fa-plus me-2"></i> Add New Event
                </button>
            </div>
            
            <div class="summary-stats">
                <div class="row text-center">
                    <div class="col-md-4"><h5>Total Events</h5><h3 class="text-primary"><?php echo $total_events; ?></h3></div>
                    <div class="col-md-4"><h5>Upcoming</h5><h3 class="text-info"><?php echo $upcoming_events; ?></h3></div>
                    <div class="col-md-4"><h5>Ongoing</h5><h3 class="text-success"><?php echo $ongoing_events; ?></h3></div>
                </div>
            </div>
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#eventsList">📅 Events List</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bookingsList">📋 Member Bookings (<?php echo $pending_event_bookings; ?> pending)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#trashedEvents">🗑️ Trash</button></li>
            </ul>
            
            <div class="tab-content">
                <div class="tab-pane fade show active" id="eventsList">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead><tr><th>ID</th><th>Event Title</th><th>Date</th><th>Time</th><th>Venue</th><th>Organizer</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($all_events as $event): ?>
                                <tr>
                                    <td><?php echo $event['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($event['title']); ?></strong><br><small><?php echo htmlspecialchars(substr($event['description'] ?? '', 0, 40)); ?></small></td>
                                    <td><?php echo date('M d, Y', strtotime($event['start_date'])); ?></td>
                                    <td><?php echo date('g:i A', strtotime($event['start_time'])) . ' - ' . date('g:i A', strtotime($event['end_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['venue'] ?? 'Greenpark'); ?></td>
                                    <td><?php echo htmlspecialchars($event['organizer_name'] ?? 'Greenpark'); ?></td>
                                    <td><span class="status-badge status-<?php echo $event['status'] ?? 'upcoming'; ?>"><?php echo ucfirst($event['status'] ?? 'Upcoming'); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#editEventModal<?php echo $event['id']; ?>">Edit</button>
                                        <a href="?view=events&action=delete_event&id=<?php echo $event['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Move to trash?')">Delete</a>
                                     </span></td>
                                </tr>
                                <div class="modal fade" id="editEventModal<?php echo $event['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg"><div class="modal-content"><form method="POST"><div class="modal-header bg-warning text-dark"><h5 class="modal-title">Edit Event</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body"><input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <div class="mb-3"><label>Event Title *</label><input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($event['title']); ?>" required></div>
                                    <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea></div>
                                    <div class="row"><div class="col-md-4 mb-3"><label>Event Date *</label><input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d', strtotime($event['start_date'])); ?>" required></div>
                                    <div class="col-md-4 mb-3"><label>Start Time *</label><input type="time" name="start_time" class="form-control" value="<?php echo date('H:i', strtotime($event['start_time'])); ?>" required></div>
                                    <div class="col-md-4 mb-3"><label>End Time *</label><input type="time" name="end_time" class="form-control" value="<?php echo date('H:i', strtotime($event['end_time'])); ?>" required></div></div>
                                    <div class="row"><div class="col-md-6 mb-3"><label>Venue</label><input type="text" name="venue" class="form-control" value="<?php echo htmlspecialchars($event['venue'] ?? 'Greenpark, Makueni'); ?>"></div>
                                    <div class="col-md-6 mb-3"><label>Max Participants</label><input type="number" name="max_participants" class="form-control" value="<?php echo $event['max_participants'] ?? 100; ?>"></div></div>
                                    <div class="mb-3"><label>Organizer Name</label><input type="text" name="organizer_name" class="form-control" value="<?php echo htmlspecialchars($event['organizer_name'] ?? ''); ?>"></div></div>
                                    <div class="modal-footer"><button type="submit" name="edit_event" class="btn btn-warning">Save Changes</button></div></form></div></div></div>
                                <?php endforeach; ?>
                                <?php if(empty($all_events)): ?>
                                <tr><td colspan="8" class="text-center py-4">No events found.</span></td>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="bookingsList">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead><tr><th>ID</th><th>Event Name</th><th>Organizer</th><th>Phone</th><th>Event Date</th><th>Time</th><th>People</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($event_bookings as $booking): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($booking['event_name'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['organizer_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($booking['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($booking['start_time'] ?? 'TBA') . ' - ' . htmlspecialchars($booking['end_time'] ?? 'TBA'); ?></td>
                                    <td><?php echo $booking['number_of_people'] ?? 0; ?></td>
                                    <td><span class="status-badge status-<?php echo $booking['status'] ?? 'pending'; ?>"><?php echo ucfirst($booking['status'] ?? 'Pending'); ?></span></td>
                                    <td>
                                        <?php if(($booking['status'] ?? 'pending') == 'pending'): ?>
                                            <a href="?view=events&action=confirm_booking&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-success btn-action">Confirm</a>
                                        <?php elseif(($booking['status'] ?? 'pending') == 'confirmed'): ?>
                                            <button class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#editBookingModal<?php echo $booking['id']; ?>">Edit</button>
                                            <form method="POST" style="display: inline-block;"><input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>"><button type="submit" name="add_booking_as_event" class="btn btn-sm btn-info btn-action">Add as Event</button></form>
                                        <?php endif; ?>
                                        <a href="?view=events&action=delete_booking&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Delete this booking?')">Delete</a>
                                     </span></td>
                                </tr>
                                <div class="modal fade" id="editBookingModal<?php echo $booking['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg"><div class="modal-content"><form method="POST"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Edit Booking #<?php echo $booking['id']; ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body"><input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <div class="mb-3"><label>Event Name *</label><input type="text" name="event_name" class="form-control" value="<?php echo htmlspecialchars($booking['event_name'] ?? ''); ?>" required></div>
                                    <div class="row"><div class="col-md-4 mb-3"><label>Event Date *</label><input type="date" name="event_date" class="form-control" value="<?php echo $booking['event_date'] ?? ''; ?>" required></div>
                                    <div class="col-md-4 mb-3"><label>Start Time *</label><input type="time" name="start_time" class="form-control" value="<?php echo $booking['start_time'] ?? '10:00'; ?>" required></div>
                                    <div class="col-md-4 mb-3"><label>End Time *</label><input type="time" name="end_time" class="form-control" value="<?php echo $booking['end_time'] ?? '17:00'; ?>" required></div></div>
                                    <div class="mb-3"><label>Organizer Name *</label><input type="text" name="organizer_name" class="form-control" value="<?php echo htmlspecialchars($booking['organizer_name'] ?? ''); ?>" required></div>
                                    <div class="row"><div class="col-md-6 mb-3"><label>Phone Number *</label><input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($booking['phone'] ?? ''); ?>" required></div>
                                    <div class="col-md-6 mb-3"><label>Alternative Phone</label><input type="tel" name="alt_phone" class="form-control" value="<?php echo htmlspecialchars($booking['alt_phone'] ?? ''); ?>"></div></div>
                                    <div class="row"><div class="col-md-6 mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($booking['email'] ?? ''); ?>"></div>
                                    <div class="col-md-6 mb-3"><label>Number of People</label><input type="number" name="number_of_people" class="form-control" value="<?php echo $booking['number_of_people'] ?? 1; ?>" min="1"></div></div>
                                    <div class="mb-3"><label>Special Requests</label><textarea name="special_requests" class="form-control" rows="2"><?php echo htmlspecialchars($booking['special_requests'] ?? ''); ?></textarea></div></div>
                                    <div class="modal-footer"><button type="submit" name="update_booking_details" class="btn btn-primary">Save Changes</button></div></form></div></div></div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="trashedEvents">
                    <div class="table-responsive"><table class="table-custom"><thead><tr><th>ID</th><th>Event Title</th><th>Deleted On</th><th>Original Date</th><th>Actions</th></tr></thead>
                    <tbody><?php foreach($trashed_events as $event): ?><tr class="trash-item"><td><?php echo $event['id']; ?></td><td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td><td><?php echo date('M d, Y', strtotime($event['deleted_at'])); ?></td><td><?php echo date('M d, Y', strtotime($event['start_date'])); ?></td><td><a href="?view=events&action=restore_event&id=<?php echo $event['id']; ?>" class="btn btn-sm btn-success btn-action">Restore</a> <a href="?view=events&action=permanent_delete_event&id=<?php echo $event['id']; ?>" class="btn btn-sm btn-dark btn-action">Permanent Delete</a></span></td></tr><?php endforeach; ?></tbody></table></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==================== CLASSES MANAGEMENT ==================== -->
        <?php if($current_view == 'classes'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</button>
            
            <h3 class="section-title"><i class="fas fa-laptop-code me-2"></i>Computer Classes & Student Management</h3>
            
            <div class="summary-stats">
                <div class="row text-center">
                    <div class="col-md-3"><h5>Active Classes</h5><h3 class="text-primary"><?php echo count($all_classes); ?></h3></div>
                    <div class="col-md-3"><h5>Trash</h5><h3 class="text-warning"><?php echo count($trashed_classes); ?></h3></div>
                    <div class="col-md-3"><h5>Total Students</h5><h3 class="text-info"><?php echo $total_class_students; ?></h3></div>
                    <div class="col-md-3"><h5>Total Graduates</h5><h3 class="text-success"><?php echo $total_graduates; ?></h3></div>
                </div>
            </div>
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#classesManage">📚 Active Classes (<?php echo count($all_classes); ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#trashManage">🗑️ Trash (<?php echo count($trashed_classes); ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#studentsManage">👨‍🎓 Enrolled Students (<?php echo $total_class_students; ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#graduatesManage">🎓 Graduates (<?php echo $total_graduates; ?>)</button></li>
            </ul>
            
            <div class="tab-content">
                <div class="tab-pane fade show active" id="classesManage">
                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addClassModal">
                            <i class="fas fa-plus me-2"></i> Add New Class
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Class Name</th>
                                    <th>Description</th>
                                    <th>Start Date</th>
                                    <th>Duration</th>
                                    <th>Fee</th>
                                    <th>Enrolled</th>
                                    <th>Graduated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_classes as $class):
                                    $enrolled_stmt = $pdo->prepare("SELECT COUNT(*) FROM class_registrations WHERE class_id = ?");
                                    $enrolled_stmt->execute([$class['id']]);
                                    $enrolled_count = $enrolled_stmt->fetchColumn();
                                    $grad_stmt = $pdo->prepare("SELECT COUNT(*) FROM class_registrations WHERE class_id = ? AND certificate_issued = 1");
                                    $grad_stmt->execute([$class['id']]);
                                    $graduated_count = $grad_stmt->fetchColumn();
                                ?>
                                <tr>
                                    <td><?php echo $class['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($class['description'] ?? '', 0, 60)); ?>...</span></td>
                                    <td><?php echo date('M d, Y', strtotime($class['start_date'] ?? 'now')); ?></td>
                                    <td><?php echo $class['duration_weeks'] ?? '8'; ?> weeks</span></td>
                                    <td>KES <?php echo number_format($class['fee_amount'] ?? 1000, 2); ?></td>
                                    <td class="text-center"><?php echo $enrolled_count; ?></td>
                                    <td class="text-center"><?php echo $graduated_count; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning btn-action" onclick="editClass(<?php echo $class['id']; ?>, '<?php echo htmlspecialchars($class['class_name']); ?>', '<?php echo htmlspecialchars($class['description']); ?>', '<?php echo $class['start_date']; ?>', <?php echo $class['duration_weeks'] ?? 8; ?>, <?php echo $class['fee_amount'] ?? 1000; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?view=classes&action=delete_class&id=<?php echo $class['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Move this class to trash?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                     </span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($all_classes)): ?>
                                <tr><td colspan="9" class="text-center py-4">No active classes found. Click "Add New Class" to create one.</span></td>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="trashManage">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Class Name</th>
                                    <th>Description</th>
                                    <th>Deleted On</th>
                                    <th>Original Fee</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($trashed_classes as $class): ?>
                                <tr class="trash-item">
                                    <td><?php echo $class['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($class['description'] ?? '', 0, 60)); ?>...</span></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($class['deleted_at'])); ?></td>
                                    <td>KES <?php echo number_format($class['fee_amount'] ?? 1000, 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success btn-action" onclick="restoreClass(<?php echo $class['id']; ?>)">
                                            <i class="fas fa-trash-restore"></i> Restore
                                        </button>
                                        <button class="btn btn-sm btn-primary btn-action" onclick="editTrashedClass(<?php echo $class['id']; ?>, '<?php echo htmlspecialchars($class['class_name']); ?>', '<?php echo htmlspecialchars($class['description']); ?>', '<?php echo $class['start_date']; ?>', <?php echo $class['duration_weeks'] ?? 8; ?>, <?php echo $class['fee_amount'] ?? 1000; ?>)">
                                            <i class="fas fa-edit"></i> Edit & Restore
                                        </button>
                                        <a href="?view=classes&action=permanent_delete_class&id=<?php echo $class['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Permanently delete this class? This cannot be undone!')">
                                            <i class="fas fa-trash-alt"></i> Permanent Delete
                                        </a>
                                     </span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($trashed_classes)): ?>
                                <td><td colspan="6" class="text-center py-4">Trash is empty</span></td>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="studentsManage">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Class Fee</th>
                                    <th>Amount Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </td>
                            </thead>
                            <tbody>
                                <?php foreach($class_registrations as $reg): 
                                    $balance = ($reg['class_fee'] ?? 1000) - ($reg['payment_amount'] ?? 0);
                                ?>
                                <tr>
                                    <td><?php echo $reg['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($reg['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($reg['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['email'] ?? 'N/A'); ?></td>
                                    <td>KES <?php echo number_format($reg['class_fee'] ?? 1000, 2); ?></td>
                                    <td>KES <?php echo number_format($reg['payment_amount'] ?? 0, 2); ?></td>
                                    <td class="<?php echo $balance > 0 ? 'text-warning' : 'text-success'; ?>">KES <?php echo number_format($balance, 2); ?></td>
                                    <td>
                                        <?php if(($reg['certificate_issued'] ?? 0) == 1): ?>
                                            <span class="status-badge status-graduated">🎓 Graduated</span>
                                        <?php else: ?>
                                            <span class="status-badge status-<?php echo $reg['payment_status'] ?? 'pending'; ?>"><?php echo ucfirst($reg['payment_status'] ?? 'Pending'); ?></span>
                                        <?php endif; ?>
                                     </span></td>
                                    <td>
                                        <?php if(($reg['certificate_issued'] ?? 0) != 1): ?>
                                            <button class="btn btn-sm btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $reg['id']; ?>">
                                                <i class="fas fa-money-bill"></i> Payment
                                            </button>
                                            <button class="btn btn-sm btn-success btn-action" onclick="issueCertificate(<?php echo $reg['id']; ?>, '<?php echo htmlspecialchars($reg['full_name']); ?>', '<?php echo htmlspecialchars($reg['class_name']); ?>')">
                                                <i class="fas fa-certificate"></i> Certificate
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary btn-action" disabled>Certified</button>
                                        <?php endif; ?>
                                     </span></td>
                                </tr>
                                
                                <!-- Payment Update Modal -->
                                <div class="modal fade" id="paymentModal<?php echo $reg['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header bg-warning text-dark"><h5 class="modal-title">Update Payment - <?php echo htmlspecialchars($reg['full_name']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body"><input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>"><input type="hidden" name="class_id" value="<?php echo $reg['class_id']; ?>">
                                    <div class="mb-3"><label>Class Name</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($reg['class_name']); ?>" disabled></div>
                                    <div class="mb-3"><label>Total Course Fee</label><input type="text" class="form-control" value="KES <?php echo number_format($reg['class_fee'] ?? 1000, 2); ?>" disabled></div>
                                    <div class="mb-3"><label>Amount Already Paid</label><input type="text" class="form-control" value="KES <?php echo number_format($reg['payment_amount'] ?? 0, 2); ?>" disabled></div>
                                    <div class="mb-3"><label>Remaining Balance</label><input type="text" class="form-control" value="KES <?php echo number_format($balance, 2); ?>" disabled></div>
                                    <div class="mb-3"><label>Additional Payment (KES)</label><input type="number" name="payment_amount" class="form-control" step="100" min="100" required><small class="text-muted">Enter amount to add to this student's payment</small></div>
                                    <div class="alert alert-info">After payment, the balance will be updated automatically.</div></div>
                                    <div class="modal-footer"><button type="submit" name="update_payment" class="btn btn-warning">Update Payment</button></div></form></div></div></div>
                                <?php endforeach; ?>
                                <?php if(empty($class_registrations)): ?>
                                <tr><td colspan="10" class="text-center py-4">No students enrolled yet.</span></td>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- TAB 4: GRADUATES -->
                <div class="tab-pane fade" id="graduatesManage">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Certificate #</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Completion Date</th>
                                    <th>Issue Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($graduates as $grad): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grad['certificate_number'] ?? 'N/A'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($grad['full_name'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($grad['class_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo isset($grad['completion_date']) ? date('M d, Y', strtotime($grad['completion_date'])) : date('M d, Y', strtotime($grad['created_at'])); ?></td>
                                    <td><?php echo isset($grad['issue_date']) ? date('M d, Y', strtotime($grad['issue_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success btn-action" onclick="notifyCertificateReady('<?php echo htmlspecialchars($grad['full_name'] ?? 'N/A'); ?>', '<?php echo htmlspecialchars($grad['class_name'] ?? 'N/A'); ?>', '<?php echo htmlspecialchars($grad['certificate_number'] ?? 'N/A'); ?>')">
                                            <i class="fas fa-envelope me-1"></i> Notify Student
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($graduates)): ?>
                                <tr><td colspan="6" class="text-center py-4">No graduates yet. Issue certificates to students.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Class Modal -->
        <div class="modal fade" id="addClassModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Class</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="mb-3"><label>Class Name *</label><input type="text" name="class_name" class="form-control" required></div>
            <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
            <div class="row"><div class="col-md-6 mb-3"><label>Start Date *</label><input type="date" name="start_date" class="form-control" required></div><div class="col-md-6 mb-3"><label>Duration (Weeks)</label><input type="number" name="duration_weeks" class="form-control" value="8"></div></div>
            <div class="mb-3"><label>Course Fee (KES)</label><input type="number" name="fee_amount" class="form-control" value="1000" min="200"></div></div>
            <div class="modal-footer"><button type="submit" name="add_class" class="btn btn-success">Add Class</button></div></form></div></div></div>

        <!-- Edit Active Class Modal -->
        <div class="modal fade" id="editClassModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header bg-warning text-dark"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Class</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="hidden" name="class_id" id="edit_class_id">
            <div class="mb-3"><label>Class Name *</label><input type="text" name="class_name" id="edit_class_name" class="form-control" required></div>
            <div class="mb-3"><label>Description</label><textarea name="description" id="edit_description" class="form-control" rows="3"></textarea></div>
            <div class="row"><div class="col-md-6 mb-3"><label>Start Date *</label><input type="date" name="start_date" id="edit_start_date" class="form-control" required></div><div class="col-md-6 mb-3"><label>Duration (Weeks)</label><input type="number" name="duration_weeks" id="edit_duration_weeks" class="form-control"></div></div>
            <div class="mb-3"><label>Course Fee (KES)</label><input type="number" name="fee_amount" id="edit_fee_amount" class="form-control" min="200"></div>
            <div class="alert alert-info mt-2"><i class="fas fa-info-circle me-2"></i>After editing, click "Update Class" to save changes. The changes will appear on the user side immediately.</div></div>
            <div class="modal-footer"><button type="submit" name="edit_class" class="btn btn-warning">Update Class</button></div></form></div></div></div>

        <!-- Edit & Restore Trashed Class Modal -->
        <div class="modal fade" id="editTrashedClassModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit & Restore Class</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="hidden" name="class_id" id="trash_class_id">
            <div class="mb-3"><label>Class Name *</label><input type="text" name="class_name" id="trash_class_name" class="form-control" required></div>
            <div class="mb-3"><label>Description</label><textarea name="description" id="trash_description" class="form-control" rows="3"></textarea></div>
            <div class="row"><div class="col-md-6 mb-3"><label>Start Date *</label><input type="date" name="start_date" id="trash_start_date" class="form-control" required></div><div class="col-md-6 mb-3"><label>Duration (Weeks)</label><input type="number" name="duration_weeks" id="trash_duration_weeks" class="form-control"></div></div>
            <div class="mb-3"><label>Course Fee (KES)</label><input type="number" name="fee_amount" id="trash_fee_amount" class="form-control" min="200"></div>
            <div class="alert alert-success mt-2"><i class="fas fa-info-circle me-2"></i>After editing, click "Restore & Add as New Class" to restore this class and add it to active classes.</div></div>
            <div class="modal-footer"><button type="submit" name="restore_and_add_class" class="btn btn-success">Restore & Add as New Class</button></div></form></div></div></div>

        <!-- Issue Certificate Modal -->
        <div class="modal fade" id="certificateModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-certificate me-2"></i>Issue Certificate</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Student Name</label>
                            <input type="text" id="certStudentName" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course Name</label>
                            <input type="text" id="certCourseName" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Completion Date</label>
                            <input type="date" id="completionDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> A unique certificate number will be generated automatically.
                        </div>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> Once issued, the student will be notified to collect their certificate at the office.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="confirmIssueCertificate()">
                            <i class="fas fa-certificate me-2"></i> Issue Certificate & Notify Student
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Modal - Student Certificate Ready -->
        <div class="modal fade" id="notificationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Certificate Ready - Notify Student</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle fa-2x d-block mb-2"></i>
                            <h5 class="fw-bold">Certificate Ready for Collection!</h5>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Student Name</label>
                            <p class="form-control-plaintext" id="notifStudentName"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Course</label>
                            <p class="form-control-plaintext" id="notifCourseName"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Certificate Number</label>
                            <p class="form-control-plaintext" id="notifCertNumber"></p>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Notification will be sent to:</strong><br>
                            <span id="notifStudentContact">Student's email and phone on record</span>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Message:</strong><br>
                            <em>"Dear <span id="notifMsgName"></span>, Your certificate for <span id="notifMsgCourse"></span> (Certificate #<span id="notifMsgCert"></span>) is now ready. Please visit the Makueni County Innovation Hub office to collect it. Thank you!"</em>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="sendNotification()">
                            <i class="fas fa-paper-plane me-2"></i> Send Notification
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        let currentRegistrationId = null;
        let currentTrashClassId = null;
        let currentNotifData = null;

        function editClass(id, name, description, startDate, durationWeeks, feeAmount) {
            document.getElementById('edit_class_id').value = id;
            document.getElementById('edit_class_name').value = name;
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit_start_date').value = startDate;
            document.getElementById('edit_duration_weeks').value = durationWeeks;
            document.getElementById('edit_fee_amount').value = feeAmount;
            new bootstrap.Modal(document.getElementById('editClassModal')).show();
        }

        function editTrashedClass(id, name, description, startDate, durationWeeks, feeAmount) {
            document.getElementById('trash_class_id').value = id;
            document.getElementById('trash_class_name').value = name;
            document.getElementById('trash_description').value = description || '';
            document.getElementById('trash_start_date').value = startDate;
            document.getElementById('trash_duration_weeks').value = durationWeeks;
            document.getElementById('trash_fee_amount').value = feeAmount;
            new bootstrap.Modal(document.getElementById('editTrashedClassModal')).show();
        }

        function restoreClass(id) {
            if(confirm('Restore this class to active list?')) {
                window.location.href = '?view=classes&action=restore_class&id=' + id;
            }
        }

        function issueCertificate(id, name, course) {
            currentRegistrationId = id;
            document.getElementById('certStudentName').value = name;
            document.getElementById('certCourseName').value = course;
            new bootstrap.Modal(document.getElementById('certificateModal')).show();
        }

        function confirmIssueCertificate() {
            const completionDate = document.getElementById('completionDate').value;
            fetch('dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=issue_certificate&registration_id=' + currentRegistrationId + '&completion_date=' + completionDate
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('certificateModal')).hide();
                    showNotificationModal(
                        data.student_name,
                        data.course_name,
                        data.certificate_number
                    );
                } else {
                    alert(' Error: ' + data.message);
                }
            })
            .catch(error => alert(' Error: ' + error));
        }

        function showNotificationModal(name, course, certNumber) {
            currentNotifData = {
                name: name,
                course: course,
                certNumber: certNumber
            };
            document.getElementById('notifStudentName').textContent = name;
            document.getElementById('notifCourseName').textContent = course;
            document.getElementById('notifCertNumber').textContent = certNumber;
            document.getElementById('notifMsgName').textContent = name;
            document.getElementById('notifMsgCourse').textContent = course;
            document.getElementById('notifMsgCert').textContent = certNumber;
            new bootstrap.Modal(document.getElementById('notificationModal')).show();
        }

        function sendNotification() {
            alert('Notification sent successfully!\n\n' + 
                  'Student: ' + currentNotifData.name + '\n' +
                  'Course: ' + currentNotifData.course + '\n' +
                  'Certificate: ' + currentNotifData.certNumber + '\n\n' +
                  'The student has been notified to collect their certificate at the office.');
            
            bootstrap.Modal.getInstance(document.getElementById('notificationModal')).hide();
            location.reload();
        }

        function notifyCertificateReady(name, course, certNumber) {
            showNotificationModal(name, course, certNumber);
        }
        </script>
        <?php endif; ?>

        <!-- ==================== AJIRY MANAGEMENT ==================== -->
        <?php if($current_view == 'ajiry'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back</button>
            <h3 class="section-title"><i class="fas fa-microphone-alt me-2"></i>AJIRY Studio Bookings</h3>
            <div class="summary-stats"><div class="row text-center"><div class="col-md-4"><h5>Total</h5><h3><?php echo $total_ajiry_bookings; ?></h3></div><div class="col-md-4"><h5>Pending</h5><h3><?php echo $pending_ajiry; ?></h3></div><div class="col-md-4"><h5>Confirmed</h5><h3><?php echo $confirmed_ajiry; ?></h3></div></div></div>
            <table class="table-custom"><thead><tr><th>ID</th><th>Booking Code</th><th>Customer</th><th>Services</th><th>Preferred Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody><?php foreach($ajiry_bookings as $booking): ?><tr><td><?php echo $booking['id']; ?></td><td><strong><?php echo htmlspecialchars($booking['booking_code']); ?></strong></td><td><?php echo htmlspecialchars($booking['full_name']); ?><br><small><?php echo htmlspecialchars($booking['email']); ?></small></td><td><?php echo htmlspecialchars(substr($booking['services'], 0, 30)); ?>...</td><td><?php echo date('M d, Y', strtotime($booking['preferred_date'])); ?></td><td><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td><td><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateAjiryModal<?php echo $booking['id']; ?>">Update</button> <a href="?view=ajiry&action=delete_ajiry_booking&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger">Delete</a></td></tr>
            <div class="modal fade" id="updateAjiryModal<?php echo $booking['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header bg-primary text-white"><h5 class="modal-title">Update AJIRY Booking</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>"><div class="mb-3"><label>Status</label><select name="status" class="form-select"><option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option><option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option><option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option><option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option></select></div></div><div class="modal-footer"><button type="submit" name="update_ajiry_status" class="btn btn-primary">Update</button></div></form></div></div></div>
            <?php endforeach; ?></tbody></table>
        </div>
        <?php endif; ?>

        <!-- ==================== USER MANAGEMENT ==================== -->
        <?php if($current_view == 'users'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back</button>
            <h3 class="section-title"><i class="fas fa-users me-2"></i>Manage Users</h3>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($all_users as $user): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                            <td><span class="status-badge <?php echo $user['role'] == 'admin' ? 'status-confirmed' : 'status-pending'; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php if($user['role'] == 'user'): ?>
                                        <a href="?view=users&action=make_admin&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary btn-action">Make Admin</a>
                                    <?php elseif($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?view=users&action=remove_admin&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning btn-action">Remove Admin</a>
                                    <?php endif; ?>
                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?view=users&action=delete_user&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Delete this user?')">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==================== FEEDBACK MANAGEMENT ==================== -->
        <?php if($current_view == 'feedback'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</button>
            
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="section-title"><i class="fas fa-comment me-2"></i>User Feedback</h3>
                <div>
                    <span class="badge bg-warning text-dark me-2">Pending: <?php echo $pending_feedback; ?></span>
                    <span class="badge bg-success">Approved: <?php echo $total_feedback - $pending_feedback; ?></span>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Message</th>
                            <th>Rating</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_feedback as $fb): ?>
                        <tr>
                            <td><?php echo $fb['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($fb['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($fb['phone']); ?></td>
                            <td style="max-width: 200px;">
                                <?php echo htmlspecialchars(substr($fb['message'], 0, 60)); ?>
                                <?php if(strlen($fb['message']) > 60): ?>...<?php endif; ?>
                                <button class="btn btn-sm btn-link p-0" data-bs-toggle="modal" data-bs-target="#viewFeedbackModal<?php echo $fb['id']; ?>">
                                    <i class="fas fa-eye text-primary"></i>
                                </button>
                            </td>
                            <td>
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <?php if($i <= $fb['rating']): ?>
                                        <i class="fas fa-star text-warning" style="font-size: 14px;"></i>
                                    <?php else: ?>
                                        <i class="far fa-star text-secondary" style="font-size: 14px;"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="text-muted small">(<?php echo $fb['rating']; ?>)</span>
                            </td>
                            <td><small><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></small></td>
                            <td>
                                <?php if($fb['status'] == 'approved'): ?>
                                    <span class="status-badge status-confirmed"> Approved</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php if($fb['status'] == 'pending'): ?>
                                        <a href="?view=feedback&action=approve_feedback&id=<?php echo $fb['id']; ?>" 
                                           class="btn btn-sm btn-success btn-action" 
                                           onclick="return confirm('Approve this feedback?')">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-info btn-action" data-bs-toggle="modal" data-bs-target="#viewFeedbackModal<?php echo $fb['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <a href="?view=feedback&action=delete_feedback&id=<?php echo $fb['id']; ?>" 
                                       class="btn btn-sm btn-danger btn-action" 
                                       onclick="return confirm('Delete this feedback? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- View Feedback Modal -->
                        <div class="modal fade" id="viewFeedbackModal<?php echo $fb['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header" style="background: linear-gradient(135deg, #1a5c3a, #0a3d2a); color: white;">
                                        <h5 class="modal-title"><i class="fas fa-comment me-2"></i>Feedback Details</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($fb['full_name']); ?></p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($fb['phone']); ?></p>
                                                <p><strong>ID Number:</strong> <?php echo htmlspecialchars($fb['id_number'] ?? 'N/A'); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Rating:</strong> 
                                                    <?php for($i=1; $i<=5; $i++): ?>
                                                        <?php if($i <= $fb['rating']): ?>
                                                            <i class="fas fa-star text-warning"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star text-secondary"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                    <span class="text-muted">(<?php echo $fb['rating']; ?>/5)</span>
                                                </p>
                                                <p><strong>Status:</strong> 
                                                    <?php if($fb['status'] == 'approved'): ?>
                                                        <span class="badge bg-success">Approved</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Submitted:</strong> <?php echo date('F d, Y h:i A', strtotime($fb['created_at'])); ?></p>
                                            </div>
                                        </div>
                                        <hr>
                                        <h6><strong>Feedback Message:</strong></h6>
                                        <div class="p-3 bg-light rounded" style="white-space: pre-wrap; max-height: 200px; overflow-y: auto;">
                                            <?php echo nl2br(htmlspecialchars($fb['message'])); ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <?php if($fb['status'] == 'pending'): ?>
                                            <a href="?view=feedback&action=approve_feedback&id=<?php echo $fb['id']; ?>" class="btn btn-success">
                                                <i class="fas fa-check me-1"></i> Approve
                                            </a>
                                        <?php endif; ?>
                                        <a href="?view=feedback&action=delete_feedback&id=<?php echo $fb['id']; ?>" class="btn btn-danger" 
                                           onclick="return confirm('Delete this feedback?')">
                                            <i class="fas fa-trash me-1"></i> Delete
                                        </a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($all_feedback)): ?>
                        <tr><td colspan="8" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x d-block text-muted mb-2"></i>
                            No feedback submissions yet.
                        </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>


    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Add New Event</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Event Title *</label><input type="text" name="title" class="form-control" required></div>
                        <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                        <div class="row"><div class="col-md-4 mb-3"><label>Event Date *</label><input type="date" name="start_date" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label>Start Time *</label><input type="time" name="start_time" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label>End Time *</label><input type="time" name="end_time" class="form-control" required></div></div>
                        <div class="row"><div class="col-md-6 mb-3"><label>Venue</label><input type="text" name="venue" class="form-control" value="Greenpark, Makueni"></div>
                        <div class="col-md-6 mb-3"><label>Max Participants</label><input type="number" name="max_participants" class="form-control" value="100"></div></div>
                        <div class="mb-3"><label>Organizer Name</label><input type="text" name="organizer_name" class="form-control"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="add_event" class="btn btn-success">Add Event</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>