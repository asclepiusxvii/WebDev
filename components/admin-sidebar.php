<aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-white/10 bg-[#121312] p-6 text-white lg:block">
  <a href="/ramtech/admin/dashboard.php" class="mb-10 flex items-center gap-3">
    <div class="flex h-10 w-10 items-center justify-center bg-[#fef5e6] text-xl font-black text-[#121312]">R</div>
    <div>
      <div class="font-black">RamTech</div>
      <div class="text-[10px] uppercase tracking-[0.2em] text-white/50">Admin Panel</div>
    </div>
  </a>

  <nav class="space-y-2 text-sm">
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/dashboard.php">Dashboard</a>
    <div class="pt-5 text-[10px] font-bold uppercase tracking-[0.25em] text-white/40">Service Management</div>
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/requests.php">Service Requests</a>
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/requests.php?status=In%20Progress">Active Repairs</a>
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/requests.php?status=Completed">Completed Services</a>

    <div class="pt-5 text-[10px] font-bold uppercase tracking-[0.25em] text-white/40">Customers</div>
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/clients.php">Clients</a>

    <div class="pt-5 text-[10px] font-bold uppercase tracking-[0.25em] text-white/40">System</div>
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/admin/settings.php">System Tools</a>
    <a class="block rounded-xl px-4 py-3 hover:bg-white/10" href="/ramtech/logout.php">Logout</a>
  </nav>
</aside>
