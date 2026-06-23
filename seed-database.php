<?php
/**
 * Database Seeding Script
 * Run this AFTER importing the SQL schema to populate demo data
 * Access: http://localhost/FBC/seed-database.php
 */

require_once __DIR__ . '/config/Database.php';

$db = new Database();
$connection = $db->connect();

if (!$connection) {
    die('Database connection failed: ' . $db->getError());
}

echo "Starting database seeding...\n\n";

// 1. Insert Demo Users
echo "1. Creating demo users...\n";

$demo_users = [
    [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_BCRYPT),
        'email' => 'admin@school.com',
        'full_name' => 'System Administrator',
        'role' => 'admin'
    ],
    [
        'username' => 'cashier',
        'password' => password_hash('cashier123', PASSWORD_BCRYPT),
        'email' => 'cashier@school.com',
        'full_name' => 'Maria Santos',
        'role' => 'cashier'
    ],
    [
        'username' => 'registrar',
        'password' => password_hash('registrar123', PASSWORD_BCRYPT),
        'email' => 'registrar@school.com',
        'full_name' => 'Juan Dela Cruz',
        'role' => 'registrar'
    ],
    [
        'username' => 'assessment',
        'password' => password_hash('assessment123', PASSWORD_BCRYPT),
        'email' => 'assessment@school.com',
        'full_name' => 'Rosa Garcia',
        'role' => 'assessment'
    ]
];

foreach ($demo_users as $user) {
    $query = "INSERT INTO users (username, password, email, full_name, role, status) 
              VALUES (?, ?, ?, ?, ?, 'active')
              ON DUPLICATE KEY UPDATE password = VALUES(password), 
                                      email = VALUES(email), 
                                      full_name = VALUES(full_name),
                                      role = VALUES(role),
                                      status = 'active'";
    
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        echo "Prepare failed: " . $connection->error . "\n";
        exit();
    }
    
    $stmt->bind_param(
        "sssss",
        $user['username'],
        $user['password'],
        $user['email'],
        $user['full_name'],
        $user['role']
    );
    
    if ($stmt->execute()) {
        echo "✓ Created user: {$user['username']} ({$user['role']})\n";
    } else {
        echo "✗ Failed to create user: {$user['username']} - " . $stmt->error . "\n";
    }
}

// 2. Insert Sample Students (for demo purposes)
echo "\n2. Creating sample students...\n";

$sample_students = [
    ['Juan', 'Reyes', 'Miguel', '2008-05-15', 'Male', '09171234567', 'juan.reyes@email.com', '123 Main St'],
    ['Maria', 'Santos', 'Dela', '2008-08-22', 'Female', '09181234567', 'maria.santos@email.com', '456 Oak Ave'],
    ['Carlos', 'Gonzales', 'Jose', '2009-03-10', 'Male', '09191234567', 'carlos.gonzales@email.com', '789 Pine Rd'],
    ['Anna', 'Lopez', 'Marie', '2009-07-18', 'Female', '09201234567', 'anna.lopez@email.com', '321 Elm St'],
    ['Roberto', 'Fernandez', 'Juan', '2010-01-25', 'Male', '09211234567', 'roberto.fern@email.com', '654 Maple Dr'],
];

foreach ($sample_students as $student) {
    list($first_name, $last_name, $middle_name, $dob, $gender, $phone, $email, $address) = $student;
    
    $query = "INSERT INTO students (first_name, last_name, middle_name, date_of_birth, gender, contact_number, email, address, status)
              SELECT ?, ?, ?, ?, ?, ?, ?, ?, 'new'
              WHERE NOT EXISTS (SELECT 1 FROM students WHERE email = ?)";
    
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        echo "Prepare failed: " . $connection->error . "\n";
        exit();
    }
    
    $stmt->bind_param(
        "sssssssss",
        $first_name,
        $last_name,
        $middle_name,
        $dob,
        $gender,
        $phone,
        $email,
        $address,
        $email
    );
    
    if ($stmt->execute()) {
        echo "✓ Created student: {$first_name} {$last_name}\n";
    } else {
        echo "✗ Failed to create student: {$first_name} {$last_name} - " . $stmt->error . "\n";
    }
}

// 3. Insert Scholarship Types
echo "\n3. Creating scholarship types...\n";

