<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('client');

$uid = (int)$_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $first = trim((string)($_POST['first_name'] ?? ''));
    $last = trim((string)($_POST['last_name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));

    if ($first === '' || $last === '') {
        $error = 'First and last name are required.';
    } elseif (mb_strlen($first) > 100 || mb_strlen($last) > 100) {
        $error = 'Names must be 100 characters or fewer.';
    } elseif (!preg_match("/^[\p{L} .'-]+$/u", $first) || !preg_match("/^[\p{L} .'-]+$/u", $last)) {
        $error = 'Names contain invalid characters.';
    } elseif ($phone !== '' && !preg_match('/^[0-9+() -]{7,50}$/', $phone)) {
        $error = 'Please enter a valid phone number.';
    } else {
        $stmt = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?');

        if (!$stmt) {
            logDatabaseError('profile update prepare', $conn);
            $error = 'Unable to update your profile right now.';
        } else {
            $stmt->bind_param('sssi', $first, $last, $phone, $uid);

            if ($stmt->execute()) {
                $_SESSION['name'] = $first . ' ' . $last;
                $message = 'Profile updated successfully.';
            } else {
                error_log('RamTech DB error [profile update execute]: ' . $stmt->error);
                $error = 'Unable to update your profile right now.';
            }
        }
    }
}

$stmt = $conn->prepare('SELECT first_name, last_name, email, phone FROM users WHERE id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Profile | RamTech</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/client-sidebar.php'; ?>
<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
  <div class="mx-auto max-w-3xl">
    <p class="text-xs font-black uppercase tracking-[0.3em]">Account</p><h1 class="mt-2 text-4xl font-black">Profile</h1>

    <?php if ($message): ?><div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

    <form method="post" novalidate class="mt-8 rounded-2xl border border-black/10 bg-white/45 p-7">
      <?= csrfField() ?>
      <div class="grid gap-5 sm:grid-cols-2">
        <label><span class="text-sm font-bold">First Name</span><input name="first_name" maxlength="100" required value="<?= e($user['first_name']) ?>" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"></label>
        <label><span class="text-sm font-bold">Last Name</span><input name="last_name" maxlength="100" required value="<?= e($user['last_name']) ?>" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"></label>
      </div>
      <label class="mt-5 block"><span class="text-sm font-bold">Email</span><input value="<?= e($user['email']) ?>" disabled class="mt-2 w-full rounded-xl border border-black/15 bg-black/5 px-4 py-3"></label>
      <label class="mt-5 block"><span class="text-sm font-bold">Phone</span><input name="phone" maxlength="50" pattern="[0-9+() -]{7,50}" value="<?= e($user['phone']) ?>" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"></label>
      <button class="mt-7 rounded-full bg-[#121312] px-6 py-3 font-bold text-[#fef5e6]">Save Changes</button>
    </form>
  </div>
</main>
</body>
</html>
