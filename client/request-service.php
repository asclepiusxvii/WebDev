<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/service-functions.php';
requireRole('client');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $service = trim((string)($_POST['service_type'] ?? ''));
    $device = trim((string)($_POST['device_type'] ?? ''));
    $brand = trim((string)($_POST['brand'] ?? ''));
    $model = trim((string)($_POST['model'] ?? ''));
    $issue = trim((string)($_POST['issue_description'] ?? ''));
    $method = trim((string)($_POST['service_method'] ?? ''));

    if (!in_array($service, allowedServiceTypes(), true)) {
        $error = 'Please choose a valid service type.';
    } elseif (!in_array($device, allowedDeviceTypes(), true)) {
        $error = 'Please choose a valid device type.';
    } elseif (!in_array($method, allowedServiceMethods(), true)) {
        $error = 'Please choose a valid service method.';
    } elseif (mb_strlen($brand) > 100 || mb_strlen($model) > 150) {
        $error = 'Brand or model is too long.';
    } elseif (mb_strlen($issue) < 10 || mb_strlen($issue) > 2000) {
        $error = 'Issue description must be between 10 and 2000 characters.';
    } else {
        $uid = (int)$_SESSION['user_id'];
        $status = 'Pending';

        $stmt = $conn->prepare(
            'INSERT INTO service_requests
             (user_id, service_type, device_type, brand, model, issue_description, service_method, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        if (!$stmt) {
            logDatabaseError('create service request prepare', $conn);
            $error = 'Unable to submit your request right now.';
        } else {
            $stmt->bind_param('isssssss', $uid, $service, $device, $brand, $model, $issue, $method, $status);

            if ($stmt->execute()) {
                $rid = (int)$stmt->insert_id;
                addRequestUpdate($conn, $rid, $status, '', 'Service request submitted by client.', $uid);
                header('Location: /ramtech/client/request-details.php?id=' . $rid . '&created=1');
                exit;
            }

            error_log('RamTech DB error [create service request execute]: ' . $stmt->error);
            $error = 'Unable to submit your request right now.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Request a Service | RamTech</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/ramtech/assets/css/custom.css">
</head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/../components/client-sidebar.php'; ?>
<main class="min-h-screen p-6 lg:ml-64 lg:p-10">
  <div class="mx-auto max-w-4xl">
    <p class="text-xs font-black uppercase tracking-[0.3em]">Services</p>
    <h1 class="mt-2 text-4xl font-black">Request a Service</h1>
    <p class="mt-2 text-black/55">Tell us about your device and what you need help with.</p>

    <?php if ($error): ?>
      <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate class="mt-8 rounded-3xl border border-black/10 bg-white/45 p-7">
      <?= csrfField() ?>
      <h2 class="text-xl font-black">Device Information</h2>

      <div class="mt-6 grid gap-5 md:grid-cols-2">
        <label class="block">
          <span class="text-sm font-bold">Service Type *</span>
          <select name="service_type" required class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3">
            <option value="">Choose a service</option>
            <?php foreach (allowedServiceTypes() as $option): ?>
              <option value="<?= e($option) ?>" <?= ($_POST['service_type'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="block">
          <span class="text-sm font-bold">Device Type *</span>
          <select name="device_type" required class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3">
            <option value="">Choose device</option>
            <?php foreach (allowedDeviceTypes() as $option): ?>
              <option value="<?= e($option) ?>" <?= ($_POST['device_type'] ?? '') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="block">
          <span class="text-sm font-bold">Brand</span>
          <input name="brand" maxlength="100" value="<?= e($_POST['brand'] ?? '') ?>"
                 class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3" placeholder="e.g. Dell">
        </label>

        <label class="block">
          <span class="text-sm font-bold">Model</span>
          <input name="model" maxlength="150" value="<?= e($_POST['model'] ?? '') ?>"
                 class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3" placeholder="e.g. Inspiron 15">
        </label>
      </div>

      <label class="mt-5 block">
        <span class="text-sm font-bold">Issue Description *</span>
        <textarea name="issue_description" minlength="10" maxlength="2000" rows="6" required
                  class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3"
                  placeholder="Describe the problem, symptoms, or service you need..."><?= e($_POST['issue_description'] ?? '') ?></textarea>
      </label>

      <div class="mt-5">
        <div class="text-sm font-bold">Preferred Service *</div>
        <div class="mt-3 flex flex-wrap gap-4">
          <?php foreach (allowedServiceMethods() as $option): ?>
            <label class="rounded-xl border border-black/10 bg-white px-4 py-3">
              <input type="radio" name="service_method" value="<?= e($option) ?>" required
                     <?= ($_POST['service_method'] ?? '') === $option ? 'checked' : '' ?>>
              <span class="ml-2"><?= e($option) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="mt-7 flex gap-3">
        <button class="rounded-full bg-[#121312] px-6 py-3 font-bold text-[#fef5e6]">Submit Request</button>
        <a href="/ramtech/client/dashboard.php" class="rounded-full border border-black/20 px-6 py-3 font-bold">Cancel</a>
      </div>
    </form>
  </div>
</main>
</body>
</html>
