<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('admin');

$stats = [
  'active' => 0,
  'repair' => 0,
  'pending' => 0,
  'resolved' => 0,
];

$stats['active'] = (int)$conn->query("SELECT COUNT(*) c FROM service_requests WHERE status NOT IN ('Completed','Cancelled')")->fetch_assoc()['c'];
$stats['repair'] = (int)$conn->query("SELECT COUNT(*) c FROM service_requests WHERE status='In Progress'")->fetch_assoc()['c'];
$stats['pending'] = (int)$conn->query("SELECT COUNT(*) c FROM service_requests WHERE status='Pending'")->fetch_assoc()['c'];
$stats['resolved'] = (int)$conn->query("SELECT COUNT(*) c FROM service_requests WHERE status='Completed' AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())")->fetch_assoc()['c'];

$recent = $conn->query("SELECT sr.*, CONCAT(u.first_name,' ',u.last_name) client_name FROM service_requests sr JOIN users u ON u.id=sr.user_id ORDER BY sr.created_at DESC LIMIT 8");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard | RamTech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
  <div class="mx-auto max-w-7xl">
    <div><p class="text-xs font-black uppercase tracking-[0.3em]">Admin Panel</p><h1 class="mt-2 text-4xl font-black">Dashboard</h1><p class="mt-2 text-black/55">Overview of RamTech service operations.</p></div>

    <div class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-2xl bg-[#121312] p-6 text-white"><div class="text-4xl font-black"><?= $stats['active'] ?></div><div class="mt-2 text-sm text-white/50">Active Requests</div></div>
      <div class="rounded-2xl border border-black/10 bg-white/45 p-6"><div class="text-4xl font-black"><?= $stats['repair'] ?></div><div class="mt-2 text-sm text-black/55">Repair Jobs</div></div>
      <div class="rounded-2xl border border-black/10 bg-white/45 p-6"><div class="text-4xl font-black"><?= $stats['pending'] ?></div><div class="mt-2 text-sm text-black/55">Pending Requests</div></div>
      <div class="rounded-2xl border border-black/10 bg-white/45 p-6"><div class="text-4xl font-black"><?= $stats['resolved'] ?></div><div class="mt-2 text-sm text-black/55">Resolved This Month</div></div>
    </div>

    <div class="mt-8 overflow-hidden rounded-2xl border border-black/10 bg-white/45">
      <div class="flex items-center justify-between border-b border-black/10 p-6"><h2 class="text-xl font-black">Recent Requests</h2><a class="text-sm font-bold" href="/ramtech/admin/requests.php">View all →</a></div>
      <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead class="bg-black/5 text-xs uppercase tracking-wider text-black/45"><tr><th class="p-4">ID</th><th class="p-4">Client</th><th class="p-4">Device</th><th class="p-4">Status</th><th class="p-4"></th></tr></thead>
        <tbody>
        <?php if($recent->num_rows===0): ?><tr><td colspan="5" class="p-8 text-center text-black/45">No requests yet.</td></tr>
        <?php else: while($r=$recent->fetch_assoc()): ?>
          <tr class="border-t border-black/5">
            <td class="p-4 font-bold"><?= requestCode((int)$r['id']) ?></td><td class="p-4"><?= e($r['client_name']) ?></td><td class="p-4"><?= e(trim($r['brand'].' '.$r['model'])) ?: e($r['device_type']) ?></td><td class="p-4"><span class="rounded-full px-3 py-1 text-xs font-bold <?= statusClass($r['status']) ?>"><?= e($r['status']) ?></span></td><td class="p-4"><a class="font-bold" href="/ramtech/admin/request-details.php?id=<?= (int)$r['id'] ?>">Manage →</a></td>
          </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</main>
</body>
</html>
