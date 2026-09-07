<?php $page_title = 'Excuse Slips'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
          <div>
            <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">Excuse Slips</h1>
            <p class="text-sm mt-1" style="color:var(--color-text-secondary)">Manage digital excuse slips and review absence requests</p>
          </div>
        </div>

        <!-- Top Menu Panel (Tabs) -->
        <div class="bg-white rounded-lg p-1.5 shadow-card border border-gray-100 mb-6 inline-flex gap-1">
          <button id="tab-btn-review" type="button"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-semibold transition-all"
                  style="background:var(--color-teal-500); color:#ffffff;"
                  onclick="switchExcuseTab('review')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Review Queue</span>
            <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold" style="background:rgba(255,255,255,0.25); color:#ffffff;">3</span>
          </button>
          <button id="tab-btn-submit" type="button"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-medium transition-all"
                  style="background:transparent; color:var(--color-text-secondary);"
                  onclick="switchExcuseTab('submit')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Submit Excuse Slip</span>
          </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 1: REVIEW QUEUE                                     -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="excuse-panel-review">
          <!-- Filters -->
          <div class="bg-white rounded-lg shadow-card mb-4">
            <div class="p-4 flex flex-wrap items-center gap-3">
              <select class="form-input form-select w-auto">
                <option>Pending</option>
                <option>All</option>
                <option>Approved</option>
                <option>Rejected</option>
              </select>
              <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" class="form-input pl-9" placeholder="Search student...">
              </div>
              <input type="date" class="form-input w-auto">
            </div>
          </div>

          <!-- Table -->
          <div class="bg-white rounded-lg shadow-card overflow-x-auto">
            <table class="data-table">
              <thead>
                <tr>
                  <th scope="col">Student</th>
                  <th scope="col">Dates</th>
                  <th scope="col">Submitted By</th>
                  <th scope="col">Submitted</th>
                  <th scope="col">Status</th>
                  <th scope="col" class="text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr class="cursor-pointer hover:bg-slate-50 transition-colors" onclick="openReviewPanel('slip-1')">
                  <td class="font-medium" style="color:var(--color-text-primary)">Dela Cruz, Juan</td>
                  <td>Sep 5–6, 2026</td>
                  <td>Parent</td>
                  <td>Sep 6, 2026</td>
                  <td><span class="badge badge-pending">⏳ Pending</span></td>
                  <td class="text-right">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); openReviewPanel('slip-1')">Review</button>
                  </td>
                </tr>
                <tr class="cursor-pointer hover:bg-slate-50 transition-colors" onclick="openReviewPanel('slip-2')">
                  <td class="font-medium" style="color:var(--color-text-primary)">Santos, Maria</td>
                  <td>Sep 3, 2026</td>
                  <td>Teacher</td>
                  <td>Sep 4, 2026</td>
                  <td><span class="badge badge-pending">⏳ Pending</span></td>
                  <td class="text-right">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); openReviewPanel('slip-2')">Review</button>
                  </td>
                </tr>
                <tr class="cursor-pointer hover:bg-slate-50 transition-colors" onclick="openReviewPanel('slip-3')">
                  <td class="font-medium" style="color:var(--color-text-primary)">Reyes, Pedro</td>
                  <td>Aug 28–30, 2026</td>
                  <td>Parent</td>
                  <td>Sep 1, 2026</td>
                  <td><span class="badge badge-approved">✓ Approved</span></td>
                  <td class="text-right">
                    <a href="/Attendance _Management_System/includes/views/excuses/detail.php" class="btn btn-secondary btn-sm" onclick="event.stopPropagation()">View</a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 2: SUBMIT EXCUSE SLIP                               -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="excuse-panel-submit" class="hidden flex justify-center">
          <div class="w-full max-w-xl">
            <div class="bg-white rounded-lg p-6 shadow-card">
              <div class="mb-5 pb-4 border-b border-gray-100">
                <h2 class="text-lg font-bold" style="color:var(--color-text-primary)">Submit Excuse Slip</h2>
                <p class="text-sm mt-0.5" style="color:var(--color-text-secondary)">Fill in absence details and attach supporting documents</p>
              </div>

              <form id="excuse-submit-form" onsubmit="handleExcuseSubmit(event)">
                <!-- Student -->
                <div class="mb-4">
                  <label for="excuse-student" class="form-label">Student</label>
                  <select id="excuse-student" class="form-input form-select" required>
                    <option value="">Select or search student...</option>
                    <option>Dela Cruz, Juan · BCP-001</option>
                    <option>Santos, Maria · BCP-002</option>
                    <option>Reyes, Pedro · BCP-003</option>
                  </select>
                </div>

                <!-- Absence Period -->
                <div class="mb-4">
                  <label class="form-label">Absence Period</label>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label for="excuse-from" class="text-xs font-medium" style="color:var(--color-text-secondary)">From Date</label>
                      <input type="date" id="excuse-from" class="form-input mt-1" value="2026-09-07" required>
                    </div>
                    <div>
                      <label for="excuse-to" class="text-xs font-medium" style="color:var(--color-text-secondary)">To Date</label>
                      <input type="date" id="excuse-to" class="form-input mt-1" value="2026-09-07" required>
                    </div>
                  </div>
                </div>

                <!-- Reason -->
                <div class="mb-4">
                  <label for="excuse-reason" class="form-label">Reason for Absence</label>
                  <textarea id="excuse-reason" class="form-input" rows="4" placeholder="Please describe the reason for the absence in detail..." required></textarea>
                </div>

                <!-- File Upload -->
                <div class="mb-6">
                  <label class="form-label">Supporting Document (optional)</label>
                  <div class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer hover:bg-gray-50 transition-colors" style="border-color:var(--color-border)" id="file-drop-zone">
                    <svg class="w-8 h-8 mx-auto mb-2" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <p class="text-sm font-medium" style="color:var(--color-text-primary)">Attach file — PDF, JPG, PNG</p>
                    <p class="text-xs mt-1" style="color:var(--color-text-muted)">Maximum file size: 5MB</p>
                    <input type="file" class="hidden" id="excuse-file" accept=".pdf,.jpg,.jpeg,.png">
                  </div>
                  <div id="file-preview" class="hidden mt-2 p-3 rounded-lg flex items-center gap-3" style="background:var(--color-teal-100)">
                    <span class="text-sm font-medium" style="color:var(--color-teal-500)" id="file-name"></span>
                    <button type="button" class="ml-auto text-sm font-semibold inline-flex items-center gap-1" style="color:var(--color-absent)" onclick="removeFile()">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                      Remove
                    </button>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-2">
                  <button type="button" class="btn btn-secondary" onclick="switchExcuseTab('review')">Cancel</button>
                  <button type="submit" class="btn btn-primary">Submit Excuse Slip</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Slide-in Review Panel -->
  <div id="slide-panel">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold" style="color:var(--color-text-primary)">Review Excuse Slip</h3>
      <button type="button" onclick="closeReviewPanel()" class="p-1.5 rounded-md hover:bg-gray-100" aria-label="Close panel">
        <svg class="w-5 h-5" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Student Info -->
    <div class="mb-4">
      <h4 class="font-semibold" style="color:var(--color-text-primary)">Juan Dela Cruz</h4>
      <p class="text-sm" style="color:var(--color-text-secondary)">Sep 5–6, 2026</p>
      <p class="text-sm mt-1" style="color:var(--color-text-muted)">Submitted by: Parent (Maria Dela Cruz)</p>
    </div>

    <hr class="my-4" style="border-color:var(--color-border)">

    <!-- Reason -->
    <div class="mb-4">
      <p class="text-sm font-medium mb-1" style="color:var(--color-text-secondary)">Reason:</p>
      <p class="text-sm" style="color:var(--color-text-primary)">
        Student had high fever. Medical certificate attached.
      </p>
    </div>

    <!-- Attachment -->
    <div class="mb-4">
      <button type="button" class="btn btn-secondary btn-sm w-full inline-flex items-center justify-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        View Attachment
      </button>
    </div>

    <!-- Affected Records -->
    <div class="p-3 rounded-lg mb-4" style="background:var(--color-surface)">
      <p class="text-sm" style="color:var(--color-text-secondary)">
        <strong>Affected records:</strong> 2 absences (Sep 5, 6)
      </p>
    </div>

    <!-- Review Notes -->
    <div class="mb-4">
      <label for="review-notes" class="form-label">Review notes:</label>
      <textarea id="review-notes" class="form-input" rows="3" placeholder="Add notes (optional)..."></textarea>
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
      <button type="button" class="btn btn-danger flex-1 justify-center inline-flex items-center gap-1.5" onclick="closeReviewPanel(); APP.showToast('Excuse slip rejected.', 'info')">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        Reject
      </button>
      <button type="button" class="btn btn-primary flex-1 justify-center inline-flex items-center gap-1.5" onclick="closeReviewPanel(); APP.showToast('Excuse slip approved!', 'success')">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Approve
      </button>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php $page_js = '<script src="/Attendance _Management_System/assets/js/excuses.js"></script>'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('excuse-slips');</script>
