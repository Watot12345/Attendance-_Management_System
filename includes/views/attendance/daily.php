<?php $page_title = 'Daily Attendance'; ?>
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
            <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">Daily Attendance</h1>
            <p class="text-sm mt-1" style="color:var(--color-text-secondary)">Wednesday, Sep 7, 2026</p>
          </div>
          <div class="flex items-center gap-2">
            <button class="btn btn-secondary btn-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              Change Date
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="APP.openManualEntryModal()">+ Manual Entry</button>
          </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-present">
            <p class="text-sm" style="color:var(--color-text-secondary)">Present</p>
            <p class="stat-number" style="color:var(--color-present)">247</p>
          </div>
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-tardy">
            <p class="text-sm" style="color:var(--color-text-secondary)">Tardy</p>
            <p class="stat-number" style="color:var(--color-tardy)">18</p>
          </div>
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-absent">
            <p class="text-sm" style="color:var(--color-text-secondary)">Absent</p>
            <p class="stat-number" style="color:var(--color-absent)">12</p>
          </div>
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-total">
            <p class="text-sm" style="color:var(--color-text-secondary)">Total Students</p>
            <p class="stat-number" style="color:var(--color-teal-500)">277</p>
          </div>
        </div>

        <!-- Tab Bar: Daily / Tardy Log / Absence Log -->
        <div id="attendance-tabs">
          <div class="flex gap-0 border-b mb-4" style="border-color:var(--color-border)">
            <button data-tab-btn="daily" class="px-4 py-2.5 text-sm border-b-2 font-semibold" style="border-color:var(--color-teal-500); color:var(--color-text-primary)" onclick="APP.switchTab('attendance-tabs','daily')">Daily</button>
            <button data-tab-btn="tardy" class="px-4 py-2.5 text-sm" style="border-color:transparent; color:var(--color-text-secondary)" onclick="APP.switchTab('attendance-tabs','tardy')">Tardy Log</button>
            <button data-tab-btn="absence" class="px-4 py-2.5 text-sm" style="border-color:transparent; color:var(--color-text-secondary)" onclick="APP.switchTab('attendance-tabs','absence')">Absence Log</button>
          </div>

          <!-- ═══ DAILY TAB ═══ -->
          <div data-tab-panel="daily">
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-card mb-4">
              <div class="p-4 flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                  <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                  <input type="text" class="form-input pl-9" placeholder="Search student name or ID...">
                </div>
                <select class="form-input form-select w-auto">
                  <option>All Grades</option>
                  <option>Grade 7</option>
                  <option>Grade 8</option>
                  <option>Grade 9</option>
                  <option>Grade 10</option>
                </select>
                <select class="form-input form-select w-auto">
                  <option>All Sections</option>
                  <option>Section A</option>
                  <option>Section B</option>
                  <option>Section C</option>
                </select>
                <select class="form-input form-select w-auto">
                  <option>All Status</option>
                  <option>Present</option>
                  <option>Tardy</option>
                  <option>Absent</option>
                  <option>Excused</option>
                </select>
                <button class="btn btn-secondary btn-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                  Export CSV/XLS
                </button>
              </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-lg shadow-card overflow-x-auto">
              <table class="data-table">
                <thead>
                  <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Student ID</th>
                    <th scope="col">Grade / Section</th>
                    <th scope="col">Time In</th>
                    <th scope="col">Time Out</th>
                    <th scope="col">Status</th>
                    <th scope="col">Method</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="font-medium">Dela Cruz, Juan</td>
                    <td>BCP-001</td>
                    <td>Grade 7 · Sec A</td>
                    <td>08:02 AM</td>
                    <td>04:15 PM</td>
                    <td><span class="badge badge-present">● Present</span></td>
                    <td>QR</td>
                  </tr>
                  <tr>
                    <td class="font-medium">Santos, Maria</td>
                    <td>BCP-002</td>
                    <td>Grade 7 · Sec A</td>
                    <td>08:18 AM</td>
                    <td>04:10 PM</td>
                    <td><span class="badge badge-tardy">● Tardy</span></td>
                    <td>RFID</td>
                  </tr>
                  <tr>
                    <td class="font-medium">Reyes, Pedro</td>
                    <td>BCP-003</td>
                    <td>Grade 7 · Sec B</td>
                    <td>—</td>
                    <td>—</td>
                    <td><span class="badge badge-absent">● Absent</span></td>
                    <td>—</td>
                  </tr>
                  <tr>
                    <td class="font-medium">Garcia, Ana</td>
                    <td>BCP-004</td>
                    <td>Grade 8 · Sec A</td>
                    <td>07:58 AM</td>
                    <td>04:20 PM</td>
                    <td><span class="badge badge-present">● Present</span></td>
                    <td>RFID</td>
                  </tr>
                  <tr>
                    <td class="font-medium">Lim, Jenny</td>
                    <td>BCP-005</td>
                    <td>Grade 8 · Sec B</td>
                    <td>08:05 AM</td>
                    <td>04:00 PM</td>
                    <td><span class="badge badge-present">● Present</span></td>
                    <td>QR</td>
                  </tr>
                  <tr>
                    <td class="font-medium">Cruz, Robert</td>
                    <td>BCP-006</td>
                    <td>Grade 9 · Sec A</td>
                    <td>07:55 AM</td>
                    <td>—</td>
                    <td><span class="badge badge-present">● Present</span></td>
                    <td>RFID</td>
                  </tr>
                  <tr>
                    <td class="font-medium">Tan, Sofia</td>
                    <td>BCP-007</td>
                    <td>Grade 9 · Sec A</td>
                    <td>—</td>
                    <td>—</td>
                    <td><span class="badge badge-excused">● Excused</span></td>
                    <td>—</td>
                  </tr>
                </tbody>
              </table>

              <!-- Pagination -->
              <div class="px-4 border-t" style="border-color:var(--color-border)">
                <?php include __DIR__ . '/../partials/pagination.php'; ?>
              </div>
            </div>
          </div>

          <!-- ═══ TARDY LOG TAB ═══ -->
          <div data-tab-panel="tardy" class="hidden">
            <div class="bg-white rounded-lg shadow-card overflow-x-auto">
              <div class="px-5 py-4 border-b" style="border-color:var(--color-border)">
                <h3 class="text-base font-semibold" style="color:var(--color-text-primary)">Tardy Log — Sep 7, 2026</h3>
              </div>
              <table class="data-table">
                <thead>
                  <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Student ID</th>
                    <th scope="col">Time In</th>
                    <th scope="col">Minutes Late</th>
                    <th scope="col">Alert Sent</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="font-medium">Santos, Maria</td>
                    <td>BCP-002</td>
                    <td>08:18 AM</td>
                    <td><span class="font-medium" style="color:var(--color-tardy)">+18 min</span></td>
                    <td><span class="badge badge-sent"> Sent</span></td>
                  </tr>
                  <tr>
                    <td class="font-medium">Villanueva, Carlo</td>
                    <td>BCP-012</td>
                    <td>08:22 AM</td>
                    <td><span class="font-medium" style="color:var(--color-tardy)">+22 min</span></td>
                    <td><span class="badge badge-sent"> Sent</span></td>
                  </tr>
                  <tr>
                    <td class="font-medium">Aquino, Bea</td>
                    <td>BCP-015</td>
                    <td>08:10 AM</td>
                    <td><span class="font-medium" style="color:var(--color-tardy)">+10 min</span></td>
                    <td><span class="badge badge-pending"> Pending</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ═══ ABSENCE LOG TAB ═══ -->
          <div data-tab-panel="absence" class="hidden">
            <div class="bg-white rounded-lg shadow-card overflow-x-auto">
              <div class="px-5 py-4 border-b" style="border-color:var(--color-border)">
                <h3 class="text-base font-semibold" style="color:var(--color-text-primary)">Absence Log — Sep 7, 2026</h3>
              </div>
              <table class="data-table">
                <thead>
                  <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Student ID</th>
                    <th scope="col">Date</th>
                    <th scope="col">Alert Sent</th>
                    <th scope="col">Excuse Slip</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="font-medium">Reyes, Pedro</td>
                    <td>BCP-003</td>
                    <td>Sep 7, 2026</td>
                    <td><span class="badge badge-sent"> Sent</span></td>
                    <td><a href="<?php echo url('dashboard/excuse-slips?tab=submit'); ?>" class="text-sm font-medium" style="color:var(--color-teal-500)">Submit →</a></td>
                  </tr>
                  <tr>
                    <td class="font-medium">Moreno, Alex</td>
                    <td>BCP-019</td>
                    <td>Sep 7, 2026</td>
                    <td><span class="badge badge-pending"> Pending</span></td>
                    <td><span class="text-sm" style="color:var(--color-text-muted)">None</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('daily');</script>
