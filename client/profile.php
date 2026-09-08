<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('client');

$uid = (int)$_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($first && $last) {
        $stmt=$conn->prepare("UPDATE users SET first_name=?, last_name=?, phone=? WHERE id=?");
        $stmt->bind_param("sssi",$first,$last,$phone,$uid);
        $stmt->execute();
        $_SESSION['name']=$first.' '.$last;
        $message='Profile updated.';
    }
}

$stmt=$conn->prepare("SELECT first_name,last_name,email,phone FROM users WHERE id=?");
$stmt->bind_param("i",$uid);
$stmt->execute();
$user=$stmt->get_result()->fetch_assoc();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Profile | RamTech</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/client-sidebar.php'; ?>
<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
  <div class="mx-auto max-w-3xl">
    <p class="text-xs font-black uppercase tracking-[0.3em]">Account</p><h1 class="mt-2 text-4xl font-black">Profile</h1>
    <?php if($message): ?><div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= e($message) ?></div><?php endif; ?>
    <form method="post" class="mt-8 rounded-2xl border border-black/10 bg-white/45 p-7">
      <div class="grid gap-5 sm:grid-cols-2">
        <label><span class="text-sm font-bold">First Name</span><input name="first_name" value="<?= e($user['first_name']) ?>" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"></label>
        <label><span class="text-sm font-bold">Last Name</span><input name="last_name" value="<?= e($user['last_name']) ?>" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"></label>
      </div>
      <label class="mt-5 block"><span class="text-sm font-bold">Email</span><input value="<?= e($user['email']) ?>" disabled class="mt-2 w-full rounded-xl border border-black/15 bg-black/5 px-4 py-3"></label>
      <label class="mt-5 block"><span class="text-sm font-bold">Phone</span><input name="phone" value="<?= e($user['phone']) ?>" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"></label>
      <button class="mt-7 rounded-full bg-[#121312] px-6 py-3 font-bold text-[#fef5e6]">Save Changes</button>
    </form>
  </div>
</main>
</body>
</html>
