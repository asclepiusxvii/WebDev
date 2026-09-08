<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (!empty($_SESSION['user_id'])) {
    header("Location: /ramtech/client/dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($first === '' || $last === '' || $email === '' || $password === '') $errors[] = "Please complete all required fields.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (!$errors) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "An account with that email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'client';
            $stmt = $conn->prepare("INSERT INTO users (first_name,last_name,email,phone,password,role) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssssss", $first, $last, $email, $phone, $hash, $role);
            if ($stmt->execute()) {
                header("Location: /ramtech/login.php?registered=1");
                exit;
            } else {
                $errors[] = "Unable to create your account.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Account | RamTech</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/ramtech/assets/css/custom.css">
</head>
<body class="min-h-screen bg-[#fef5e6] text-[#121312]">
<div class="grid min-h-screen lg:grid-cols-2">
  <div class="ram-pattern hidden items-center justify-center p-12 lg:flex">
    <div class="max-w-md">
      <div class="mb-8 flex h-20 w-20 items-center justify-center bg-[#121312] text-5xl font-black text-[#fef5e6]">R</div>
      <h1 class="text-5xl font-black">RamTech</h1>
      <p class="mt-3 text-lg text-black/60">Solutions Made Simple.</p>
      <p class="mt-8 leading-7 text-black/60">Create an account to submit repair requests, track service progress, and keep your device service history in one place.</p>
    </div>
  </div>

  <div class="flex items-center justify-center p-6 sm:p-10">
    <form method="post" class="w-full max-w-xl rounded-3xl border border-black/10 bg-white/50 p-8 shadow-sm">
      <a href="/ramtech/index.php" class="text-sm font-bold">← Back to home</a>
      <h2 class="mt-8 text-4xl font-black">Create your account</h2>
      <p class="mt-2 text-black/55">Start managing your RamTech service requests.</p>

      <?php if ($errors): ?>
      <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <?= implode("<br>", array_map('e', $errors)) ?>
      </div>
      <?php endif; ?>

      <div class="mt-7 grid gap-5 sm:grid-cols-2">
        <label class="block"><span class="text-sm font-bold">First Name *</span><input name="first_name" value="<?= e($_POST['first_name'] ?? '') ?>" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3 outline-none focus:border-black" required></label>
        <label class="block"><span class="text-sm font-bold">Last Name *</span><input name="last_name" value="<?= e($_POST['last_name'] ?? '') ?>" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3 outline-none focus:border-black" required></label>
      </div>
      <label class="mt-5 block"><span class="text-sm font-bold">Email Address *</span><input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3 outline-none focus:border-black" required></label>
      <label class="mt-5 block"><span class="text-sm font-bold">Phone Number</span><input name="phone" value="<?= e($_POST['phone'] ?? '') ?>" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3 outline-none focus:border-black"></label>
      <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <label class="block"><span class="text-sm font-bold">Password *</span><input type="password" name="password" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3 outline-none focus:border-black" required></label>
        <label class="block"><span class="text-sm font-bold">Confirm Password *</span><input type="password" name="confirm_password" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3 outline-none focus:border-black" required></label>
      </div>

      <button class="mt-7 w-full rounded-full bg-[#121312] px-6 py-3 font-bold text-[#fef5e6]">Create Account</button>
      <p class="mt-5 text-center text-sm text-black/55">Already registered? <a class="font-bold text-black" href="/ramtech/login.php">Sign In</a></p>
    </form>
  </div>
</div>
</body>
</html>
