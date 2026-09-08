<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

/*
|--------------------------------------------------------------------------
| LIVE HOMEPAGE STATISTICS
|--------------------------------------------------------------------------
| These values are pulled directly from the current MySQL database.
|
| Active:
|   Every request that is not Completed or Cancelled.
|
| In Progress:
|   Requests whose exact status is "In Progress".
|
| Resolved:
|   Every request whose status is "Completed".
|
| Recent Requests:
|   The 3 newest service requests in the database.
|--------------------------------------------------------------------------
*/

$activeRequests = 0;
$inProgressRequests = 0;
$resolvedRequests = 0;
$totalRequests = 0;

$result = $conn->query("
    SELECT
        COUNT(*) AS total_requests,
        SUM(CASE
            WHEN status NOT IN ('Completed', 'Cancelled') THEN 1
            ELSE 0
        END) AS active_requests,
        SUM(CASE
            WHEN status = 'In Progress' THEN 1
            ELSE 0
        END) AS in_progress_requests,
        SUM(CASE
            WHEN status = 'Completed' THEN 1
            ELSE 0
        END) AS resolved_requests
    FROM service_requests
");

if ($result) {
    $stats = $result->fetch_assoc();

    $totalRequests = (int)($stats['total_requests'] ?? 0);
    $activeRequests = (int)($stats['active_requests'] ?? 0);
    $inProgressRequests = (int)($stats['in_progress_requests'] ?? 0);
    $resolvedRequests = (int)($stats['resolved_requests'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| QUEUE LOAD
|--------------------------------------------------------------------------
| A simple visual percentage based on active requests compared with all
| requests currently stored. If there are no requests, the load is 0%.
|--------------------------------------------------------------------------
*/
$queueLoad = $totalRequests > 0
    ? (int)round(($activeRequests / $totalRequests) * 100)
    : 0;

$recentRequests = $conn->query("
    SELECT
        sr.id,
        sr.device_type,
        sr.brand,
        sr.model,
        sr.status
    FROM service_requests sr
    ORDER BY sr.created_at DESC
    LIMIT 3
");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RamTech | Solutions Made Simple</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/ramtech/assets/css/custom.css">
</head>
<body class="bg-[#fef5e6] text-[#121312]">
<?php include __DIR__ . '/components/public-navbar.php'; ?>

<main>
  <section id="home" class="mx-auto grid min-h-[78vh] max-w-7xl items-center gap-12 px-6 py-20 lg:grid-cols-2">
    <div>
      <p class="mb-5 text-xs font-black uppercase tracking-[0.35em]">RamTech Technology Solutions</p>

      <h1 class="heading-font text-6xl font-black leading-[0.9] sm:text-7xl">
        Technology.<br>Made Simple.
      </h1>

      <p class="mt-7 max-w-xl text-lg leading-8 text-black/65">
        Reliable software and hardware solutions designed to keep your business moving.
      </p>

      <p class="mt-4 max-w-xl leading-7 text-black/60">
        From device support and repair to technology solutions, RamTech provides practical IT services
        with a focus on quality, clarity, and dependable service.
      </p>

      <div class="mt-8 flex flex-wrap gap-3">
        <?php
        $requestLink = '/ramtech/register.php';

        if (!empty($_SESSION['user_id'])) {
            $requestLink = ($_SESSION['role'] ?? '') === 'admin'
                ? '/ramtech/admin/dashboard.php'
                : '/ramtech/client/request-service.php';
        }
        ?>

        <a href="<?= $requestLink ?>"
           class="rounded-full bg-[#121312] px-6 py-3 text-sm font-bold text-[#fef5e6]">
          <?= (($_SESSION['role'] ?? '') === 'admin') ? 'Open Dashboard' : 'Request a Service' ?>
        </a>

        <a href="#contact"
           class="rounded-full border border-black/30 px-6 py-3 text-sm font-bold">
          Contact RamTech
        </a>
      </div>
    </div>

    <!-- LIVE SERVICE DESK -->
    <div class="ram-pattern rounded-3xl border border-black/10 p-8">
      <div class="rounded-3xl bg-[#121312] p-7 text-white shadow-2xl">

        <div class="flex items-center justify-between border-b border-white/10 pb-5">
          <div>
            <p class="text-xs uppercase tracking-[0.25em] text-white/40">
              RamTech Service Desk
            </p>

            <h2 class="mt-2 text-2xl font-bold">
              Service Overview
            </h2>

            <p class="mt-1 text-xs text-white/35">
              Live data from the RamTech service database
            </p>
          </div>

          <div class="flex h-10 w-10 items-center justify-center bg-[#fef5e6] font-black text-[#121312]">
            R
          </div>
        </div>

        <!-- LIVE COUNTERS -->
        <div class="mt-6 grid grid-cols-3 gap-3">
          <div class="rounded-xl bg-white/5 p-4">
            <div class="text-2xl font-black">
              <?= number_format($activeRequests) ?>
            </div>
            <div class="text-xs text-white/50">
              Active
            </div>
          </div>

          <div class="rounded-xl bg-white/5 p-4">
            <div class="text-2xl font-black">
              <?= number_format($inProgressRequests) ?>
            </div>
            <div class="text-xs text-white/50">
              In Progress
            </div>
          </div>

          <div class="rounded-xl bg-white/5 p-4">
            <div class="text-2xl font-black">
              <?= number_format($resolvedRequests) ?>
            </div>
            <div class="text-xs text-white/50">
              Resolved
            </div>
          </div>
        </div>

        <!-- RECENT REQUESTS -->
        <div class="mt-6">
          <div class="mb-3 flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-[0.25em] text-white/35">
              Recent Requests
            </span>

            <span class="text-[10px] uppercase tracking-[0.18em] text-white/30">
              <?= number_format($totalRequests) ?> total
            </span>
          </div>

          <div class="space-y-3 text-sm">

            <?php if ($recentRequests && $recentRequests->num_rows > 0): ?>

              <?php while ($request = $recentRequests->fetch_assoc()): ?>

                <?php
                $deviceName = trim(
                    ($request['brand'] ?? '') . ' ' . ($request['model'] ?? '')
                );

                if ($deviceName === '') {
                    $deviceName = $request['device_type'] ?: 'Device';
                }
                ?>

                <div class="flex items-center justify-between gap-4 rounded-xl bg-white/5 p-4">

                  <div class="min-w-0">
                    <span class="font-bold">
                      <?= requestCode((int)$request['id']) ?>
                    </span>

                    <span class="ml-2 truncate text-white/65">
                      <?= e($deviceName) ?>
                    </span>
                  </div>

                  <span class="shrink-0 text-xs text-white/45">
                    <?= e($request['status']) ?>
                  </span>

                </div>

              <?php endwhile; ?>

            <?php else: ?>

              <div class="rounded-xl bg-white/5 p-5 text-center text-white/40">
                No service requests yet.
              </div>

            <?php endif; ?>

          </div>
        </div>

        <!-- QUEUE LOAD -->
        <div class="mt-6 border-t border-white/10 pt-5">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold uppercase tracking-[0.18em] text-white/40">
              Queue Load
            </span>

            <span class="font-bold">
              <?= $queueLoad ?>%
            </span>
          </div>

          <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/10">
            <div
              class="h-full rounded-full bg-[#fef5e6] transition-all"
              style="width: <?= min(100, max(0, $queueLoad)) ?>%">
            </div>
          </div>

          <p class="mt-2 text-[10px] leading-4 text-white/30">
            Active requests compared with all recorded requests.
          </p>
        </div>

      </div>
    </div>
  </section>

  <section class="bg-[#121312] text-white">
    <div class="mx-auto grid max-w-7xl gap-8 px-6 py-8 md:grid-cols-4">
      <div>
        <div class="font-bold">Quality Service</div>
        <div class="mt-1 text-sm text-white/50">
          Precision and care in every engagement.
        </div>
      </div>

      <div>
        <div class="font-bold">Hardware & Software</div>
        <div class="mt-1 text-sm text-white/50">
          Full-spectrum technology support.
        </div>
      </div>

      <div>
        <div class="font-bold">Simple Solutions</div>
        <div class="mt-1 text-sm text-white/50">
          Clear answers without unnecessary complexity.
        </div>
      </div>

      <div>
        <div class="font-bold">Reliable Support</div>
        <div class="mt-1 text-sm text-white/50">
          Dependable service you can count on.
        </div>
      </div>
    </div>
  </section>

  <section id="services" class="mx-auto max-w-7xl px-6 py-24">
    <p class="text-xs font-black uppercase tracking-[0.35em]">
      Services
    </p>

    <h2 class="mt-3 text-4xl font-black">
      Solutions for the technology you rely on.
    </h2>

    <p class="mt-3 max-w-2xl text-black/60">
      Practical IT services for hardware, software, devices, and everyday technology needs.
    </p>

    <div class="mt-10 grid gap-5 md:grid-cols-2">
      <?php
      $services = [
        [
          'Device Repair',
          'Professional support for device issues, repairs, and maintenance.'
        ],
        [
          'Hardware Solutions',
          'Technology hardware selected and supported around your needs.'
        ],
        [
          'Software Support',
          'Installation, troubleshooting, and configuration support.'
        ],
        [
          'IT Services',
          'Straightforward technology support for day-to-day business needs.'
        ],
      ];

      foreach ($services as $service):
      ?>
        <div class="rounded-2xl border border-black/10 bg-white/35 p-7">
          <h3 class="text-xl font-black">
            <?= e($service[0]) ?>
          </h3>

          <p class="mt-3 text-black/60">
            <?= e($service[1]) ?>
          </p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="system" class="bg-[#121312] text-white">
    <div class="mx-auto grid max-w-7xl gap-12 px-6 py-24 lg:grid-cols-2">

      <div>
        <p class="text-xs font-black uppercase tracking-[0.35em] text-white/50">
          RamTech Service System
        </p>

        <h2 class="mt-4 text-5xl font-black">
          Better visibility.<br>
          Simpler service.
        </h2>

        <p class="mt-6 max-w-xl leading-7 text-white/60">
          Organize device repair requests, track progress, and maintain clear communication
          from request to completion.
        </p>
      </div>

      <div class="rounded-3xl border border-white/10 bg-white/5 p-7">
        <div class="grid grid-cols-3 gap-4 text-center">

          <div class="rounded-xl bg-white/5 p-5">
            <div class="text-3xl font-black">01</div>
            <div class="mt-2 text-xs text-white/50">
              Request
            </div>
          </div>

          <div class="rounded-xl bg-white/5 p-5">
            <div class="text-3xl font-black">02</div>
            <div class="mt-2 text-xs text-white/50">
              Track
            </div>
          </div>

          <div class="rounded-xl bg-white/5 p-5">
            <div class="text-3xl font-black">03</div>
            <div class="mt-2 text-xs text-white/50">
              Resolve
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>

  <section id="about" class="mx-auto max-w-7xl px-6 py-24 text-center">
    <p class="text-xs font-black uppercase tracking-[0.35em]">
      About RamTech
    </p>

    <h2 class="mx-auto mt-4 max-w-3xl text-5xl font-black">
      Technology should work for you.
    </h2>

    <p class="mx-auto mt-6 max-w-3xl leading-8 text-black/60">
      RamTech is an information technology company focused on quality products and
      services across both software and hardware.
    </p>
  </section>

  <section id="contact" class="bg-[#121312] px-6 py-20 text-center text-white">
    <p class="text-xs font-black uppercase tracking-[0.35em] text-white/50">
      RamTech
    </p>

    <h2 class="mt-4 text-5xl font-black">
      Let's make technology simpler.
    </h2>

    <p class="mx-auto mt-5 max-w-xl text-white/60">
      Have a device, software, or IT challenge? Start a service request today.
    </p>

    <a
      href="<?= $requestLink ?>"
      class="mt-7 inline-block rounded-full bg-[#fef5e6] px-6 py-3 font-bold text-[#121312]">
      <?= (($_SESSION['role'] ?? '') === 'admin') ? 'Open Dashboard' : 'Get Started' ?>
    </a>
  </section>
</main>

<footer class="bg-[#121312] px-6 pb-10 text-center text-sm text-white/40">
  © 2026 RamTech. All rights reserved. Solutions Made Simple.
</footer>

<script src="/ramtech/assets/js/app.js"></script>
</body>
</html>
