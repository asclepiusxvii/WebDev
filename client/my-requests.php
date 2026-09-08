<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('client');

$uid = (int)$_SESSION['user_id'];
$status = trim($_GET['status'] ?? '');

if ($status !== '') {
    $stmt = $conn->prepare("SELECT * FROM service_requests WHERE user_id=? AND status=? ORDER BY created_at DESC");
    $stmt->bind_param("is", $uid, $status);
} else {
    $stmt = $conn->prepare("SELECT * FROM service_requests WHERE user_id=? ORDER BY created_at DESC");
    $stmt->bind_param("i", $uid);
}
$stmt->execute();
$rows = $stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Requests | RamTech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/client-sidebar.php'; ?>
<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
  <div class="mx-auto max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div><p class="text-xs font-black uppercase tracking-[0.3em]">Services</p><h1 class="mt-2 text-4xl font-black">My Service Requests</h1></div>
      <a href="/ramtech/client/request-service.php" class="rounded-full bg-[#121312] px-5 py-3 text-sm font-bold text-[#fef5e6]">+ New Request</a>
    </div>

    <div class="mt-7 flex flex-wrap gap-2 text-sm font-bold">
      <a class="rounded-full border border-black/15 px-4 py-2" href="/ramtech/client/my-requests.php">All</a>
      <?php foreach(['Pending','In Progress','Ready for Pickup','Completed'] as $s): ?>
        <a class="rounded-full border border-black/15 px-4 py-2" href="/ramtech/client/my-requests.php?status=<?= urlencode($s) ?>"><?= e($s) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="mt-7 space-y-4">
      <?php if ($rows->num_rows === 0): ?>
        <div class="rounded-2xl border border-black/10 bg-white/45 p-8 text-center text-black/45">No matching service requests.</div>
      <?php else: while($r=$rows->fetch_assoc()): ?>
        <div class="rounded-2xl border border-black/10 bg-white/45 p-6">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <div class="font-black"><?= requestCode((int)$r['id']) ?></div>
              <div class="mt-2 text-xl font-bold"><?= e(trim($r['brand'].' '.$r['model'])) ?: e($r['device_type']) ?></div>
              <div class="mt-1 text-sm text-black/50"><?= e($r['service_type']) ?> • Submitted <?= date('M d, Y', strtotime($r['created_at'])) ?></div>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold <?= statusClass($r['status']) ?>"><?= e($r['status']) ?></span>
          </div>
          <div class="mt-5"><a class="font-bold" href="/ramtech/client/request-details.php?id=<?= (int)$r['id'] ?>">View Details →</a></div>
        </div>
      <?php endwhile; endif; ?>
    </div>
  </div>
</main>
</body>
</html>
