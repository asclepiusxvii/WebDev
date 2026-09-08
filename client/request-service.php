<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
requireRole('client');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = trim($_POST['service_type'] ?? '');
    $device = trim($_POST['device_type'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $issue = trim($_POST['issue_description'] ?? '');
    $method = trim($_POST['service_method'] ?? '');

    if ($service === '' || $device === '' || $issue === '' || $method === '') {
        $error = "Please complete all required fields.";
    } else {
        $uid = (int)$_SESSION['user_id'];
        $status = 'Pending';
        $stmt = $conn->prepare("INSERT INTO service_requests (user_id,service_type,device_type,brand,model,issue_description,service_method,status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("isssssss", $uid,$service,$device,$brand,$model,$issue,$method,$status);
        if ($stmt->execute()) {
            $rid = $stmt->insert_id;
            $note = 'Service request submitted by client.';
            $upd = $conn->prepare("INSERT INTO request_updates (request_id,status,notes,updated_by) VALUES (?,?,?,?)");
            $upd->bind_param("issi", $rid,$status,$note,$uid);
            $upd->execute();
            header("Location: /ramtech/client/request-details.php?id=".$rid."&created=1");
            exit;
        } else {
            $error = "Unable to submit the request.";
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

    <?php if ($error): ?><div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><?= e($error) ?></div><?php endif; ?>

    <form method="post" class="mt-8 rounded-3xl border border-black/10 bg-white/45 p-7">
      <h2 class="text-xl font-black">Device Information</h2>

      <div class="mt-6 grid gap-5 md:grid-cols-2">
        <label class="block"><span class="text-sm font-bold">Service Type *</span>
          <select name="service_type" required class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3">
            <option value="">Choose a service</option>
            <option>Device Repair</option><option>Hardware Solutions</option><option>Software Support</option><option>IT Services</option>
          </select>
        </label>
        <label class="block"><span class="text-sm font-bold">Device Type *</span>
          <select name="device_type" required class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3">
            <option value="">Choose device</option>
            <option>Laptop</option><option>Desktop PC</option><option>Smartphone</option><option>Tablet</option><option>Printer</option><option>Other</option>
          </select>
        </label>
        <label class="block"><span class="text-sm font-bold">Brand</span><input name="brand" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3" placeholder="e.g. Dell"></label>
        <label class="block"><span class="text-sm font-bold">Model</span><input name="model" class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3" placeholder="e.g. Inspiron 15"></label>
      </div>

      <label class="mt-5 block"><span class="text-sm font-bold">Issue Description *</span>
        <textarea name="issue_description" rows="6" required class="mt-2 w-full rounded-xl border border-black/15 bg-white px-4 py-3" placeholder="Describe the problem, symptoms, or service you need..."></textarea>
      </label>

      <div class="mt-5">
        <div class="text-sm font-bold">Preferred Service *</div>
        <div class="mt-3 flex flex-wrap gap-4">
          <label class="rounded-xl border border-black/10 bg-white px-4 py-3"><input type="radio" name="service_method" value="Drop-off" required> <span class="ml-2">Drop-off</span></label>
          <label class="rounded-xl border border-black/10 bg-white px-4 py-3"><input type="radio" name="service_method" value="On-site" required> <span class="ml-2">On-site</span></label>
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
