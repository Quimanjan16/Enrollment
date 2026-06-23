<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('registrar');

$doc_id = (int)($_GET['doc_id'] ?? 0);
$message = '';
$error = '';

$query = "
    SELECT d.*, s.first_name, s.last_name, s.id as student_id
    FROM documents d
    JOIN students s ON d.student_id = s.id
    WHERE d.id = ?
";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    redirect('/dashboard/registrar/documents.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cleanInput($_POST['action'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');

    if (!in_array($action, ['verified', 'rejected'], true)) {
        $error = 'Please choose verify or reject.';
    } else {
        $upd = $db->prepare("UPDATE documents SET status = ?, verified_by = ?, verified_at = NOW(), notes = ? WHERE id = ?");
        $upd->bind_param("sisi", $action, $_SESSION['user_id'], $notes, $doc_id);
        if ($upd->execute()) {
            logActivity($db, $_SESSION['user_id'], 'Document ' . ucfirst($action), 'document', $doc_id, $doc['document_type']);
            $message = 'Document marked as ' . $action . '.';
            $stmt->execute();
            $doc = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Failed to update document status.';
        }
    }
}

renderPageStart('Verify Document', 'registrar', 'documents', 'Registrar Personnel');
renderAlerts($message, $error);
?>
<div class="card" style="max-width: 640px;">
    <div class="card-header">
        <h3 class="card-title">Review Document</h3>
        <a href="<?php echo appUrl('/dashboard/registrar/documents.php'); ?>" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div style="padding: 24px;">
        <p><strong>Student:</strong> <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></p>
        <p><strong>Document:</strong> <?php echo htmlspecialchars($doc['document_type']); ?></p>
        <p><strong>Uploaded:</strong> <?php echo formatDate($doc['upload_date']); ?></p>
        <p><strong>Current Status:</strong> <span class="badge badge-warning"><?php echo ucfirst($doc['status']); ?></span></p>
        <?php if ($doc['notes']): ?>
        <p><strong>Notes:</strong> <?php echo htmlspecialchars($doc['notes']); ?></p>
        <?php endif; ?>
    </div>
    <?php if ($doc['status'] === 'pending'): ?>
    <form method="POST">
        <div class="form-group">
            <label for="notes">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="3"></textarea>
        </div>
        <div class="card-footer" style="display:flex;gap:8px;">
            <button type="submit" name="action" value="verified" class="btn btn-success">Verify Document</button>
            <button type="submit" name="action" value="rejected" class="btn btn-secondary">Reject Document</button>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php renderPageEnd(); ?>
