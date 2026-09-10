<?php
$currentRole = (string)($_SESSION['role'] ?? 'client');
$isAdminUser = $currentRole === 'admin';
$isStaffUser = $currentRole === 'staff';
$homeLink = $isStaffUser ? '/ramtech/staff/dashboard.php' : '/ramtech/admin/dashboard.php';
$panelName = $isStaffUser ? 'Staff Panel' : 'Admin Panel';
?>
<aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-white/10 bg-[#121312] p-6 text-white lg:block">
  <a href="<?= e($homeLink) ?>" class="mb-10 flex items-center gap-3">
    <div class="flex h-10 w-10 items-center justify-center bg-[#fef5e6] text-xl font-black text-[#121312]">R</div>
    <div>
      <div class="font-black">RamTech</div>
      <div class="text-[10px] uppercase tracking-[0.2em] text-white/50"><?= e($panelName) ?></div>
    </div>
  </a>

  <nav class="space-y-2 text-sm">
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="<?= e($homeLink) ?>">Dashboard</a>

    <div class="pt-5 text-[10px] font-bold uppercase tracking-[0.25em] text-white/40">Service Management</div>
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/requests.php">Service Requests</a>
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/requests.php?status=In%20Progress">Active Repairs</a>
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/requests.php?status=Completed">Completed Services</a>

    <?php if ($isAdminUser): ?>
      <div class="pt-5 text-[10px] font-bold uppercase tracking-[0.25em] text-white/40">Accounts</div>
      <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/clients.php">Clients</a>
      <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/staff.php">Staff Accounts</a>

      <div class="pt-5 text-[10px] font-bold uppercase tracking-[0.25em] text-white/40">System</div>
      <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/settings.php">System Tools</a>
    <?php endif; ?>

    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/logout.php">Logout</a>
  </nav>
</aside>
