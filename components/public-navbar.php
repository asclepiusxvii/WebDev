<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<header class="sticky top-0 z-50 border-b border-black/10 bg-[#fef5e6]/95 backdrop-blur">
  <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
    <a href="/ramtech/index.php" class="flex items-center gap-3">
      <div class="flex h-9 w-9 items-center justify-center bg-[#121312] text-xl font-black text-[#fef5e6]">R</div>
      <div>
        <div class="font-black leading-none">RamTech</div>
        <div class="mt-1 text-[10px] uppercase tracking-[0.28em] text-black/60">Solutions Made Simple</div>
      </div>
    </a>

    <nav class="hidden items-center gap-7 text-sm font-semibold md:flex">
      <a href="/ramtech/index.php#home" class="hover:opacity-60">Home</a>
      <a href="/ramtech/index.php#services" class="hover:opacity-60">Services</a>
      <a href="/ramtech/index.php#system" class="hover:opacity-60">Solutions</a>
      <a href="/ramtech/index.php#about" class="hover:opacity-60">About</a>
      <a href="/ramtech/index.php#contact" class="hover:opacity-60">Contact</a>
    </nav>

    <div class="flex items-center gap-3">
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="<?= ($_SESSION['role'] ?? '') === 'admin' ? '/ramtech/admin/dashboard.php' : '/ramtech/client/dashboard.php' ?>"
           class="rounded-full bg-[#121312] px-5 py-2 text-sm font-bold text-[#fef5e6]">Dashboard</a>
      <?php else: ?>
        <a href="/ramtech/login.php" class="hidden text-sm font-semibold sm:block">Sign In</a>
        <a href="/ramtech/register.php" class="rounded-full bg-[#121312] px-5 py-2 text-sm font-bold text-[#fef5e6]">Create Account</a>
      <?php endif; ?>
    </div>
  </div>
</header>
