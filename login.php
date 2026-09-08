<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') header("Location: /ramtech/admin/dashboard.php");
    else header("Location: /ramtech/client/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') header("Location: /ramtech/admin/dashboard.php");
        else header("Location: /ramtech/client/dashboard.php");
        exit;
    }

    $error = "Invalid email or password.";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In | RamTech</title>
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
      <p class="mt-8 leading-7 text-black/60">Sign in to manage service requests and track repairs from submission to completion.</p>
    </div>
  </div>

  <div class="flex items-center justify-center p-6 sm:p-10">
    <form method="post" class="w-full max-w-md rounded-3xl border border-black/10 bg-white/50 p-8 shadow-sm">
      <a href="/ramtech/index.php" class="text-sm font-bold">← Back to home</a>
      <h2 class="mt-8 text-4xl font-black">Welcome back</h2>
      <p class="mt-2 text-black/55">Sign in to manage your service requests.</p>

      <?php if (isset($_GET['registered'])): ?>
        <div data-flash class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">Account created successfully. You can sign in now.</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div>
      <?php endif; ?>

      <label class="mt-7 block"><span class="text-sm font-bold">Email</span><input type="email" name="email" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3 outline-none focus:border-black" required></label>
      <label class="mt-5 block"><span class="text-sm font-bold">Password</span><input type="password" name="password" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3 outline-none focus:border-black" required></label>

      <button class="mt-7 w-full rounded-full bg-[#121312] px-6 py-3 font-bold text-[#fef5e6]">Sign In</button>
      <p class="mt-5 text-center text-sm text-black/55">Don't have an account? <a class="font-bold text-black" href="/ramtech/register.php">Create Account</a></p>
    </form>
  </div>
</div>
<script src="/ramtech/assets/js/app.js"></script>
</body>
</html>
