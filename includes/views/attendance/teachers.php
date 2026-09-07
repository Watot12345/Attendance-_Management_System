<?php $page_title = 'Teacher Attendance'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
          <div>
            <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">Teacher Attendance</h1>
            <p class="text-sm mt-1" style="color:var(--color-text-secondary)">Wednesday, Sep 7, 2026</p>
          </div>
          <button class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Change Date
          </button>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-present">
            <p class="text-sm" style="color:var(--color-text-secondary)">Present</p>
            <p class="stat-number" style="color:var(--color-present)">8</p>
          </div>
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-absent">
            <p class="text-sm" style="color:var(--color-text-secondary)">Absent</p>
            <p class="stat-number" style="color:var(--color-absent)">2</p>
          </div>
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-total">
            <p class="text-sm" style="color:var(--color-text-secondary)">Total Teachers</p>
            <p class="stat-number" style="color:var(--color-teal-500)">10</p>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-card mb-4">
          <div class="p-4 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              <input type="text" class="form-input pl-9" placeholder="Search teacher...">
            </div>
            <select class="form-input form-select w-auto">
              <option>All Departments</option>
              <option>Mathematics</option>
              <option>Science</option>
              <option>English</option>
              <option>Filipino</option>
              <option>Social Studies</option>
            </select>
            <button class="btn btn-secondary btn-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Export Teacher Log
            </button>
          </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-lg shadow-card overflow-x-auto">
          <table class="data-table">
            <thead>
              <tr>
                <th scope="col">Name</th>
                <th scope="col">Employee ID</th>
                <th scope="col">Department</th>
                <th scope="col">Time In</th>
                <th scope="col">Status</th>
                <th scope="col">Method</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="font-medium">Santos, Dr. A.</td>
                <td>EMP-01</td>
                <td>Mathematics</td>
                <td>07:45 AM</td>
                <td><span class="badge badge-present">● Present</span></td>
                <td>RFID</td>
              </tr>
              <tr>
                <td class="font-medium">Cruz, Mr. B.</td>
                <td>EMP-02</td>
                <td>Science</td>
                <td>08:05 AM</td>
                <td><span class="badge badge-present">● Present</span></td>
                <td>QR</td>
              </tr>
              <tr>
                <td class="font-medium">Reyes, Ms. C.</td>
                <td>EMP-03</td>
                <td>English</td>
                <td>—</td>
                <td><span class="badge badge-absent">● Absent</span></td>
                <td>—</td>
              </tr>
              <tr>
                <td class="font-medium">Garcia, Mr. D.</td>
                <td>EMP-04</td>
                <td>Filipino</td>
                <td>07:30 AM</td>
                <td><span class="badge badge-present">● Present</span></td>
                <td>RFID</td>
              </tr>
              <tr>
                <td class="font-medium">Tan, Ms. E.</td>
                <td>EMP-05</td>
                <td>Social Studies</td>
                <td>07:55 AM</td>
                <td><span class="badge badge-present">● Present</span></td>
                <td>QR</td>
              </tr>
            </tbody>
          </table>

          <!-- Note -->
          <div class="px-5 py-3 border-t" style="border-color:var(--color-border)">
            <p class="text-xs" style="color:var(--color-text-muted)">Note: No tardy threshold for teachers.</p>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('teachers');</script>
