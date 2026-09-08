<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/service-functions.php';
requireRole('client');

$uid = (int)$_SESSION['user_id'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$request = getServiceRequestForClient($conn, $id, $uid);
if (!$request) {
    http_response_code(404);
    die('Request not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    requireCsrf();

    if ($request['status'] !== 'Pending') {
        $error = 'Only pending requests can be cancelled by the client.';
    } else {
        $status = 'Cancelled';

        $stmt = $conn->prepare(
            "UPDATE service_requests
             SET status = ?
             WHERE id = ? AND user_id = ? AND status = 'Pending'"
        );

        if (!$stmt) {
            logDatabaseError('client cancel prepare', $conn);
            $error = 'Unable to cancel this request right now.';
        } else {
            $stmt->bind_param('sii', $status, $id, $uid);

            if ($stmt->execute() && $stmt->affected_rows === 1) {
                addRequestUpdate($conn, $id, $status, (string)($request['technician_name'] ?? ''), 'Request cancelled by client.', $uid);
                header('Location: /ramtech/client/request-details.php?id=' . $id . '&cancelled=1');
                exit;
            }

            $error = 'Unable to cancel this request. Its status may have already changed.';
        }
    }
}

$request = getServiceRequestForClient($conn, $id, $uid);
$updates = getRequestUpdates($conn, $id);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= requestCode($id) ?> | RamTech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/client-sidebar.php'; ?>
<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
  <div class="mx-auto max-w-5xl">
    <?php if (isset($_GET['created'])): ?>
      <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">Your service request was submitted successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['cancelled'])): ?>
      <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">Your service request was cancelled.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-black uppercase tracking-[0.3em]">Service Request</p>
        <h1 class="mt-2 text-4xl font-black"><?= requestCode($id) ?></h1>
      </div>

      <div class="flex items-center gap-3">
        <span class="rounded-full px-4 py-2 text-sm font-bold <?= statusClass($request['status']) ?>"><?= e($request['status']) ?></span>

        <?php if ($request['status'] === 'Pending'): ?>
          <form method="post" onsubmit="return confirm('Cancel this pending service request?');">
            <?= csrfField() ?>
            <button name="cancel_request" value="1" class="rounded-full border border-red-300 px-4 py-2 text-sm font-bold text-red-700">
              Cancel Request
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
      <section class="rounded-2xl border border-black/10 bg-white/45 p-6 lg:col-span-2">
        <h2 class="text-xl font-black">Request Details</h2>
        <dl class="mt-5 grid gap-5 sm:grid-cols-2">
          <div><dt class="text-xs uppercase tracking-wider text-black/40">Device</dt><dd class="mt-1 font-bold"><?= e(trim($request['brand'].' '.$request['model'])) ?: e($request['device_type']) ?></dd></div>
          <div><dt class="text-xs uppercase tracking-wider text-black/40">Service</dt><dd class="mt-1 font-bold"><?= e($request['service_type']) ?></dd></div>
          <div><dt class="text-xs uppercase tracking-wider text-black/40">Method</dt><dd class="mt-1 font-bold"><?= e($request['service_method']) ?></dd></div>
          <div><dt class="text-xs uppercase tracking-wider text-black/40">Assigned Technician</dt><dd class="mt-1 font-bold"><?= !empty($request['technician_name']) ? e($request['technician_name']) : 'Not assigned yet' ?></dd></div>
          <div><dt class="text-xs uppercase tracking-wider text-black/40">Submitted</dt><dd class="mt-1 font-bold"><?= date('M d, Y h:i A', strtotime($request['created_at'])) ?></dd></div>
          <div><dt class="text-xs uppercase tracking-wider text-black/40">Estimated Completion</dt><dd class="mt-1 font-bold"><?= $request['estimated_completion'] ? date('M d, Y', strtotime($request['estimated_completion'])) : 'Not set' ?></dd></div>
        </dl>

        <div class="mt-6">
          <div class="text-xs uppercase tracking-wider text-black/40">Issue Description</div>
          <p class="mt-2 leading-7 text-black/70"><?= nl2br(e($request['issue_description'])) ?></p>
        </div>

        <?php if (!empty($request['technician_notes'])): ?>
          <div class="mt-6 rounded-xl bg-black/5 p-4">
            <div class="text-xs uppercase tracking-wider text-black/40">Technician Notes</div>
            <p class="mt-2"><?= nl2br(e($request['technician_notes'])) ?></p>
          </div>
        <?php endif; ?>
      </section>

      <section class="rounded-2xl border border-black/10 bg-[#121312] p-6 text-white">
        <h2 class="text-xl font-black">Progress</h2>
        <div class="mt-6 space-y-5">
          <?php if (!$updates || $updates->num_rows === 0): ?>
            <p class="text-sm text-white/45">No updates yet.</p>
          <?php else: while ($u = $updates->fetch_assoc()): ?>
            <div class="relative border-l border-white/20 pl-5">
              <div class="absolute -left-1.5 top-1 h-3 w-3 rounded-full bg-[#fef5e6]"></div>
              <div class="font-bold"><?= e($u['status']) ?></div>
              <?php if (!empty($u['technician_name'])): ?><div class="mt-1 text-sm text-white/70">Technician: <span class="font-bold"><?= e($u['technician_name']) ?></span></div><?php endif; ?>
              <?php if (!empty($u['notes'])): ?><div class="mt-1 text-sm leading-6 text-white/55"><?= e($u['notes']) ?></div><?php endif; ?>
              <div class="mt-1 text-xs text-white/30"><?= date('M d, Y h:i A', strtotime($u['created_at'])) ?></div>
            </div>
          <?php endwhile; endif; ?>
        </div>
      </section>
    </div>
  </div>
</main>
</body>
</html>