$scholarships = [
    ['Academic Excellence', 'Merit-based', 'For students with high academic performance', 25],
    ['Athlete Scholarship', 'Merit-based', 'For students in varsity sports', 20],
    ['Arts & Culture', 'Merit-based', 'For students active in arts and cultural programs', 15],
    ['Financial Aid', 'Need-based', 'For students with financial needs', 30],
    ['Indigenous Peoples (IP)', 'Partial', 'For indigenous student applicants', 35]
];

foreach ($scholarships as $scholarship) {
    list($name, $type, $description, $discount_percentage) = $scholarship;
    
    $query = "INSERT INTO scholarships (scholarship_name, scholarship_type, description, discount_percentage, status)
              SELECT ?, ?, ?, ?, 'active'
              WHERE NOT EXISTS (SELECT 1 FROM scholarships WHERE scholarship_name = ?)";
    
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        echo "Prepare failed: " . $connection->error . "\n";
        exit();
    }
    
    $stmt->bind_param(
        "sssds",
        $name,
        $type,
        $description,
        $discount_percentage,
        $name
    );
    
    if ($stmt->execute()) {
        echo "✓ Created scholarship: {$name}\n";
    } else {
        echo "✗ Failed to create scholarship: {$name} - " . $stmt->error . "\n";
    }
}

// 4. Create documents and demo enrollment with payments
echo "\n4. Setting up demo enrollment workflow...\n";

require_once __DIR__ . '/config/Helpers.php';
require_once __DIR__ . '/config/EnrollmentService.php';

$student_row = $connection->query("SELECT id FROM students ORDER BY id LIMIT 1")->fetch_assoc();
$cashier_row = $connection->query("SELECT id FROM users WHERE username = 'cashier' LIMIT 1")->fetch_assoc();

if ($student_row && $cashier_row) {
    $student_id = (int)$student_row['id'];
    $cashier_id = (int)$cashier_row['id'];

    $doc_types = ['Form 137', 'Form 138', 'Report Card'];
    foreach ($doc_types as $doc_type) {
        $doc_q = "INSERT INTO documents (student_id, document_type, status, verified_by, verified_at)
                  SELECT ?, ?, 'verified', (SELECT id FROM users WHERE username = 'registrar' LIMIT 1), NOW()
                  WHERE NOT EXISTS (SELECT 1 FROM documents WHERE student_id = ? AND document_type = ?)";
        $doc_stmt = $connection->prepare($doc_q);
        $doc_stmt->bind_param("isis", $student_id, $doc_type, $student_id, $doc_type);
        $doc_stmt->execute();
    }
    echo "✓ Documents verified for demo student\n";

    $year = date('Y') . '-' . (date('Y') + 1);
    $exists = $connection->prepare("SELECT id FROM enrollments WHERE student_id = ? AND academic_year = ? AND semester = 1");
    $exists->bind_param("is", $student_id, $year);
    $exists->execute();

    if ($exists->get_result()->num_rows === 0) {
        try {
            $connection->begin_transaction();
            $enrollment_id = createEnrollment($connection, $student_id, $year, 1, 7, 12000, $cashier_id);

            $prelim = $connection->prepare("SELECT id, amount_due FROM payment_schedules WHERE enrollment_id = ? AND payment_type = 'Prelim'");
            $prelim->bind_param("i", $enrollment_id);
            $prelim->execute();
            $schedule = $prelim->get_result()->fetch_assoc();

            if ($schedule) {
                recordStudentPayment($connection, (int)$schedule['id'], (float)$schedule['amount_due'], 'Cash', 'DEMO-001', 'Demo Prelim payment', $cashier_id);
            }

            $connection->commit();
            echo "✓ Demo enrollment created (Grade 7, 10 subjects, Prelim paid)\n";
        } catch (Exception $e) {
            $connection->rollback();
            echo "✗ Demo enrollment failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "• Demo enrollment already exists\n";
    }
}

// 5. Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "Database seeding completed successfully!\n";
echo str_repeat("=", 50) . "\n\n";

echo "DEMO LOGIN CREDENTIALS:\n\n";
echo "Admin Account:\n";
echo "  Username: admin\n";
echo "  Password: admin123\n\n";

echo "Cashier Account:\n";
echo "  Username: cashier\n";
echo "  Password: cashier123\n\n";

echo "Registrar Account:\n";
echo "  Username: registrar\n";
echo "  Password: registrar123\n\n";

echo "Assessment Account:\n";
echo "  Username: assessment\n";
echo "  Password: assessment123\n\n";

echo "You can now login at: http://localhost/FBC/login.php\n";

$connection->close();
?>
