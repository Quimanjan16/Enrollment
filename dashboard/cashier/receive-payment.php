<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('cashier');

$message = '';
$error = '';
if (isset($_GET['success'])) {
    $message = 'Payment recorded successfully. Balances have been updated for all roles.';
}
$search = cleanInput($_GET['search'] ?? '');
$selected_schedule_id = (int)($_GET['schedule_id'] ?? $_POST['schedule_id'] ?? 0);

$pending_query = "
    SELECT ps.*, e.student_id, e.academic_year, e.semester, e.grade_level,
           s.first_name, s.last_name
    FROM payment_schedules ps
    JOIN enrollments e ON ps.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    WHERE ps.payment_status != 'paid'
";
if ($search !== '') {
    $pending_query .= " AND (s.first_name LIKE '%" . $db->real_escape_string($search) . "%'
        OR s.last_name LIKE '%" . $db->real_escape_string($search) . "%'
        OR s.contact_number LIKE '%" . $db->real_escape_string($search) . "%')";
}
$pending_query .= " ORDER BY s.last_name, s.first_name, FIELD(ps.payment_type, 'Prelim', 'Midterm', 'Pre-Final', 'Final')";
$pending_schedules = $db->query($pending_query)->fetch_all(MYSQLI_ASSOC);

$selected = null;
foreach ($pending_schedules as $row) {
    if ((int)$row['id'] === $selected_schedule_id) {
        $selected = $row;
        break;
    }
}
if (!$selected && $selected_schedule_id > 0) {
    $stmt = $db->prepare("
        SELECT ps.*, e.student_id, e.academic_year, e.semester, e.grade_level, s.first_name, s.last_name
        FROM payment_schedules ps
        JOIN enrollments e ON ps.enrollment_id = e.id
        JOIN students s ON e.student_id = s.id
        WHERE ps.id = ? AND ps.payment_status != 'paid'
    ");
    $stmt->bind_param("i", $selected_schedule_id);
    $stmt->execute();
    $selected = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = (int)($_POST['schedule_id'] ?? 0);
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $payment_method = cleanInput($_POST['payment_method'] ?? '');
    $reference_number = cleanInput($_POST['reference_number'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');

    if ($schedule_id <= 0) {
        $error = 'Please select a student and payment period.';
    } elseif ($amount_paid <= 0 || empty($payment_method)) {
        $error = 'Enter a valid amount and payment method.';
    } else {
        try {
            $db->begin_transaction();
            $result = recordStudentPayment($db, $schedule_id, $amount_paid, $payment_method, $reference_number, $notes, $_SESSION['user_id']);
            $db->commit();
            redirect('/dashboard/cashier/receive-payment.php?success=1');
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}

$today = $db->query("SELECT COALESCE(SUM(amount_paid), 0) as total FROM payments WHERE DATE(created_at) = CURDATE()")->fetch_assoc();

renderPageStart('Receive Payment', 'cashier', 'receive-payment', 'Cashier');
renderAlerts($message, $error);
?>
<div class="receive-hero">
    <div>
        <h2>Receive &amp; Record Payment</h2>
        <p>Select the student and payment period (Prelim, Midterm, Pre-Final, or Final), enter the amount received, then save. Balances update for cashier, assessment, registrar, and admin.</p>
    </div>
    <div class="today-box">
        <span>Today's collections</span>
        <strong><?php echo formatCurrency($today['total'] ?? 0); ?></strong>
    </div>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3 class="card-title">Payment Form</h3>
    </div>
    <form method="POST" id="paymentForm">
        <div class="form-row">
            <div class="form-group">
                <label for="schedule_id">Student &amp; Payment Period</label>
                <select id="schedule_id" name="schedule_id" required onchange="updatePaymentDetails()">
                    <option value="">— Select who is paying —</option>
                    <?php foreach ($pending_schedules as $ps):
                        $balance = max(0, $ps['amount_due'] - $ps['amount_paid']);
                    ?>
                    <option value="<?php echo $ps['id']; ?>"
                        data-balance="<?php echo number_format($balance, 2, '.', ''); ?>"
                        data-due="<?php echo number_format($ps['amount_due'], 2, '.', ''); ?>"
                        data-paid="<?php echo number_format($ps['amount_paid'], 2, '.', ''); ?>"
                        data-student="<?php echo htmlspecialchars($ps['first_name'] . ' ' . $ps['last_name']); ?>"
                        data-type="<?php echo htmlspecialchars($ps['payment_type']); ?>"
                        data-grade="<?php echo htmlspecialchars(getGradeLevelName($ps['grade_level'])); ?>"
                        <?php echo (int)$ps['id'] === $selected_schedule_id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ps['last_name'] . ', ' . $ps['first_name']); ?>
                        — <?php echo getGradeLevelName($ps['grade_level']); ?>
                        — <?php echo $ps['payment_type']; ?>
                        (Balance: <?php echo formatCurrency($balance); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (count($pending_schedules) === 0): ?>
                <small style="color:#ef4444;display:block;margin-top:8px;">
                    No unpaid schedules found. Enroll a student first (New Enrollees → Enroll), then return here to receive payment.
                </small>
                <?php endif; ?>
            </div>
        </div>

        <div id="paymentDetails" class="payment-details" style="display:none;">
            <div class="detail-grid">
                <div><span>Student</span><strong id="detailStudent">—</strong></div>
                <div><span>Grade</span><strong id="detailGrade">—</strong></div>
                <div><span>Period</span><strong id="detailType">—</strong></div>
                <div><span>Amount Due</span><strong id="detailDue">—</strong></div>
                <div><span>Already Paid</span><strong id="detailPaid">—</strong></div>
                <div><span>Balance</span><strong id="detailBalance" style="color:#ef4444;">—</strong></div>
            </div>
        </div>

        <div class="form-row" style="margin-top:16px;">
            <div class="form-group">
                <label for="amount_paid">Amount Received (₱)</label>
                <input type="number" id="amount_paid" name="amount_paid" min="0.01" step="0.01" placeholder="0.00" required>
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
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="reference_number">Reference / OR Number</label>
                <input type="text" id="reference_number" name="reference_number" placeholder="Optional">
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <input type="text" id="notes" name="notes" placeholder="Optional">
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-success btn-lg" <?php echo count($pending_schedules) === 0 ? 'disabled' : ''; ?>>
                💵 Record Payment
            </button>
            <a href="<?php echo appUrl('/dashboard/cashier/payment-records.php'); ?>" class="btn btn-secondary">View History</a>
        </div>
    </form>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3 class="card-title">Find Student</h3>
        <form method="GET" style="display:flex;gap:8px;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name or contact..." style="padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;">
            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            <?php if ($search): ?><a href="?" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
        </form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Period</th>
                    <th>Due</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pending_schedules) > 0): foreach ($pending_schedules as $ps):
                    $bal = max(0, $ps['amount_due'] - $ps['amount_paid']);
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($ps['first_name'] . ' ' . $ps['last_name']); ?></strong></td>
                    <td><?php echo getGradeLevelName($ps['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($ps['payment_type']); ?></td>
                    <td><?php echo formatCurrency($ps['amount_due']); ?></td>
                    <td><?php echo formatCurrency($ps['amount_paid']); ?></td>
                    <td><?php echo formatCurrency($bal); ?></td>
                    <td>
                        <a href="?schedule_id=<?php echo $ps['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-success btn-sm">Receive</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:32px;color:#6b7280;">
                        No outstanding balances. Enroll students under <strong>New Enrollees</strong> first.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.receive-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    background: linear-gradient(135deg, #7c3aed 0%, #9b62fc 100%);
    color: white;
    padding: 28px 32px;
    border-radius: 16px;
}
.receive-hero h2 { margin: 0 0 8px; color: white; font-size: 24px; }
.receive-hero p { margin: 0; opacity: 0.9; font-size: 14px; max-width: 560px; }
.today-box { background: rgba(255,255,255,0.15); padding: 16px 24px; border-radius: 12px; text-align: center; }
.today-box span { display: block; font-size: 12px; opacity: 0.9; }
.today-box strong { font-size: 28px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 0 24px; }
.payment-details { padding: 16px 24px; background: #f5f3ff; border-top: 1px solid #e9d5ff; border-bottom: 1px solid #e9d5ff; }
.detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
.detail-grid span { display: block; font-size: 12px; color: #6b7280; }
.detail-grid strong { font-size: 15px; color: #1e1b2e; }
.btn-lg { padding: 14px 28px; font-size: 16px; }
@media (max-width: 768px) {
    .receive-hero { flex-direction: column; text-align: center; }
    .form-row { grid-template-columns: 1fr; }
}
</style>
<script>
function updatePaymentDetails() {
    const sel = document.getElementById('schedule_id');
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('paymentDetails');
    const amount = document.getElementById('amount_paid');

    if (!opt || !opt.value) {
        box.style.display = 'none';
        amount.value = '';
        return;
    }

    box.style.display = 'block';
    document.getElementById('detailStudent').textContent = opt.dataset.student;
    document.getElementById('detailGrade').textContent = opt.dataset.grade;
    document.getElementById('detailType').textContent = opt.dataset.type;
    document.getElementById('detailDue').textContent = '₱' + parseFloat(opt.dataset.due).toLocaleString('en-PH', {minimumFractionDigits: 2});
    document.getElementById('detailPaid').textContent = '₱' + parseFloat(opt.dataset.paid).toLocaleString('en-PH', {minimumFractionDigits: 2});
    document.getElementById('detailBalance').textContent = '₱' + parseFloat(opt.dataset.balance).toLocaleString('en-PH', {minimumFractionDigits: 2});

    amount.max = opt.dataset.balance;
    amount.value = opt.dataset.balance;
}
document.addEventListener('DOMContentLoaded', updatePaymentDetails);
</script>
<?php renderPageEnd(); ?>
