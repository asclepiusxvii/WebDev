<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('admin');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 100) {
        $errors[] = 'First name must be between 2 and 100 characters.';
    }
    if (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 100) {
        $errors[] = 'Last name must be between 2 and 100 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone !== '' && mb_strlen($phone) > 50) {
        $errors[] = 'Phone number is too long.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');

        if (!$check) {
            logDatabaseError('staff email check prepare', $conn);
            $errors[] = 'Unable to create the staff account right now.';
        } else {
            $check->bind_param('s', $email);

            if (!$check->execute()) {
                error_log('RamTech staff email check error: ' . $check->error);
                $errors[] = 'Unable to create the staff account right now.';
            } elseif ($check->get_result()->num_rows > 0) {
                $errors[] = 'That email address is already registered.';
            }
        }
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'staff';

        $stmt = $conn->prepare(
            'INSERT INTO users (first_name, last_name, email, phone, password, role)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        if (!$stmt) {
            logDatabaseError('staff insert prepare', $conn);
            $errors[] = 'Unable to create the staff account right now.';
        } else {
            $stmt->bind_param('ssssss', $firstName, $lastName, $email, $phone, $passwordHash, $role);

            if ($stmt->execute()) {
                $success = 'Staff account created successfully.';
                $_POST = [];
            } else {
                error_log('RamTech staff insert error: ' . $stmt->error);
                $errors[] = 'Unable to create the staff account right now.';
            }
        }
    }
}

$staffRows = $conn->query(
    "SELECT id, first_name, last_name, email, phone, created_at
     FROM users
     WHERE role = 'staff'
     ORDER BY created_at DESC"
);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Staff Accounts | RamTech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/admin-sidebar.php'; ?>

<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
  <div class="mx-auto max-w-6xl">
    <p class="text-xs font-black uppercase tracking-[0.3em]">Account Management</p>
    <h1 class="mt-2 text-4xl font-black">Staff Accounts</h1>
    <p class="mt-3 max-w-2xl text-sm leading-6 text-black/55">
      Create employee accounts for staff who need to manage RamTech service requests.
    </p>

    <?php if ($success): ?>
      <div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <ul class="list-disc space-y-1 pl-5">
          <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="mt-8 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
      <section class="rounded-2xl border border-black/10 bg-white/45 p-6">
        <h2 class="text-xl font-black">Register Staff / Employee</h2>

        <form method="post" class="mt-6 space-y-4">
          <?= csrfField() ?>

          <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
              <span class="text-sm font-bold">First Name</span>
              <input type="text" name="first_name" maxlength="100" required
                     value="<?= e($_POST['first_name'] ?? '') ?>"
                     class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3">
            </label>
            <label class="block">
              <span class="text-sm font-bold">Last Name</span>
              <input type="text" name="last_name" maxlength="100" required
                     value="<?= e($_POST['last_name'] ?? '') ?>"
                     class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3">
            </label>
          </div>

          <label class="block">
            <span class="text-sm font-bold">Email</span>
            <input type="email" name="email" maxlength="190" required
                   value="<?= e($_POST['email'] ?? '') ?>"
                   class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3">
          </label>

          <label class="block">
            <span class="text-sm font-bold">Phone</span>
            <input type="text" name="phone" maxlength="50"
                   value="<?= e($_POST['phone'] ?? '') ?>"
                   class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3">
          </label>

          <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
              <span class="text-sm font-bold">Temporary Password</span>
              <input type="password" name="password" minlength="8" required
                     class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3">
            </label>
            <label class="block">
              <span class="text-sm font-bold">Confirm Password</span>
              <input type="password" name="confirm_password" minlength="8" required
                     class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3">
            </label>
          </div>

          <button class="rounded-full bg-[#121312] px-6 py-3 text-sm font-bold text-white">
            Create Staff Account
          </button>
        </form>
      </section>

      <section class="overflow-hidden rounded-2xl border border-black/10 bg-white/45">
        <div class="border-b border-black/10 p-6">
          <h2 class="text-xl font-black">Registered Staff</h2>
          <p class="mt-1 text-sm text-black/45">Employee accounts currently registered in RamTech.</p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[650px] text-left text-sm">
            <thead class="bg-black/5 text-xs uppercase tracking-wider text-black/45">
              <tr>
                <th class="p-4">Employee</th>
                <th class="p-4">Contact</th>
                <th class="p-4">Role</th>
                <th class="p-4">Created</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$staffRows || $staffRows->num_rows === 0): ?>
                <tr><td colspan="4" class="p-8 text-center text-black/45">No staff accounts yet.</td></tr>
              <?php else: while ($staff = $staffRows->fetch_assoc()): ?>
                <tr class="border-t border-black/5">
                  <td class="p-4 font-bold"><?= e($staff['first_name'] . ' ' . $staff['last_name']) ?></td>
                  <td class="p-4"><?= e($staff['email']) ?><div class="text-xs text-black/40"><?= e($staff['phone']) ?></div></td>
                  <td class="p-4"><span class="rounded-full bg-black px-3 py-1 text-xs font-bold text-white">Staff</span></td>
                  <td class="p-4"><?= date('M d, Y', strtotime($staff['created_at'])) ?></td>
                </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</main>
</body>
</html>
