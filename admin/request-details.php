<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
$message = '';
$error = '';

$stmt = $conn->prepare("
    SELECT sr.*,
           CONCAT(u.first_name,' ',u.last_name) client_name,
           u.email,
           u.phone
    FROM service_requests sr
    JOIN users u ON u.id = sr.user_id
    WHERE sr.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();

if (!$r) {
    http_response_code(404);
    die("Request not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = trim($_POST['status'] ?? '');
    $technicianName = trim($_POST['technician_name'] ?? '');
    $notes = trim($_POST['technician_notes'] ?? '');
    $estimated = trim($_POST['estimated_completion'] ?? '');
    $admin = (int)$_SESSION['user_id'];

    /*
     * When a request is accepted, a technician must be assigned.
     * For later updates, the technician name remains attached to the request.
     */
    if ($status === 'Accepted' && $technicianName === '') {
        $error = 'Please enter the technician\'s name before accepting this request.';
    } else {
        $stmt = $conn->prepare("
            UPDATE service_requests
            SET status = ?,
                technician_name = ?,
                technician_notes = ?,
                estimated_completion = NULLIF(?, '')
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssi",
            $status,
            $technicianName,
            $notes,
            $estimated,
            $id
        );

        if ($stmt->execute()) {
            $upd = $conn->prepare("
                INSERT INTO request_updates
                    (request_id, status, technician_name, notes, updated_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $upd->bind_param(
                "isssi",
                $id,
                $status,
                $technicianName,
                $notes,
                $admin
            );
            $upd->execute();

            $message = 'Request updated successfully.';

            // Refresh current request data after update.
            $stmt = $conn->prepare("
                SELECT sr.*,
                       CONCAT(u.first_name,' ',u.last_name) client_name,
                       u.email,
                       u.phone
                FROM service_requests sr
                JOIN users u ON u.id = sr.user_id
                WHERE sr.id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Unable to update request.';
        }
    }
}

$stmt = $conn->prepare("
    SELECT ru.*,
           CONCAT(u.first_name,' ',u.last_name) updater
    FROM request_updates ru
    LEFT JOIN users u ON u.id = ru.updated_by
    WHERE request_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$updates = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
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

    <span class="rounded-full px-4 py-2 text-sm font-bold <?= statusClass($r['status']) ?>">
      <?= e($r['status']) ?>
    </span>
  </div>

  <?php if ($message): ?>
    <div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
      <?= e($message) ?>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
      <?= e($error) ?>
    </div>
  <?php endif; ?>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">

    <section class="rounded-2xl border border-black/10 bg-white/45 p-6">
      <h2 class="text-xl font-black">Customer & Request</h2>

      <dl class="mt-5 space-y-4 text-sm">
        <div>
          <dt class="text-black/40">Customer</dt>
          <dd class="font-bold"><?= e($r['client_name']) ?></dd>
          <dd><?= e($r['email']) ?> • <?= e($r['phone']) ?></dd>
        </div>

        <div>
          <dt class="text-black/40">Device</dt>
          <dd class="font-bold">
            <?= e(trim($r['brand'].' '.$r['model'])) ?: e($r['device_type']) ?>
          </dd>
        </div>

        <div>
          <dt class="text-black/40">Service</dt>
          <dd class="font-bold">
            <?= e($r['service_type']) ?> • <?= e($r['service_method']) ?>
          </dd>
        </div>

        <div>
          <dt class="text-black/40">Assigned Technician</dt>
          <dd class="font-bold">
            <?= $r['technician_name'] ? e($r['technician_name']) : 'Not assigned yet' ?>
          </dd>
        </div>

        <div>
          <dt class="text-black/40">Issue</dt>
          <dd class="mt-1 leading-6"><?= nl2br(e($r['issue_description'])) ?></dd>
        </div>
      </dl>
    </section>

    <form method="post"
          id="updateForm"
          class="rounded-2xl border border-black/10 bg-[#121312] p-6 text-white">

      <h2 class="text-xl font-black">Update Service</h2>

      <label class="mt-5 block">
        <span class="text-sm font-bold">Current Status</span>

        <select
          id="status"
          name="status"
          class="mt-2 w-full rounded-xl bg-white px-4 py-3 text-black">

          <?php foreach([
            'Pending',
            'Accepted',
            'Device Received',
            'Diagnosing',
            'In Progress',
            'Ready for Pickup',
            'Completed',
            'Cancelled'
          ] as $s): ?>

            <option value="<?= e($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>>
              <?= e($s) ?>
            </option>

          <?php endforeach; ?>
        </select>
      </label>

      <!-- Technician assignment -->
      <div id="technicianBox"
           class="mt-5 rounded-xl border border-white/10 bg-white/5 p-4">

        <label class="block">
          <span class="text-sm font-bold">
            Technician Name
            <span id="technicianRequired"
                  class="hidden text-red-300">*</span>
          </span>

          <input
            id="technicianName"
            type="text"
            name="technician_name"
            value="<?= e($r['technician_name'] ?? '') ?>"
            placeholder="e.g. Alex Ramos"
            class="mt-2 w-full rounded-xl bg-white px-4 py-3 text-black">

          <p id="technicianHint"
             class="mt-2 text-xs leading-5 text-white/45">
            A technician must be assigned when the request is accepted.
          </p>
        </label>
      </div>

      <label class="mt-5 block">
        <span class="text-sm font-bold">Estimated Completion</span>

        <input
          type="date"
          name="estimated_completion"
          value="<?= e($r['estimated_completion'] ?? '') ?>"
          class="mt-2 w-full rounded-xl bg-white px-4 py-3 text-black">
      </label>

      <label class="mt-5 block">
        <span class="text-sm font-bold">Technician Notes</span>

        <textarea
          name="technician_notes"
          rows="6"
          class="mt-2 w-full rounded-xl bg-white px-4 py-3 text-black"><?= e($r['technician_notes']) ?></textarea>
      </label>

      <button
        class="mt-6 rounded-full bg-[#fef5e6] px-6 py-3 font-bold text-[#121312]">
        Update Request
      </button>
    </form>
  </div>

  <section class="mt-6 rounded-2xl border border-black/10 bg-white/45 p-6">
    <h2 class="text-xl font-black">Update History</h2>

    <div class="mt-5 space-y-4">

      <?php if ($updates->num_rows === 0): ?>

        <p class="text-black/45">No updates yet.</p>

      <?php else: while ($u = $updates->fetch_assoc()): ?>

        <div class="border-l-2 border-black/15 pl-4">

          <div class="font-bold">
            <?= e($u['status']) ?>
          </div>

          <?php if (!empty($u['technician_name'])): ?>
            <div class="mt-1 text-sm">
              Technician:
              <span class="font-bold"><?= e($u['technician_name']) ?></span>
            </div>
          <?php endif; ?>

          <?php if (!empty($u['notes'])): ?>
            <div class="mt-1 text-sm text-black/60">
              <?= nl2br(e($u['notes'])) ?>
            </div>
          <?php endif; ?>

          <div class="mt-1 text-xs text-black/35">
            <?= date('M d, Y h:i A', strtotime($u['created_at'])) ?>
            <?= $u['updater'] ? ' • ' . e($u['updater']) : '' ?>
          </div>

        </div>

      <?php endwhile; endif; ?>

    </div>
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

    if (isAccepted && technicianInput.value.trim() === '') {
        technicianInput.focus();
    }
}

statusSelect.addEventListener('change', updateTechnicianRequirement);
updateTechnicianRequirement();
</script>
</body>
</html>
