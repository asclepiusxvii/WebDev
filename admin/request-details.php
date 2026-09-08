<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/service-functions.php';
requireRole('admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$message = '';
$error = '';

$r = getServiceRequestForAdmin($conn, $id);
if (!$r) {
    http_response_code(404);
    die('Request not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (isset($_POST['delete_request'])) {
        $stmt = $conn->prepare('DELETE FROM service_requests WHERE id = ?');

        if (!$stmt) {
            logDatabaseError('admin delete request prepare', $conn);
            $error = 'Unable to delete this request right now.';
        } else {
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                flash('success', 'Service request ' . requestCode($id) . ' was permanently deleted.');
                header('Location: /ramtech/admin/requests.php');
                exit;
            }

            error_log('RamTech DB error [admin delete request execute]: ' . $stmt->error);
            $error = 'Unable to delete this request right now.';
        }
    } elseif (isset($_POST['update_request'])) {
        $status = trim((string)($_POST['status'] ?? ''));
        $technicianName = trim((string)($_POST['technician_name'] ?? ''));
        $notes = trim((string)($_POST['technician_notes'] ?? ''));
        $estimated = trim((string)($_POST['estimated_completion'] ?? ''));
        $admin = (int)$_SESSION['user_id'];

        if (!in_array($status, allowedStatuses(), true)) {
            $error = 'Please choose a valid request status.';
        } elseif (mb_strlen($technicianName) > 150) {
            $error = 'Technician name must be 150 characters or fewer.';
        } elseif ($status === 'Accepted' && $technicianName === '') {
            $error = 'Please enter the technician\'s name before accepting this request.';
        } elseif ($technicianName !== '' && !preg_match("/^[\p{L} .'-]+$/u", $technicianName)) {
            $error = 'Technician name contains invalid characters.';
        } elseif (mb_strlen($notes) > 3000) {
            $error = 'Technician notes must be 3000 characters or fewer.';
        } elseif (!validDateOrEmpty($estimated)) {
            $error = 'Please enter a valid estimated completion date.';
        } else {
            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare(
                    "UPDATE service_requests
                     SET status = ?, technician_name = ?, technician_notes = ?, estimated_completion = NULLIF(?, '')
                     WHERE id = ?"
                );

                if (!$stmt) {
                    throw new RuntimeException('Could not prepare service update.');
                }

                $stmt->bind_param('ssssi', $status, $technicianName, $notes, $estimated, $id);

                if (!$stmt->execute()) {
                    throw new RuntimeException('Could not save service update.');
                }

                if (!addRequestUpdate($conn, $id, $status, $technicianName, $notes, $admin)) {
                    throw new RuntimeException('Could not save request history.');
                }

                $conn->commit();
                $message = 'Request updated successfully.';
                $r = getServiceRequestForAdmin($conn, $id);
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('RamTech admin update error: ' . $e->getMessage());
                $error = 'Unable to update this request right now. Please try again.';
            }
        }
    }
}

$updates = getRequestUpdates($conn, $id);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage <?= requestCode($id) ?> | RamTech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/admin-sidebar.php'; ?>

