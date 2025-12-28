<?php
session_start();
header('Content-Type: application/json');

define('SECURE_ACCESS', true);

require_once 'config/config.php';

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$appointmentDate = trim($_POST['appointmentDate'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];

if (empty($firstName)) {
    $errors[] = 'First name is required.';
}

if (empty($lastName)) {
    $errors[] = 'Last name is required.';
}

if (empty($email)) {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if (empty($phone)) {
    $errors[] = 'Phone number is required.';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
} elseif (strlen($message) > 1800) {
    $errors[] = 'Message is too long (maximum 1800 characters).';
}

if (!empty($errors)) {
    $response['message'] = implode(' ', $errors);
    echo json_encode($response);
    exit();
}

$firstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
$lastName = htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

$appointmentDateFormatted = null;
if (!empty($appointmentDate)) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $appointmentDate);
    if ($dateObj && $dateObj->format('Y-m-d') === $appointmentDate) {
        $appointmentDateFormatted = $appointmentDate;
    }
}

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$stmt = $conn->prepare("
    INSERT INTO inquiries 
    (first_name, last_name, email, phone, appointment_date, message, ip_address, user_agent, status, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())
");

if (!$stmt) {
    $response['message'] = 'Database error. Please try again later.';
    echo json_encode($response);
    exit();
}

$stmt->bind_param(
    "ssssssss",
    $firstName,
    $lastName,
    $email,
    $phone,
    $appointmentDateFormatted,
    $message,
    $ipAddress,
    $userAgent
);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Thank you for contacting us! We will get back to you soon.';
    $response['inquiry_id'] = $stmt->insert_id;
    
} else {
    $response['message'] = 'Failed to submit inquiry. Please try again.';
}

$stmt->close();
$conn->close();

echo json_encode($response);
exit();

function sendAdminNotification($firstName, $lastName, $email, $message) {
    $to = "admin@imargroup.com";
    $subject = "New Inquiry from $firstName $lastName";
    $body = "New inquiry received:\n\n";
    $body .= "Name: $firstName $lastName\n";
    $body .= "Email: $email\n";
    $body .= "Message: $message\n";
    $headers = "From: noreply@imargroup.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    
    mail($to, $subject, $body, $headers);
}
?>