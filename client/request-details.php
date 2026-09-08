<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('client');

$uid = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM service_requests WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $id, $uid);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
if (!$request) { http_response_code(404); die("Request not found."); }

$stmt = $conn->prepare("SELECT ru.*, u.first_name, u.last_name FROM request_updates ru LEFT JOIN users u ON u.id=ru.updated_by WHERE ru.request_id=? ORDER BY ru.created_at ASC");
$stmt->bind_param("i", $id);
$stmt->execute();
$updates = $stmt->get_result();
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

    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-black uppercase tracking-[0.3em]">Service Request</p>
        <h1 class="mt-2 text-4xl font-black"><?= requestCode($id) ?></h1>
      </div>
      <span class="rounded-full px-4 py-2 text-sm font-bold <?= statusClass($request['status']) ?>"><?= e($request['status']) ?></span>
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
        </dl>
        <div class="mt-6"><div class="text-xs uppercase tracking-wider text-black/40">Issue Description</div><p class="mt-2 leading-7 text-black/70"><?= nl2br(e($request['issue_description'])) ?></p></div>
        <?php if (!empty($request['technician_notes'])): ?>
          <div class="mt-6 rounded-xl bg-black/5 p-4"><div class="text-xs uppercase tracking-wider text-black/40">Technician Notes</div><p class="mt-2"><?= nl2br(e($request['technician_notes'])) ?></p></div>
        <?php endif; ?>
      </section>

      <section class="rounded-2xl border border-black/10 bg-[#121312] p-6 text-white">
        <h2 class="text-xl font-black">Progress</h2>
        <div class="mt-6 space-y-5">
          <?php while($u=$updates->fetch_assoc()): ?>
            <div class="relative border-l border-white/20 pl-5">
              <div class="absolute -left-1.5 top-1 h-3 w-3 rounded-full bg-[#fef5e6]"></div>
              <div class="font-bold"><?= e($u['status']) ?></div>
              <?php if (!empty($u['technician_name'])): ?><div class="mt-1 text-sm text-white/70">Technician: <span class="font-bold"><?= e($u['technician_name']) ?></span></div><?php endif; ?>
              <?php if ($u['notes']): ?><div class="mt-1 text-sm leading-6 text-white/55"><?= e($u['notes']) ?></div><?php endif; ?>
              <div class="mt-1 text-xs text-white/30"><?= date('M d, Y h:i A', strtotime($u['created_at'])) ?></div>
            </div>
          <?php endwhile; ?>
        </div>
      </section>
    </div>
  </div>
</main>
</body>
</html>
