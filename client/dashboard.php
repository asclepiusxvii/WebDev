<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('client');

$uid = (int)$_SESSION['user_id'];

$counts = ['Pending'=>0,'In Progress'=>0,'Completed'=>0];
$stmt = $conn->prepare("SELECT status, COUNT(*) total FROM service_requests WHERE user_id=? GROUP BY status");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    if ($r['status'] === 'Completed') $counts['Completed'] += (int)$r['total'];
    elseif (in_array($r['status'], ['In Progress','Accepted','Device Received','Diagnosing','Ready for Pickup'])) $counts['In Progress'] += (int)$r['total'];
    else $counts['Pending'] += (int)$r['total'];
}

$stmt = $conn->prepare("SELECT * FROM service_requests WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $uid);
$stmt->execute();
$recent = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Client Dashboard | RamTech</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/ramtech/assets/css/custom.css">
</head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/client-sidebar.php'; ?>
<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
  <div class="mx-auto max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-black uppercase tracking-[0.3em]">Client Portal</p>
        <h1 class="mt-2 text-4xl font-black">Hello, <?= e(explode(' ', $_SESSION['name'])[0]) ?>.</h1>
        <p class="mt-2 text-black/55">Here's what's happening with your service requests.</p>
      </div>
      <a href="/ramtech/client/request-service.php" class="rounded-full bg-[#121312] px-5 py-3 text-sm font-bold text-[#fef5e6]">+ Request a Service</a>
    </div>

    <div class="mt-8 grid gap-5 sm:grid-cols-3">
      <div class="rounded-2xl border border-black/10 bg-white/45 p-6"><div class="text-4xl font-black"><?= $counts['Pending'] ?></div><div class="mt-2 text-sm text-black/55">Pending / New</div></div>
      <div class="rounded-2xl border border-black/10 bg-white/45 p-6"><div class="text-4xl font-black"><?= $counts['In Progress'] ?></div><div class="mt-2 text-sm text-black/55">In Progress</div></div>
      <div class="rounded-2xl border border-black/10 bg-white/45 p-6"><div class="text-4xl font-black"><?= $counts['Completed'] ?></div><div class="mt-2 text-sm text-black/55">Completed</div></div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-black/10 bg-white/45">
      <div class="flex items-center justify-between border-b border-black/10 p-6">
        <h2 class="text-xl font-black">Recent Service Requests</h2>
        <a class="text-sm font-bold" href="/ramtech/client/my-requests.php">View all →</a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-black/5 text-xs uppercase tracking-wider text-black/45">
            <tr><th class="p-4">Request</th><th class="p-4">Device</th><th class="p-4">Service</th><th class="p-4">Status</th><th class="p-4"></th></tr>
          </thead>
          <tbody>
          <?php if ($recent->num_rows === 0): ?>
            <tr><td colspan="5" class="p-8 text-center text-black/45">No service requests yet.</td></tr>
          <?php else: while($r = $recent->fetch_assoc()): ?>
            <tr class="border-t border-black/5">
              <td class="p-4 font-bold"><?= requestCode((int)$r['id']) ?></td>
              <td class="p-4"><?= e(trim($r['brand'].' '.$r['model'])) ?></td>
              <td class="p-4"><?= e($r['service_type']) ?></td>
              <td class="p-4"><span class="rounded-full px-3 py-1 text-xs font-bold <?= statusClass($r['status']) ?>"><?= e($r['status']) ?></span></td>
              <td class="p-4"><a class="font-bold" href="/ramtech/client/request-details.php?id=<?= (int)$r['id'] ?>">View →</a></td>
            </tr>
          <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
</body>
</html>
