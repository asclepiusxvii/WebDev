<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('staff');

$totalRequests = 0;
$pendingRequests = 0;
$activeRequests = 0;
$completedRequests = 0;

$result = $conn->query(
    "SELECT
       COUNT(*) AS total_requests,
       SUM(status = 'Pending') AS pending_requests,
       SUM(status IN ('Accepted','Device Received','Diagnosing','In Progress','Ready for Pickup')) AS active_requests,
       SUM(status = 'Completed') AS completed_requests
     FROM service_requests"
);

if ($result) {
    $stats = $result->fetch_assoc();
    $totalRequests = (int)($stats['total_requests'] ?? 0);
    $pendingRequests = (int)($stats['pending_requests'] ?? 0);
    $activeRequests = (int)($stats['active_requests'] ?? 0);
    $completedRequests = (int)($stats['completed_requests'] ?? 0);
}

$recent = $conn->query(
    "SELECT sr.id, sr.device_type, sr.brand, sr.model, sr.status, sr.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS client_name
     FROM service_requests sr
     JOIN users u ON u.id = sr.user_id
     ORDER BY sr.created_at DESC
     LIMIT 8"
);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Staff Dashboard | RamTech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/admin-sidebar.php'; ?>

<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
  <div class="mx-auto max-w-6xl">
    <p class="text-xs font-black uppercase tracking-[0.3em]">Staff Workspace</p>
    <h1 class="mt-2 text-4xl font-black">Dashboard</h1>
    <p class="mt-3 text-sm text-black/55">Review and update RamTech service requests.</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-2xl border border-black/10 bg-white/45 p-5"><div class="text-xs font-bold uppercase tracking-wider text-black/45">Total Requests</div><div class="mt-2 text-3xl font-black"><?= $totalRequests ?></div></div>
      <div class="rounded-2xl border border-black/10 bg-white/45 p-5"><div class="text-xs font-bold uppercase tracking-wider text-black/45">Pending</div><div class="mt-2 text-3xl font-black"><?= $pendingRequests ?></div></div>
      <div class="rounded-2xl border border-black/10 bg-white/45 p-5"><div class="text-xs font-bold uppercase tracking-wider text-black/45">Active Repairs</div><div class="mt-2 text-3xl font-black"><?= $activeRequests ?></div></div>
      <div class="rounded-2xl border border-black/10 bg-white/45 p-5"><div class="text-xs font-bold uppercase tracking-wider text-black/45">Completed</div><div class="mt-2 text-3xl font-black"><?= $completedRequests ?></div></div>
    </div>

    <section class="mt-8 overflow-hidden rounded-2xl border border-black/10 bg-white/45">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-black/10 p-6">
        <div>
          <h2 class="text-xl font-black">Recent Service Requests</h2>
          <p class="mt-1 text-sm text-black/45">Open a request to update its status and technician information.</p>
        </div>
        <a href="/ramtech/admin/requests.php" class="rounded-full bg-[#121312] px-5 py-3 text-sm font-bold text-white">View All Requests</a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
          <thead class="bg-black/5 text-xs uppercase tracking-wider text-black/45">
            <tr><th class="p-4">Request</th><th class="p-4">Client</th><th class="p-4">Device</th><th class="p-4">Status</th><th class="p-4">Created</th><th class="p-4"></th></tr>
          </thead>
          <tbody>
          <?php if (!$recent || $recent->num_rows === 0): ?>
            <tr><td colspan="6" class="p-8 text-center text-black/45">No requests yet.</td></tr>
          <?php else: while ($request = $recent->fetch_assoc()): ?>
            <tr class="border-t border-black/5">
              <td class="p-4 font-bold"><?= requestCode((int)$request['id']) ?></td>
              <td class="p-4"><?= e($request['client_name']) ?></td>
              <td class="p-4"><?= e(trim($request['brand'].' '.$request['model']) ?: $request['device_type']) ?></td>
              <td class="p-4"><span class="rounded-full px-3 py-1 text-xs font-bold <?= statusClass($request['status']) ?>"><?= e($request['status']) ?></span></td>
              <td class="p-4"><?= date('M d, Y', strtotime($request['created_at'])) ?></td>
              <td class="p-4"><a class="font-bold underline" href="/ramtech/admin/request-details.php?id=<?= (int)$request['id'] ?>">Manage</a></td>
            </tr>
          <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</main>
</body>
</html>
