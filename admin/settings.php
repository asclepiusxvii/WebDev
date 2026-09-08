<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('admin');

$message = '';
$error = '';

function generateFlushKey(): string {
    $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $numbers = '23456789';
    $characters = [];

    for ($i = 0; $i < 5; $i++) {
        $characters[] = $letters[random_int(0, strlen($letters) - 1)];
    }
    for ($i = 0; $i < 3; $i++) {
        $characters[] = $numbers[random_int(0, strlen($numbers) - 1)];
    }
    for ($i = count($characters) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
    }

    return implode('', $characters);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['flush_confirmation_key'] = generateFlushKey();
}

if (empty($_SESSION['flush_confirmation_key'])) {
    $_SESSION['flush_confirmation_key'] = generateFlushKey();
}

$flushKey = (string)$_SESSION['flush_confirmation_key'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flush_history'])) {
    requireCsrf();
    $confirmation = strtoupper(trim((string)($_POST['confirmation'] ?? '')));

    if (!hash_equals((string)$_SESSION['flush_confirmation_key'], $confirmation)) {
        $error = 'The confirmation key is incorrect. A new key has been generated.';
        $_SESSION['flush_confirmation_key'] = generateFlushKey();
        $flushKey = (string)$_SESSION['flush_confirmation_key'];
    } else {
        $conn->begin_transaction();

        try {
            if (!$conn->query('DELETE FROM service_requests')) {
                throw new RuntimeException('Could not delete service history.');
            }

            $conn->query('ALTER TABLE request_updates AUTO_INCREMENT = 1');
            $conn->query('ALTER TABLE service_requests AUTO_INCREMENT = 1');
            $conn->commit();

            $message = 'Service history was flushed successfully. User accounts were preserved.';
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('RamTech flush history error: ' . $e->getMessage() . ' | ' . $conn->error);
            $error = 'Unable to flush service history right now. Please try again.';
        }

        $_SESSION['flush_confirmation_key'] = generateFlushKey();
        $flushKey = (string)$_SESSION['flush_confirmation_key'];
    }
}

$serviceCount = 0;
$updateCount = 0;

$result = $conn->query('SELECT COUNT(*) AS total FROM service_requests');
if ($result) $serviceCount = (int)$result->fetch_assoc()['total'];

$result = $conn->query('SELECT COUNT(*) AS total FROM request_updates');
if ($result) $updateCount = (int)$result->fetch_assoc()['total'];
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>System Tools | RamTech</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/admin-sidebar.php'; ?>
<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
<div class="mx-auto max-w-5xl">
  <p class="text-xs font-black uppercase tracking-[0.3em]">System</p>
  <h1 class="mt-2 text-4xl font-black">System Tools</h1>
  <p class="mt-2 max-w-2xl text-black/55">Administrative maintenance tools for the RamTech service management system.</p>

  <?php if ($message): ?><div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"><?= e($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

  <div class="mt-8 grid gap-5 sm:grid-cols-2">
    <div class="rounded-2xl border border-black/10 bg-white/45 p-6"><div class="text-4xl font-black"><?= number_format($serviceCount) ?></div><div class="mt-2 text-sm text-black/55">Service request records</div></div>
    <div class="rounded-2xl border border-black/10 bg-white/45 p-6"><div class="text-4xl font-black"><?= number_format($updateCount) ?></div><div class="mt-2 text-sm text-black/55">Request update records</div></div>
  </div>

  <section class="mt-8 overflow-hidden rounded-3xl border border-red-200 bg-white/55">
    <div class="border-b border-red-200 bg-red-50 p-6">
      <p class="text-xs font-black uppercase tracking-[0.25em] text-red-600">Danger Zone</p>
      <h2 class="mt-2 text-2xl font-black text-red-800">Flush History</h2>
      <p class="mt-2 max-w-3xl text-sm leading-6 text-red-700/80">Permanently deletes all service request records and associated request update history. User accounts are preserved.</p>
    </div>

    <form method="post" class="p-6" onsubmit="return confirm('This will permanently delete ALL service request history. This action cannot be undone. Continue?');">
      <?= csrfField() ?>

      <div class="rounded-2xl border border-red-200 bg-red-50/50 p-5">
        <div class="font-bold text-red-800">This action cannot be undone.</div>
        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-red-700/80">
          <li>Deletes every record in <code>service_requests</code>.</li>
          <li>Deletes linked records in <code>request_updates</code>.</li>
          <li>Resets service/request history counters.</li>
          <li>Does not delete client or admin accounts.</li>
        </ul>
      </div>

      <div class="mt-6 max-w-md rounded-2xl border border-black/10 bg-[#121312] p-5 text-white">
        <div class="text-xs font-bold uppercase tracking-[0.22em] text-white/45">Confirmation Key</div>
        <div class="mt-3 select-all font-mono text-3xl font-black tracking-[0.18em]"><?= e($flushKey) ?></div>
        <p class="mt-3 text-xs leading-5 text-white/45">This key changes whenever you refresh this page.</p>
      </div>

      <label class="mt-6 block max-w-md">
        <span class="text-sm font-bold">Enter the confirmation key</span>
        <input type="text" name="confirmation" autocomplete="off" required maxlength="8" pattern="[A-Za-z0-9]{8}"
               placeholder="Enter key"
               class="mt-2 w-full rounded-xl border border-red-200 bg-white px-4 py-3 font-mono uppercase tracking-widest outline-none focus:border-red-500">
      </label>

      <button type="submit" name="flush_history" value="1" class="mt-6 rounded-full bg-red-700 px-6 py-3 font-bold text-white hover:bg-red-800">Flush History</button>
    </form>
  </section>
</div>
</main>
</body>
</html>
