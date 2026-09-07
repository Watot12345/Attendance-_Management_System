<?php $page_title = 'Excuse Slip Detail'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6 flex items-start justify-center" style="background:var(--color-surface)">
        <div class="w-full max-w-xl">
          <div class="flex items-center gap-2 mb-6">
            <a href="/Attendance _Management_System/includes/views/dashboard/excuse-slips.php" class="text-sm" style="color:var(--color-teal-500)">← Back to Excuse Slips</a>
          </div>

          <div class="bg-white rounded-lg shadow-card">
            <!-- Header -->
            <div class="px-6 py-4 border-b flex items-center justify-between" style="border-color:var(--color-border)">
              <h1 class="text-xl font-bold" style="color:var(--color-text-primary)">Excuse Slip Detail</h1>
              <span class="badge badge-approved">✓ Approved</span>
            </div>

            <div class="p-6 space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-xs uppercase font-semibold" style="color:var(--color-text-muted)">Student</p>
                  <p class="text-sm font-medium mt-0.5">Reyes, Pedro</p>
                </div>
                <div>
                  <p class="text-xs uppercase font-semibold" style="color:var(--color-text-muted)">Student ID</p>
                  <p class="text-sm font-medium mt-0.5">BCP-003</p>
                </div>
                <div>
                  <p class="text-xs uppercase font-semibold" style="color:var(--color-text-muted)">Absence Period</p>
                  <p class="text-sm font-medium mt-0.5">Aug 28–30, 2026</p>
                </div>
                <div>
                  <p class="text-xs uppercase font-semibold" style="color:var(--color-text-muted)">Submitted By</p>
                  <p class="text-sm font-medium mt-0.5">Parent (Maria Reyes)</p>
                </div>
                <div>
                  <p class="text-xs uppercase font-semibold" style="color:var(--color-text-muted)">Date Submitted</p>
                  <p class="text-sm font-medium mt-0.5">Sep 1, 2026</p>
                </div>
                <div>
                  <p class="text-xs uppercase font-semibold" style="color:var(--color-text-muted)">Reviewed By</p>
                  <p class="text-sm font-medium mt-0.5">Admin Santos</p>
                </div>
              </div>

              <hr style="border-color:var(--color-border)">

              <div>
                <p class="text-xs uppercase font-semibold mb-1" style="color:var(--color-text-muted)">Reason</p>
                <p class="text-sm">Family emergency — grandparent hospitalized. Supporting documents submitted.</p>
              </div>

              <div>
                <p class="text-xs uppercase font-semibold mb-1" style="color:var(--color-text-muted)">Supporting Document</p>
                <button class="btn btn-secondary btn-sm">📄 View Attachment (medical_cert.pdf)</button>
              </div>

              <div>
                <p class="text-xs uppercase font-semibold mb-1" style="color:var(--color-text-muted)">Affected Records</p>
                <p class="text-sm">3 absence records (Aug 28, 29, 30) updated to <span class="badge badge-excused">● Excused</span></p>
              </div>

              <div>
                <p class="text-xs uppercase font-semibold mb-1" style="color:var(--color-text-muted)">Review Notes</p>
                <p class="text-sm">Documents verified. Approved — Sep 2, 2026.</p>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('excuse-review');</script>