<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
<div class="mx-auto max-w-6xl">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <p class="text-xs font-black uppercase tracking-[0.3em]">Manage Request</p>
      <h1 class="mt-2 text-4xl font-black"><?= requestCode($id) ?></h1>
    </div>
    <span class="rounded-full px-4 py-2 text-sm font-bold <?= statusClass($r['status']) ?>"><?= e($r['status']) ?></span>
  </div>

  <?php if ($message): ?><div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= e($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-black/10 bg-white/45 p-6">
      <h2 class="text-xl font-black">Customer & Request</h2>
      <dl class="mt-5 space-y-4 text-sm">
        <div><dt class="text-black/40">Customer</dt><dd class="font-bold"><?= e($r['client_name']) ?></dd><dd><?= e($r['email']) ?><?= $r['phone'] ? ' • ' . e($r['phone']) : '' ?></dd></div>
        <div><dt class="text-black/40">Device</dt><dd class="font-bold"><?= e(trim($r['brand'].' '.$r['model'])) ?: e($r['device_type']) ?></dd></div>
        <div><dt class="text-black/40">Service</dt><dd class="font-bold"><?= e($r['service_type']) ?> • <?= e($r['service_method']) ?></dd></div>
        <div><dt class="text-black/40">Assigned Technician</dt><dd class="font-bold"><?= $r['technician_name'] ? e($r['technician_name']) : 'Not assigned yet' ?></dd></div>
        <div><dt class="text-black/40">Issue</dt><dd class="mt-1 leading-6"><?= nl2br(e($r['issue_description'])) ?></dd></div>
      </dl>
    </section>

    <form method="post" id="updateForm" novalidate class="rounded-2xl border border-black/10 bg-[#121312] p-6 text-white">
      <?= csrfField() ?>
      <h2 class="text-xl font-black">Update Service</h2>

      <label class="mt-5 block">
        <span class="text-sm font-bold">Current Status</span>
        <select id="status" name="status" required class="mt-2 w-full rounded-xl bg-white px-4 py-3 text-black">
          <?php foreach (allowedStatuses() as $s): ?>
            <option value="<?= e($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <div class="mt-5 rounded-xl border border-white/10 bg-white/5 p-4">
        <label class="block">
          <span class="text-sm font-bold">Technician Name <span id="technicianRequired" class="hidden text-red-300">*</span></span>
          <input id="technicianName" type="text" name="technician_name" maxlength="150"
                 value="<?= e($r['technician_name'] ?? '') ?>" placeholder="e.g. Alex Ramos"
                 class="mt-2 w-full rounded-xl bg-white px-4 py-3 text-black">
          <p class="mt-2 text-xs leading-5 text-white/45">A technician is required when the request is accepted.</p>
        </label>
      </div>

      <label class="mt-5 block">
        <span class="text-sm font-bold">Estimated Completion</span>
        <input type="date" name="estimated_completion" value="<?= e($r['estimated_completion'] ?? '') ?>"
               class="mt-2 w-full rounded-xl bg-white px-4 py-3 text-black">
      </label>

      <label class="mt-5 block">
        <span class="text-sm font-bold">Technician Notes</span>
        <textarea name="technician_notes" maxlength="3000" rows="6"
                  class="mt-2 w-full rounded-xl bg-white px-4 py-3 text-black"><?= e($r['technician_notes']) ?></textarea>
      </label>

      <button name="update_request" value="1" class="mt-6 rounded-full bg-[#fef5e6] px-6 py-3 font-bold text-[#121312]">Update Request</button>
    </form>
  </div>

  <section class="mt-6 rounded-2xl border border-black/10 bg-white/45 p-6">
    <h2 class="text-xl font-black">Update History</h2>
    <div class="mt-5 space-y-4">
      <?php if (!$updates || $updates->num_rows === 0): ?>
        <p class="text-black/45">No updates yet.</p>
      <?php else: while ($u = $updates->fetch_assoc()): ?>
        <div class="border-l-2 border-black/15 pl-4">
          <div class="font-bold"><?= e($u['status']) ?></div>
          <?php if (!empty($u['technician_name'])): ?><div class="mt-1 text-sm">Technician: <span class="font-bold"><?= e($u['technician_name']) ?></span></div><?php endif; ?>
          <?php if (!empty($u['notes'])): ?><div class="mt-1 text-sm text-black/60"><?= nl2br(e($u['notes'])) ?></div><?php endif; ?>
          <div class="mt-1 text-xs text-black/35"><?= date('M d, Y h:i A', strtotime($u['created_at'])) ?><?= $u['updater'] ? ' • ' . e($u['updater']) : '' ?></div>
        </div>
      <?php endwhile; endif; ?>
    </div>
  </section>

  <section class="mt-6 rounded-2xl border border-red-200 bg-red-50/50 p-6">
    <p class="text-xs font-black uppercase tracking-[0.25em] text-red-600">Danger Zone</p>
    <h2 class="mt-2 text-xl font-black text-red-800">Delete This Request</h2>
    <p class="mt-2 text-sm text-red-700/80">This permanently deletes this service request and its update history.</p>
    <form method="post" class="mt-5" onsubmit="return confirm('Permanently delete <?= requestCode($id) ?> and all of its history?');">
      <?= csrfField() ?>
      <button name="delete_request" value="1" class="rounded-full bg-red-700 px-5 py-3 text-sm font-bold text-white">Delete Request</button>
    </form>
  </section>
</div>
</main>

<script>
const statusSelect = document.getElementById('status');
const technicianInput = document.getElementById('technicianName');
const technicianRequired = document.getElementById('technicianRequired');

function updateTechnicianRequirement() {
    const isAccepted = statusSelect.value === 'Accepted';
    technicianInput.required = isAccepted;
    technicianRequired.classList.toggle('hidden', !isAccepted);
}
statusSelect.addEventListener('change', updateTechnicianRequirement);
updateTechnicianRequirement();
</script>
</body>
</html>
