<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('cashier');

$student_id = (int)($_GET['id'] ?? 0);
$message = '';
$error = '';

$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    redirect('/dashboard/cashier/students.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'record_payment') {
    $schedule_id = (int)($_POST['schedule_id'] ?? 0);
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $payment_method = cleanInput($_POST['payment_method'] ?? '');
    $reference_number = cleanInput($_POST['reference_number'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');

    if ($schedule_id <= 0 || $amount_paid <= 0 || empty($payment_method)) {
        $error = 'Please select a payment period and enter a valid amount.';
    } else {
        try {
            $db->begin_transaction();
            $result = recordStudentPayment($db, $schedule_id, $amount_paid, $payment_method, $reference_number, $notes, $_SESSION['user_id']);
            $db->commit();
            $message = formatCurrency($amount_paid) . ' recorded for ' . $result['payment_type'] . '. Balance: ' . formatCurrency($result['balance']) . '. Status: ' . ucfirst($result['new_status']) . '.';
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}

$docs_stmt = $db->prepare("SELECT * FROM documents WHERE student_id = ? ORDER BY document_type");
$docs_stmt->bind_param("i", $student_id);
$docs_stmt->execute();
$documents = $docs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$doc_progress = getDocumentProgress($db, $student_id);

$enroll_stmt = $db->prepare("SELECT * FROM enrollments WHERE student_id = ? ORDER BY created_at DESC");
$enroll_stmt->bind_param("i", $student_id);
$enroll_stmt->execute();
$enrollment_list = $enroll_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$scholarship_stmt = $db->prepare("
    SELECT ss.*, sh.scholarship_name, sh.scholarship_type
    FROM student_scholarships ss
    JOIN scholarships sh ON ss.scholarship_id = sh.id
    WHERE ss.student_id = ?
");
$scholarship_stmt->bind_param("i", $student_id);
$scholarship_stmt->execute();
$scholarships = $scholarship_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$fees_stmt = $db->prepare("
    SELECT af.*, e.academic_year, e.semester
    FROM additional_fees af
    JOIN enrollments e ON af.enrollment_id = e.id
    WHERE e.student_id = ?
    ORDER BY af.created_at DESC
");
$fees_stmt->bind_param("i", $student_id);
$fees_stmt->execute();
$additional_fees = $fees_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

renderPageStart('Student Registration', 'cashier', 'students', 'Cashier');
renderAlerts($message, $error);
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Registration — <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="<?php echo appUrl('/dashboard/cashier/students.php'); ?>" class="btn btn-secondary btn-sm">Back</a>
            <?php if (in_array($student['status'], ['new', 'continuing'], true)): ?>
            <a href="<?php echo appUrl('/dashboard/cashier/enroll-student.php?id=' . $student_id); ?>" class="btn btn-primary btn-sm">Enroll / Set Grade</a>
            <?php endif; ?>
            <?php if (count($enrollment_list) > 0): ?>
            <button type="button" class="btn btn-success btn-sm" onclick="openModal('paymentModal')">Add a Payment</button>
            <a href="<?php echo appUrl('/dashboard/cashier/receive-payment.php'); ?>" class="btn btn-success btn-sm">Receive Payment</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="profile-grid">
        <div><strong>Date of Birth:</strong> <?php echo formatDate($student['date_of_birth']); ?></div>
        <div><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></div>
        <div><strong>Contact:</strong> <?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?></div>
        <div><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></div>
        <div><strong>Address:</strong> <?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></div>
        <div><strong>Status:</strong> <span class="badge badge-purple"><?php echo ucfirst($student['status']); ?></span></div>
        <div><strong>Documents:</strong> <span class="badge badge-success"><?php echo (int)$doc_progress['verified']; ?>/<?php echo (int)$doc_progress['total']; ?> verified</span></div>
    </div>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header"><h3 class="card-title">Required Documents (Form 137, Form 138, Report Card)</h3></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Document</th><th>Status</th><th>Uploaded</th></tr></thead>
            <tbody>
                <?php foreach ($documents as $d): ?>
                <tr>
                    <td><?php echo htmlspecialchars($d['document_type']); ?></td>
                    <td><span class="badge <?php echo $d['status'] === 'verified' ? 'badge-success' : 'badge-warning'; ?>"><?php echo ucfirst($d['status']); ?></span></td>
                    <td><?php echo formatDate($d['upload_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (count($enrollment_list) === 0): ?>
<div class="card" style="margin-top:24px;">
    <div class="card-header"><h3 class="card-title">Enrollment & Payments</h3></div>
    <p style="padding:24px;color:#6b7280;text-align:center;">Not enrolled yet. After the registrar completes requirements, enroll the student and set their grade level.</p>
</div>
<?php endif; ?>

<?php foreach ($enrollment_list as $enrollment):
    $schedules = getEnrollmentPaymentSchedules($db, $enrollment['id']);
    $subjects = getEnrollmentSubjects($db, $enrollment['id']);
    $total_due = array_sum(array_column($schedules, 'amount_due'));
    $total_paid = array_sum(array_column($schedules, 'amount_paid'));
    $balance = max(0, $total_due - $total_paid);
?>
<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3 class="card-title"><?php echo getGradeLevelName($enrollment['grade_level']); ?> — <?php echo htmlspecialchars($enrollment['academic_year']); ?> Sem <?php echo $enrollment['semester']; ?></h3>
        <span class="badge" style="background-color:<?php echo getEnrollmentStatusColor($enrollment['enrollment_status']); ?>20;color:<?php echo getEnrollmentStatusColor($enrollment['enrollment_status']); ?>;"><?php echo ucfirst($enrollment['enrollment_status']); ?></span>
    </div>

    <div class="summary-grid">
        <div class="summary-box"><span>Tuition</span><strong><?php echo formatCurrency($enrollment['total_tuition']); ?></strong></div>
        <div class="summary-box"><span>Additional Fees</span><strong><?php echo formatCurrency($enrollment['additional_fees']); ?></strong></div>
        <div class="summary-box"><span>Scholarship</span><strong>-<?php echo formatCurrency($enrollment['scholarship_amount']); ?></strong></div>
        <div class="summary-box"><span>Net Amount</span><strong><?php echo formatCurrency($enrollment['net_amount']); ?></strong></div>
        <div class="summary-box"><span>Total Paid</span><strong style="color:#10b981;"><?php echo formatCurrency($total_paid); ?></strong></div>
        <div class="summary-box"><span>Balance</span><strong style="color:#ef4444;"><?php echo formatCurrency($balance); ?></strong></div>
    </div>

    <h4 style="padding:16px 24px 0;margin:0;color:#374151;">Payment Breakdown (4 installments per semester)</h4>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Amount Due</th>
                    <th>Amount Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Exam Eligible</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $sch):
                    $sch_balance = max(0, $sch['amount_due'] - $sch['amount_paid']);
                    $exam_ok = getExamEligibilityByPayment($db, $enrollment['id'], $sch['payment_type']);
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($sch['payment_type']); ?></strong></td>
                    <td><?php echo formatCurrency($sch['amount_due']); ?></td>
                    <td><?php echo formatCurrency($sch['amount_paid']); ?></td>
                    <td><?php echo formatCurrency($sch_balance); ?></td>
                    <td>
                        <span class="badge" style="background-color:<?php echo getPaymentStatusColor($sch['payment_status']); ?>20;color:<?php echo getPaymentStatusColor($sch['payment_status']); ?>;">
                            <?php echo ucfirst($sch['payment_status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($exam_ok): ?>
                        <span class="badge badge-success">Yes</span>
                        <?php else: ?>
                        <span class="badge badge-warning">No</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $sch['due_date'] ? formatDate($sch['due_date']) : '—'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (count($subjects) > 0): ?>
    <h4 style="padding:16px 24px 0;margin:0;color:#374151;">Subject Schedule (Philippine Curriculum — Grade <?php echo $enrollment['grade_level']; ?>)</h4>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Code</th><th>Subject</th></tr></thead>
            <tbody>
                <?php foreach ($subjects as $sub): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($sub['subject_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (count($scholarships) > 0): ?>
<div class="card" style="margin-top:24px;">
    <div class="card-header"><h3 class="card-title">Scholarships</h3></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Program</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($scholarships as $sch): ?>
                <tr>
                    <td><?php echo htmlspecialchars($sch['scholarship_name']); ?></td>
                    <td><?php echo htmlspecialchars($sch['scholarship_type']); ?></td>
                    <td><?php echo formatCurrency($sch['approved_amount']); ?></td>
                    <td><span class="badge badge-purple"><?php echo ucfirst($sch['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (count($additional_fees) > 0): ?>
<div class="card" style="margin-top:24px;">
    <div class="card-header"><h3 class="card-title">Additional Fees Applied</h3></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Description</th><th>Amount</th><th>Year/Sem</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($additional_fees as $fee): ?>
                <tr>
                    <td><?php echo htmlspecialchars($fee['fee_description']); ?></td>
                    <td><?php echo formatCurrency($fee['fee_amount']); ?></td>
                    <td><?php echo htmlspecialchars($fee['academic_year'] . ' Sem ' . $fee['semester']); ?></td>
                    <td><?php echo formatDate($fee['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (count($enrollment_list) > 0): ?>
<div class="modal-overlay" id="paymentModalOverlay" onclick="closeModal('paymentModal')">
    <div class="modal" id="paymentModal" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Add a Payment</h2>
            <button type="button" class="modal-close" onclick="closeModal('paymentModal')">✕</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="record_payment">
            <div class="form-group">
                <label for="schedule_id">Payment Period</label>
                <select id="schedule_id" name="schedule_id" required onchange="updateBalanceHint()">
                    <option value="">Select payment period</option>
                    <?php foreach ($enrollment_list as $enrollment):
                        $schedules = getEnrollmentPaymentSchedules($db, $enrollment['id']);
                        foreach ($schedules as $sch):
                            if ($sch['payment_status'] === 'paid') continue;
                            $bal = max(0, $sch['amount_due'] - $sch['amount_paid']);
                    ?>
                    <option value="<?php echo $sch['id']; ?>" data-balance="<?php echo number_format($bal, 2, '.', ''); ?>">
                        <?php echo getGradeLevelName($enrollment['grade_level']); ?> — <?php echo $sch['payment_type']; ?> (Balance: <?php echo formatCurrency($bal); ?>)
                    </option>
                    <?php endforeach; endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="amount_paid">Amount (₱)</label>
                <input type="number" id="amount_paid" name="amount_paid" min="0.01" step="0.01" required>
                <small id="balanceHint" style="color:#6b7280;">Select a payment period to see the balance.</small>
            </div>
            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="Cash">Cash</option>
                    <option value="Check">Check</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Card">Card</option>
                </select>
            </div>
            <div class="form-group">
                <label for="reference_number">Reference Number</label>
                <input type="text" id="reference_number" name="reference_number">
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2"></textarea>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Record Payment</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.profile-grid, .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; padding: 24px; }
.summary-grid { padding-top: 0; }
.summary-box { background: var(--ap-50, #f5f3ff); border-radius: 8px; padding: 16px; }
.summary-box span { display: block; font-size: 12px; color: #6b7280; margin-bottom: 4px; }
.summary-box strong { font-size: 18px; color: #1e1b2e; }
</style>
<script>
function openModal(id) { document.getElementById(id + 'Overlay').classList.add('active'); }
function closeModal(id) { document.getElementById(id + 'Overlay').classList.remove('active'); }
function updateBalanceHint() {
    const sel = document.getElementById('schedule_id');
    const opt = sel.options[sel.selectedIndex];
    const bal = opt ? opt.getAttribute('data-balance') : null;
    const hint = document.getElementById('balanceHint');
    const amount = document.getElementById('amount_paid');
    if (bal) {
        hint.textContent = 'Outstanding balance: ₱' + parseFloat(bal).toLocaleString('en-PH', {minimumFractionDigits: 2});
        amount.max = bal;
        amount.value = bal;
    }
}
<?php if ($error && ($_POST['action'] ?? '') === 'record_payment'): ?>
document.addEventListener('DOMContentLoaded', function() { openModal('paymentModal'); });
<?php endif; ?>
</script>
<?php renderPageEnd(); ?>
