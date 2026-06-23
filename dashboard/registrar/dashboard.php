<?php
require_once __DIR__ . '/../../init.php';

$auth->requireRole('registrar');

// Get registrar statistics
$stats_query = "
SELECT 
    (SELECT COUNT(*) FROM students WHERE status = 'new') as new_students,
    (SELECT COUNT(*) FROM documents WHERE status = 'pending') as pending_documents,
    (SELECT COUNT(*) FROM enrollments WHERE enrollment_status = 'pending') as pending_enrollments,
    (SELECT COUNT(*) FROM additional_fees) as total_additional_fees
";

$result = $db->query($stats_query);
$stats = $result->fetch_assoc();

// Get pending documents
$docs_query = "
SELECT d.*, s.first_name, s.last_name, s.id as student_id
FROM documents d
JOIN students s ON d.student_id = s.id
WHERE d.status = 'pending'
ORDER BY d.upload_date ASC
LIMIT 10
";
$pending_docs = $db->query($docs_query)->fetch_all(MYSQLI_ASSOC);

// Get new students waiting for enrollment
$students_query = "
SELECT s.*, 
       (SELECT COUNT(*) FROM documents WHERE student_id = s.id AND status = 'verified') as verified_docs
FROM students s
WHERE s.status = 'new'
ORDER BY s.created_at DESC
LIMIT 10
";
$new_students = $db->query($students_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard - School Enrollment System</title>
    <link rel="stylesheet" href="<?php echo appUrl('/public/styles.css'); ?>">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">📚</div>
            </div>

            <nav class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/index.php'); ?>" class="nav-link active">
                        <span class="nav-icon">📋</span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/registrar/new-student.php'); ?>" class="nav-link">
                        <span class="nav-icon">➕</span>
                        New Student
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/registrar/students.php'); ?>" class="nav-link">
                        <span class="nav-icon">🎓</span>
                        Students
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/registrar/documents.php'); ?>" class="nav-link">
                        <span class="nav-icon">📄</span>
                        Documents
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/registrar/additional-fees.php'); ?>" class="nav-link">
                        <span class="nav-icon">💰</span>
                        Additional Fees
                    </a>
                </li>
            </nav>

            <div class="sidebar-footer">
                <a href="<?php echo appUrl('/logout.php'); ?>" class="nav-link">
                    <span class="nav-icon">🚪</span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-content">
                    <h1 class="header-title">Registrar Dashboard</h1>
                    <div class="user-menu">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                        <div>
                            <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong><br>
                            <small style="color: #6b7280;">Registrar Personnel</small>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">New Students</div>
                        <div class="stat-value"><?php echo $stats['new_students'] ?? 0; ?></div>
                        <div class="stat-change positive">Requiring profiles</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #f59e0b;">
                        <div class="stat-label">Pending Documents</div>
                        <div class="stat-value"><?php echo $stats['pending_documents'] ?? 0; ?></div>
                        <div class="stat-change">Awaiting verification</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #3b82f6;">
                        <div class="stat-label">Pending Enrollments</div>
                        <div class="stat-value"><?php echo $stats['pending_enrollments'] ?? 0; ?></div>
                        <div class="stat-change">Under review</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #10b981;">
                        <div class="stat-label">Additional Fees Applied</div>
                        <div class="stat-value"><?php echo $stats['total_additional_fees'] ?? 0; ?></div>
                        <div class="stat-change">Active fees</div>
                    </div>
                </div>

                <!-- Pending Documents -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pending Documents</h3>
                        <a href="<?php echo appUrl('/dashboard/registrar/documents.php'); ?>" class="btn btn-primary btn-sm">View All</a>
                    </div>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Document Type</th>
                                    <th>Uploaded</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_docs as $doc): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                    <td><?php echo formatDate($doc['upload_date']); ?></td>
                                    <td>
                                        <span class="badge badge-warning">Pending</span>
                                    </td>
                                    <td>
                                        <a href="<?php echo appUrl('/dashboard/registrar/verify-document.php?doc_id=' . $doc['id']); ?>" class="btn btn-primary btn-sm">Review</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- New Students -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">New Students for Profile Creation</h3>
                        <a href="<?php echo appUrl('/dashboard/registrar/new-student.php'); ?>" class="btn btn-primary btn-sm">Create Profile</a>
                    </div>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Date of Birth</th>
                                    <th>Email</th>
                                    <th>Verified Docs</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($new_students as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong></td>
                                    <td><?php echo formatDate($student['date_of_birth']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-success"><?php echo $student['verified_docs']; ?>/3</span>
                                    </td>
                                    <td><?php echo formatDate($student['created_at']); ?></td>
                                    <td>
                                        <a href="<?php echo appUrl('/dashboard/registrar/edit-student.php?id=' . $student['id']); ?>" class="btn btn-primary btn-sm">Edit Profile</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
