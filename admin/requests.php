<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('admin');

$status = trim($_GET['status'] ?? '');
$q = trim($_GET['q'] ?? '');

$sql = "SELECT sr.*, CONCAT(u.first_name,' ',u.last_name) client_name, u.email FROM service_requests sr JOIN users u ON u.id=sr.user_id WHERE 1=1";
$params = [];
$types = '';

if ($status !== '') { $sql .= " AND sr.status=?"; $params[]=$status; $types.='s'; }
if ($q !== '') { $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR sr.brand LIKE ? OR sr.model LIKE ? OR sr.id = ?)"; $like="%$q%"; $params=array_merge($params,[$like,$like,$like,$like,(int)$q]); $types.='ssssi'; }
$sql .= " ORDER BY sr.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows=$stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Service Requests | RamTech</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
<div class="mx-auto max-w-7xl">
  <p class="text-xs font-black uppercase tracking-[0.3em]">Service Management</p><h1 class="mt-2 text-4xl font-black">Service Requests</h1>
  <form class="mt-7 flex flex-wrap gap-3">
    <input name="q" value="<?= e($q) ?>" placeholder="Search client, device or ID..." class="min-w-[280px] flex-1 rounded-full border border-black/15 bg-white px-5 py-3">
    <select name="status" class="rounded-full border border-black/15 bg-white px-5 py-3">
      <option value="">All statuses</option>
      <?php foreach(['Pending','Accepted','Device Received','Diagnosing','In Progress','Ready for Pickup','Completed','Cancelled'] as $s): ?><option <?= $status===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?>
    </select>
    <button class="rounded-full bg-[#121312] px-6 py-3 font-bold text-[#fef5e6]">Filter</button>
  </form>

  <div class="mt-7 overflow-hidden rounded-2xl border border-black/10 bg-white/45">
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
      <thead class="bg-black/5 text-xs uppercase tracking-wider text-black/45"><tr><th class="p-4">ID</th><th class="p-4">Client</th><th class="p-4">Device</th><th class="p-4">Service</th><th class="p-4">Status</th><th class="p-4"></th></tr></thead>
      <tbody>
      <?php if($rows->num_rows===0): ?><tr><td colspan="6" class="p-8 text-center text-black/45">No matching requests.</td></tr>
      <?php else: while($r=$rows->fetch_assoc()): ?>
        <tr class="border-t border-black/5">
          <td class="p-4 font-bold"><?= requestCode((int)$r['id']) ?></td><td class="p-4"><?= e($r['client_name']) ?><div class="text-xs text-black/40"><?= e($r['email']) ?></div></td><td class="p-4"><?= e(trim($r['brand'].' '.$r['model'])) ?: e($r['device_type']) ?></td><td class="p-4"><?= e($r['service_type']) ?></td><td class="p-4"><span class="rounded-full px-3 py-1 text-xs font-bold <?= statusClass($r['status']) ?>"><?= e($r['status']) ?></span></td><td class="p-4"><a class="font-bold" href="/ramtech/admin/request-details.php?id=<?= (int)$r['id'] ?>">Manage →</a></td>
        </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table></div>
  </div>
</div>
</main>
</body>
</html>
