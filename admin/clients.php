<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('admin');

$rows=$conn->query("SELECT u.id,u.first_name,u.last_name,u.email,u.phone,u.created_at,COUNT(sr.id) request_count FROM users u LEFT JOIN service_requests sr ON sr.user_id=u.id WHERE u.role='client' GROUP BY u.id ORDER BY u.created_at DESC");
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Clients | RamTech</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
<div class="mx-auto max-w-6xl">
  <p class="text-xs font-black uppercase tracking-[0.3em]">Customers</p><h1 class="mt-2 text-4xl font-black">Clients</h1>
  <div class="mt-8 overflow-hidden rounded-2xl border border-black/10 bg-white/45"><div class="overflow-x-auto"><table class="w-full text-left text-sm">
    <thead class="bg-black/5 text-xs uppercase tracking-wider text-black/45"><tr><th class="p-4">Client</th><th class="p-4">Contact</th><th class="p-4">Requests</th><th class="p-4">Joined</th></tr></thead>
    <tbody>
    <?php if($rows->num_rows===0): ?><tr><td colspan="4" class="p-8 text-center text-black/45">No clients yet.</td></tr>
    <?php else: while($r=$rows->fetch_assoc()): ?>
      <tr class="border-t border-black/5"><td class="p-4 font-bold"><?= e($r['first_name'].' '.$r['last_name']) ?></td><td class="p-4"><?= e($r['email']) ?><div class="text-xs text-black/40"><?= e($r['phone']) ?></div></td><td class="p-4"><?= (int)$r['request_count'] ?></td><td class="p-4"><?= date('M d, Y',strtotime($r['created_at'])) ?></td></tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table></div></div>
</div>
</main>
</body>
</html>
