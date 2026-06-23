<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('registrar');

$documents = $db->query("
    SELECT d.*, s.first_name, s.last_name, s.id as student_id
    FROM documents d
    JOIN students s ON d.student_id = s.id
    ORDER BY FIELD(d.status, 'pending', 'rejected', 'verified'), d.upload_date DESC
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Documents', 'registrar', 'documents', 'Registrar Personnel');
?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Student Documents</h3></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Document Type</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($documents) > 0): foreach ($documents as $doc): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($doc['document_type']); ?></td>
                    <td>
                        <span class="badge <?php echo $doc['status'] === 'verified' ? 'badge-success' : ($doc['status'] === 'rejected' ? 'badge-warning' : 'badge-warning'); ?>">
                            <?php echo ucfirst($doc['status']); ?>
                        </span>
                    </td>
                    <td><?php echo formatDate($doc['upload_date']); ?></td>
                    <td>
                        <?php if ($doc['status'] === 'pending'): ?>
                        <a href="<?php echo appUrl('/dashboard/registrar/verify-document.php?doc_id=' . $doc['id']); ?>" class="btn btn-primary btn-sm">Review</a>
                        <?php else: ?>
                        <span style="color:#6b7280;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center;padding:32px;color:#6b7280;">No documents found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
