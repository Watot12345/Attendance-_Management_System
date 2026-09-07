# UI/UX Design Architecture
# AI-Supported Attendance Management System
# Bestlink College of the Philippines

> Planning document. No code. Covers visual identity, token system, page inventory, layout patterns, component library, and screen-by-screen wireframes for all 10 core features.

---

## 1. Design Brief Summary

| Axis | Value |
|---|---|
| Product | Web-based academic attendance system with ML insights |
| Institution | Bestlink College of the Philippines |
| Primary users | Students, Faculty/Teachers, Administrators, Parents |
| Primary job | Record attendance via RFID/QR, detect tardy/absence automatically, manage excuse slips digitally, surface ML insights, alert parents |
| Tone | Institutional but approachable — trustworthy, clear, efficient |
| Stack | PHP 8.2+ backend · Python/Scikit-learn ML service · MySQL · Tailwind CSS · Vanilla JS |

---

## 2. Confirmed Feature Set (10 Core Features)

| # | Feature | Description |
|---|---|---|
| F1 | Daily Attendance Marking | Record student/teacher attendance per day |
| F2 | RFID / QR Scanning | Hardware RFID tap OR camera QR scan at entry/exit |
| F3 | Tardy & Absence Logs | Auto-flag late arrivals; auto-mark absent at close |
| F4 | Teacher Attendance | Separate tracking and reporting for faculty |
| F5 | Excuse Slip Submission | Digital excuse slip with file attachment + approval workflow |
| F6 | Attendance Calendar | Monthly color-coded calendar per student/teacher |
| F7 | Alerts to Parents | Automated email when student is tardy or absent |
| F8 | Analytics Dashboard | ML-powered patterns, trends, heatmaps via Scikit-learn |
| F9 | Perfect Attendance Award Tool | Identify and notify zero-absence/tardy students |
| F10 | CSV / Excel Export | Export any attendance data with filters |

---

## 3. Design Token System

### 3.1 Color Palette

Direction: deep navy institutional base + fresh teal accent.
Navy = academic authority. Teal = data clarity. Cool gray surface = neutral, not warm cream (avoids AI default tell).

```
--color-navy-950:   #0B1929   ← sidebar, top nav bg
--color-navy-800:   #152D4A   ← sidebar hover
--color-navy-600:   #1E4270   ← secondary bg, card borders
--color-teal-500:   #0D9488   ← primary CTA, active nav, links
--color-teal-300:   #5EEAD4   ← hover on accent, chart highlights
--color-teal-100:   #CCFBF1   ← badge fills, light accent bg

--color-surface:    #F0F4F8   ← page background
--color-card:       #FFFFFF   ← card/panel bg
--color-border:     #CBD5E1   ← dividers, input borders

--color-text-primary:   #0F172A  ← headings, labels
--color-text-secondary: #475569  ← subtitles, metadata
--color-text-muted:     #94A3B8  ← placeholders, disabled

/* Attendance status colors — used across F1, F2, F3, F6 */
--color-present:    #16A34A   ← green
--color-tardy:      #D97706   ← amber
--color-absent:     #DC2626   ← red
--color-excused:    #2563EB   ← blue
--color-no-data:    #E2E8F0   ← gray (no class / weekend)

/* Alert severity — used in F7, F8 */
--color-alert-high:   #DC2626
--color-alert-medium: #D97706
--color-alert-low:    #2563EB
```

### 3.2 Typography

```
Display / Headings:  "Plus Jakarta Sans"  weights: 600, 700
  — page titles, section headers, stat numbers, award text

Body / UI:           "Inter"  weights: 400, 500, 600
  — all body, labels, table content, form fields, badges
```

Type scale:
```
text-xs:   12px  ← timestamps, table metadata, badge text
text-sm:   14px  ← form labels, secondary info, table rows
text-base: 16px  ← body copy, descriptions
text-lg:   18px  ← card titles, section subheads
text-xl:   20px  ← page subheadings
text-2xl:  24px  ← page titles (h1)
text-4xl:  36px  ← stat numbers on dashboard cards
text-5xl:  48px  ← award number / hero stat
```

### 3.3 Spacing & Layout

```
Base unit:        4px (Tailwind default)
Page max-width:   1280px
Sidebar width:    256px fixed desktop, collapsible mobile
Content padding:  24px desktop, 16px mobile
Card padding:     20px
Card radius:      rounded-lg (8px) — cards/inputs only
Button radius:    rounded-md (6px)
Table row height: 48px
```

### 3.4 Elevation

Two levels only — no generic shadows everywhere:
```
Level 1 (cards):   box-shadow: 0 1px 3px rgba(0,0,0,0.08)
Level 2 (modals):  box-shadow: 0 8px 32px rgba(0,0,0,0.18)
```

### 3.5 Design Principles

1. **Status always dual-encoded** — attendance status uses color + text label, never color alone
2. **Data density over decoration** — management system; every pixel conveys information
3. **ML insights are explanations** — show finding + why + how many affected, never raw coefficients
4. **One bold element per screen** — key stat or action dominates; everything else recedes
5. **Motion only answers actions** — modal open/close, toast appear, tab switch. Zero scroll reveals or floating animations

---

## 4. Page Inventory (All 10 Features Mapped)

### 4.1 Auth
| Page | Route | Features |
|---|---|---|
| Login | `/login` | — |

### 4.2 Dashboard
| Page | Route | Features |
|---|---|---|
| Main Dashboard | `/dashboard` | F1, F3, F8 summary |

### 4.3 Attendance Module (F1, F2, F3, F4)
| Page | Route | Who | Features |
|---|---|---|---|
| Daily Attendance List | `/attendance/daily` | Admin, Faculty | F1, F3 |
| RFID/QR Scan Kiosk | `/attendance/scan` | Admin, Faculty, Student | F2 |
| Tardy & Absence Log | `/attendance/tardies` | Admin, Faculty | F3 |
| Teacher Attendance | `/attendance/teachers` | Admin | F4 |
| Teacher Daily Log | `/attendance/teachers/daily` | Admin | F4 |
| Manual Entry | `/attendance/manual` | Admin | F1 |
| Student Attendance Detail | `/attendance/student/{id}` | Admin, Faculty | F1, F3 |

### 4.4 Attendance Calendar (F6)
| Page | Route | Who |
|---|---|---|
| Attendance Calendar | `/attendance/calendar` | All roles |

### 4.5 Excuse Slips (F5)
| Page | Route | Who |
|---|---|---|
| Submit Excuse Slip | `/excuses/submit` | Student, Faculty, Admin, Parent |
| Review Queue | `/excuses/review` | Admin |
| Excuse Detail | `/excuses/{id}` | Admin, submitter |

### 4.6 Parent Alerts (F7)
| Page | Route | Who |
|---|---|---|
| Alert History | `/alerts/history` | Admin |
| Alert Settings | `/alerts/settings` | Admin |
| Parent View (child attendance) | `/parent/attendance` | Parent |

### 4.7 Analytics (F8)
| Page | Route | Who |
|---|---|---|
| Analytics Dashboard | `/analytics` | Admin, Faculty |
| Attendance Patterns | `/analytics/patterns` | Admin, Faculty |
| At-Risk Students | `/analytics/at-risk` | Admin, Faculty |
| Prediction Report | `/analytics/predictions` | Admin |

### 4.8 Perfect Attendance Award (F9)
| Page | Route | Who |
|---|---|---|
| Award Tool | `/awards` | Admin |
| Award Results | `/awards/results` | Admin |

### 4.9 Export (F10)
| Page | Route | Who |
|---|---|---|
| Export Center | `/exports` | Admin |

### 4.10 Admin
| Page | Route |
|---|---|
| User Management | `/users` |
| Create/Edit User | `/users/create`, `/users/{id}/edit` |
| System Settings | `/settings` |

---

## 5. Layout Patterns

### 5.1 Global Shell (Authenticated)

```
┌─────────────────────────────────────────────────────────┐
│  TOPBAR  [Logo · System Name]       [🔔] [User ▾] [?]  │  h:56px, bg:white, border-bottom
├───────────┬─────────────────────────────────────────────┤
│           │                                             │
│  SIDEBAR  │   CONTENT AREA                              │
│  256px    │   bg: --color-surface, padding: 24px        │
│  bg:navy  │                                             │
│           │   ┌───────────────────────────────────┐    │
│  [nav]    │   │ PAGE HEADER                       │    │
│           │   │ Title + breadcrumb + primary btn  │    │
│           │   └───────────────────────────────────┘    │
│           │                                             │
│           │   [PAGE CONTENT]                            │
└───────────┴─────────────────────────────────────────────┘
```

### 5.2 Sidebar Navigation (Role-Aware)

```
[BCP Logo]  Attendance System
─────────────────────────────
[🏠] Dashboard
[📋] Attendance
     ├ Daily Log           (F1)
     ├ Scan RFID/QR        (F2)
     ├ Tardy & Absence     (F3)
     ├ Teacher Attendance  (F4)
     ├ Calendar            (F6)
     └ Manual Entry        (admin only)
[📝] Excuse Slips
     ├ Submit              (F5)
     └ Review Queue        (admin only, F5)
[🔔] Parent Alerts         (admin, F7)
     ├ Alert History
     └ Alert Settings
[📊] ML Analytics          (admin/faculty, F8)
     ├ Dashboard
     ├ Patterns
     └ At-Risk Students
[🏆] Perfect Attendance    (admin, F9)
[📥] Export Data           (admin, F10)
─────────────────────────────
[⚙️] Users                 (admin only)
[⚙️] Settings
[avatar] Name · Role
[→] Logout
```

### 5.3 Content Layout Patterns

**Pattern A — Stat Cards + Table** (F1, F3, F4)
```
┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐
│ STAT │ │ STAT │ │ STAT │ │ STAT │   4-col stat row
└──────┘ └──────┘ └──────┘ └──────┘

┌─────────────────────────────────────────┐
│  TABLE / LIST                           │
│  [search] [filter] [date] [export]      │
│  rows...                                │
└─────────────────────────────────────────┘
```

**Pattern B — Split Panel** (F8 Analytics)
```
┌──────────────────────┬──────────────────┐
│  CHART / VIZ  (2/3)  │  FILTER  (1/3)   │
└──────────────────────┴──────────────────┘
┌──────────────────────────────────────────┐
│  SECONDARY CHARTS (2-col grid)           │
└──────────────────────────────────────────┘
```

**Pattern C — Centered Form** (Login, F5 Submit)
```
            ┌────────────────────┐
            │  FORM CARD         │
            │  max-width: 480px  │
            │  centered          │
            │  [fields]          │
            │  [submit]          │
            └────────────────────┘
```

**Pattern D — Calendar Grid** (F6)
```
┌─────────────────────────────────────────┐
│  [Student ▾]  [← Sep 2026 →]  [Print]  │
├─────────────────────────────────────────┤
│  Sun  Mon  Tue  Wed  Thu  Fri  Sat      │
│  [ ]  [P]  [P]  [T]  [A]  [P]  [ ]    │
│  ...                                    │
├─────────────────────────────────────────┤
│  [P]Present [T]Tardy [A]Absent [E]Excused│
└─────────────────────────────────────────┘
```

**Pattern E — Tool + Results** (F9 Awards, F10 Export)
```
┌──────────────────────────────────────────┐
│  TOOL CONFIG PANEL                       │
│  [select period] [filters] [Run Tool]    │
└──────────────────────────────────────────┘
┌──────────────────────────────────────────┐
│  RESULTS (appears after run)             │
│  [table] [export] [notify parents]       │
└──────────────────────────────────────────┘
```

---

## 6. Component Library

### 6.1 Stat Card (F1, F3, F4, F8)
```
┌────────────────────────┐
│  Present Today          │  text-sm, --color-text-secondary
│                          │
│  247                    │  text-4xl, Plus Jakarta Sans 700
│                          │
│  ↑ 12 from yesterday    │  text-xs, green if up/good, red if up/bad
└────────────────────────┘
border-left: 4px solid var(--color-present)
color varies by stat type
```

### 6.2 Attendance Status Badge (F1, F2, F3, F6)
```
[● Present]   bg-green-100 text-green-800 border border-green-200
[● Tardy  ]   bg-amber-100 text-amber-800 border border-amber-200
[● Absent ]   bg-red-100   text-red-800   border border-red-200
[● Excused]   bg-blue-100  text-blue-800  border border-blue-200
```
Always: dot icon + text label. Never color alone.

### 6.3 RFID/QR Scan Confirmation Overlay (F2)
```
┌─────────────────────────┐
│   [student photo 80px]   │
│   Juan Dela Cruz         │  text-xl bold
│   Grade 7 — Section A   │  text-sm muted
│                          │
│   ████████████           │
│      PRESENT             │  large, full-width colored band
│      08:04 AM            │  green=present, amber=tardy
│                          │
│   Dismissing in 3s...    │  text-xs muted
└─────────────────────────┘
RFID tap: same overlay, method shows "RFID"
QR scan: same overlay, method shows "QR"
```

### 6.4 ML Insight Card (F8)
```
┌─────────────────────────────────────────────┐
│  🔍 Pattern Detected                         │  text-xs teal uppercase
│                                              │
│  Absences spike on Mondays                  │  text-lg bold — the finding
│                                              │
│  Students in Grade 7 Section A have 3×      │  text-sm body — explanation
│  more absences on Mondays vs other days.    │
│  Based on 90 days of attendance data.       │
│                                              │
│  12 students affected    [View Details →]   │  action always present
└─────────────────────────────────────────────┘
border-left: 4px solid --color-teal-500
```

### 6.5 Data Table (F1, F3, F4, F10)
```
┌──────────────────────────────────────────────────────────┐
│  [🔍 Search...]   [Grade ▾] [Section ▾] [Status ▾] [Date▾]│
├──────────────────────────────────────────────────────────┤
│  Name              │ ID      │ Time In │ Status  │ ···  │
│  ──────────────────┼─────────┼─────────┼─────────┼──────│
│  Dela Cruz, Juan   │ BCP-001 │ 08:02   │[Present]│ ···  │
│  Santos, Maria     │ BCP-002 │ 08:18   │[Tardy]  │ ···  │
│  Reyes, Pedro      │ BCP-003 │  —      │[Absent] │ ···  │
├──────────────────────────────────────────────────────────┤
│  1–25 of 312   [← Prev]  1 2 3 … 13  [Next →]          │
└──────────────────────────────────────────────────────────┘
row h: 48px, alt row: bg-slate-50/40, sticky header
```

### 6.6 Alert Badge / Notification (F7)
```
Topbar bell:  [🔔 3]  — red dot with count if pending alerts

Alert list item:
┌─────────────────────────────────────────────┐
│  🔴 ABSENT   Juan Dela Cruz                 │
│  Sep 7, 2026 · Email queued to parent       │
│  maria.delacruzmom@email.com                │
│  [Sent ✓]  or  [Pending]  or  [Failed ✗]   │
└─────────────────────────────────────────────┘
```

### 6.7 Award Card (F9)
```
┌─────────────────────────────────────────────┐
│  🏆  Perfect Attendance                      │  gold accent
│                                              │
│  Juan Dela Cruz                             │  text-lg bold
│  Grade 7 — Section A                        │
│                                              │
│  September 2026                             │
│  22 days · 0 absences · 0 tardies          │
│                                              │
│  [📧 Notify Parent]   [🖨 Print Certificate]│
└─────────────────────────────────────────────┘
```

### 6.8 Buttons
```
Primary:    bg-teal-500 text-white hover:bg-teal-600        px-4 py-2 rounded-md
Secondary:  bg-white border border-slate-300 text-slate-700  px-4 py-2 rounded-md
Danger:     bg-red-600 text-white hover:bg-red-700           px-4 py-2 rounded-md
Ghost:      text-teal-600 hover:bg-teal-50                   px-4 py-2 rounded-md

Sizes: sm(px-3 py-1.5 text-sm)  md(default)  lg(px-6 py-3 text-base)
```

### 6.9 Toast Notifications
```
Success: bg-green-50  border-l-4 border-green-500  text-green-800
Error:   bg-red-50    border-l-4 border-red-500    text-red-800
Warning: bg-amber-50  border-l-4 border-amber-500  text-amber-800
Info:    bg-blue-50   border-l-4 border-blue-500   text-blue-800

Position: fixed bottom-4 right-4 z-50
Animation: slide-up + fade-in (300ms), auto-dismiss 4s
```

---

## 7. Screen Wireframes (All 10 Features)

### 7.1 Login
```
┌──────────────────────────────────────────────────────────┐
│  bg: --color-navy-950                                    │
│                                                          │
│              [BCP Logo]                                  │
│        Bestlink College of the Philippines               │
│                                                          │
│   ┌──────────────────────────────────────────────────┐  │
│   │  Attendance Management System                    │  │
│   │  Sign in to continue                             │  │
│   │                                                  │  │
│   │  Email address                                   │  │
│   │  [________________________________]              │  │
│   │                                                  │  │
│   │  Password                                   [👁] │  │
│   │  [________________________________]              │  │
│   │                                                  │  │
│   │  [         Sign In          ]                    │  │
│   │                                                  │  │
│   │  Forgot password? Contact your administrator.    │  │
│   └──────────────────────────────────────────────────┘  │
│                                                          │
│  Academic Year 2025–2026                                 │
└──────────────────────────────────────────────────────────┘
Bold element: navy bg. Card: pure white, no decoration.
```

### 7.2 Admin Dashboard (F1, F3, F7, F8 preview)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │ TOPBAR                          [🔔 3] [Admin▾]│
│         ├────────────────────────────────────────────────┤
│[Home]   │  Good morning, Dr. Santos                      │
│[Attend] │  Wednesday, September 7, 2026                  │
│ ├Daily  │                                                 │
│ ├Scan   │  ┌─────────┐┌─────────┐┌─────────┐┌─────────┐ │
│ ├Tardy  │  │ Present ││  Tardy  ││  Absent ││ Excused │ │
│ ├Teacher│  │   247   ││   18    ││   12    ││    5    │ │
│ └Cal    │  │↑12 yest.││↓3 yest.││↑2 yest.││         │ │
│[Excuses]│  └─────────┘└─────────┘└─────────┘└─────────┘ │
│ ├Submit │                                                 │
│ └Review │  ┌──────────────────────┐┌───────────────────┐ │
│[Alerts] │  │ 30-Day Trend         ││ 🔍 ML Insight     │ │
│[Analyt] │  │ [Chart.js line]      ││ Monday absences   │ │
│[Awards] │  │                      ││ 3× above average  │ │
│[Export] │  └──────────────────────┘│ 12 students       │ │
│[Users]  │                          │ [View Details →]  │ │
│[Setting]│  ┌──────────────────────┘└───────────────────┘ │
│         │  │ Recent Activity                              │
│[avatar] │  │ 08:32 Dela Cruz, Juan — PRESENT (QR)        │
│Logout   │  │ 08:31 Santos, Maria — TARDY (RFID)          │
└─────────┴──┴──────────────────────────────────────────────┘
```

### 7.3 RFID/QR Scan Kiosk (F2)
```
┌──────────────────────────────────────────────────────────┐
│  [← Back]         RFID / QR Scanner        [Manual Entry]│
├──────────────────────────────────────────────────────────┤
│                                                          │
│   ┌─────────────┐         ┌──────────────────────┐      │
│   │  RFID TAP   │   OR    │   QR CODE CAMERA     │      │
│   │             │         │                      │      │
│   │  📡         │         │  [CAMERA FEED]       │      │
│   │  Tap your   │         │  ┌──────────┐        │      │
│   │  ID card    │         │  │          │        │      │
│   │  here       │         │  └──────────┘        │      │
│   │             │         │  scan target box     │      │
│   └─────────────┘         └──────────────────────┘      │
│                                                          │
│   ────────────────── OR SEARCH ──────────────────        │
│   Student ID / Name  [_____________________] [Find]      │
│                                                          │
│   Entry ◉   Exit ○                    Direction toggle   │
│                                                          │
└──────────────────────────────────────────────────────────┘

ON SCAN SUCCESS — full overlay:
┌──────────────────────────────────────────────────────────┐
│                   [photo 80px]                           │
│                   Juan Dela Cruz                         │
│                   Grade 7 · Section A                    │
│                                                          │
│          ┌─────────────────────────────┐                 │
│          │          PRESENT            │  ← green bg     │
│          │   08:04 AM  ·  QR Scan     │                 │
│          └─────────────────────────────┘                 │
│                                                          │
│                  Dismissing in 3s...                     │
└──────────────────────────────────────────────────────────┘
amber bg if TARDY. Shows method: RFID or QR.
```

### 7.4 Daily Attendance List (F1) + Tardy Log (F3)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │  Daily Attendance          [+ Manual Entry]     │
│         │  Wednesday, Sep 7, 2026    [📅 Change Date]     │
│         ├────────────────────────────────────────────────┤
│         │ ┌─────────┐┌─────────┐┌─────────┐┌─────────┐  │
│         │ │Present  ││ Tardy   ││ Absent  ││  Total  │  │
│         │ │  247    ││   18    ││   12    ││   277   │  │
│         │ └─────────┘└─────────┘└─────────┘└─────────┘  │
│         │                                                 │
│         │ [🔍 Search...]  [Grade▾][Section▾][Status▾]    │
│         │                               [Export CSV/XLS] │
│         │                                                 │
│         │ Name            │ID     │TimeIn │Status │Method│
│         │ ────────────────┼───────┼───────┼───────┼──────│
│         │ Dela Cruz, Juan │BCP-001│08:02  │[Pres] │ QR  │
│         │ Santos, Maria   │BCP-002│08:18  │[Tard] │ RFID│
│         │ Reyes, Pedro    │BCP-003│  —    │[Abs]  │  —  │
│         │ Garcia, Ana     │BCP-004│07:58  │[Pres] │ RFID│
│         │                                                 │
│         │  1–25 of 277   [Prev] 1 2 … 12 [Next]         │
└─────────┴────────────────────────────────────────────────┘

TARDY & ABSENCE LOG tab (F3) — same page, tab switch:
│ [Daily] [Tardy Log] [Absence Log]                        │
│                                                          │
│ Tardy Log — Sep 7, 2026                                  │
│ Name          │ Time In │ Minutes Late │ Alert Sent       │
│ Santos, Maria │ 08:18   │ +18 min      │ ✓ Sent           │
│ ...                                                      │
```

### 7.5 Teacher Attendance (F4)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │  Teacher Attendance                             │
│         │  Wednesday, Sep 7, 2026    [📅 Change Date]    │
│         ├────────────────────────────────────────────────┤
│         │ ┌─────────┐┌─────────┐┌─────────┐             │
│         │ │ Present ││  Absent ││  Total  │             │
│         │ │    8    ││    2    ││   10    │             │
│         │ └─────────┘└─────────┘└─────────┘             │
│         │                                                 │
│         │ [🔍 Search teacher...]  [Dept▾]                │
│         │                                                 │
│         │ Name             │EmpID  │TimeIn│Status│Method  │
│         │ ─────────────────┼───────┼──────┼──────┼───────│
│         │ Santos, Dr. A.   │EMP-01 │07:45 │[Pres]│ RFID  │
│         │ Cruz, Mr. B.     │EMP-02 │08:05 │[Pres]│ QR    │
│         │ Reyes, Ms. C.    │EMP-03 │  —   │[Abs] │  —    │
│         │                                                 │
│         │ Note: No tardy threshold for teachers.         │
│         │                          [Export Teacher Log]  │
└─────────┴────────────────────────────────────────────────┘
```

### 7.6 Attendance Calendar (F6)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │  Attendance Calendar                  [🖨 Print]│
│         ├────────────────────────────────────────────────┤
│         │  Student: [Dela Cruz, Juan · BCP-001       ▾]  │
│         │                                                 │
│         │  ← August 2026      September 2026  →          │
│         │  ──────────────────────────────────────────    │
│         │  Sun   Mon   Tue   Wed   Thu   Fri   Sat       │
│         │                1     2     3     4     5       │
│         │               [P]   [P]   [T]   [P]   [ ]     │
│         │   7     8     9    10    11    12    13        │
│         │  [ ]   [P]   [P]   [A]   [P]   [P]   [ ]     │
│         │  14    15    16    17    18    19    20        │
│         │  [ ]   [P]   [E]   [P]   [P]   [P]   [ ]     │
│         │  21    22    23    24    25    26    27        │
│         │  [ ]   [P]   [P]   [P]   [P]   [P]   [ ]     │
│         │  28    29    30                                │
│         │  [ ]   [P]   [P]                              │
│         │  ──────────────────────────────────────────   │
│         │  [🟢P] [🟡T] [🔴A] [🔵E] [⬜No class]        │
│         │                                                 │
│         │  Summary: 18 Present · 1 Tardy · 1 Absent · 1 Excused│
└─────────┴────────────────────────────────────────────────┘
Click any colored day → modal:
  Date: Sep 3, 2026
  Status: Tardy
  Time In: 08:18 AM   Time Out: 04:15 PM
  Method: RFID
  Minutes Late: +18
  Excuse Slip: None submitted
  [Submit Excuse Slip]
```

### 7.7 Excuse Slip Submit (F5)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │  Submit Excuse Slip                             │
│         ├────────────────────────────────────────────────┤
│         │  ┌──────────────────────────────────────────┐  │
│         │  │                                          │  │
│         │  │  Student                                 │  │
│         │  │  [Select or search student...       ▾]   │  │
│         │  │                                          │  │
│         │  │  Absence Period                          │  │
│         │  │  From [📅 Sep 7, 2026]  To [📅 Sep 7]   │  │
│         │  │                                          │  │
│         │  │  Reason for Absence                      │  │
│         │  │  [________________________________________│  │
│         │  │   ________________________________________│  │
│         │  │   ______________________________]        │  │
│         │  │                                          │  │
│         │  │  Supporting Document (optional)          │  │
│         │  │  [📎 Attach file — PDF, JPG, PNG, 5MB]  │  │
│         │  │                                          │  │
│         │  │  [Cancel]        [Submit Excuse Slip]    │  │
│         │  └──────────────────────────────────────────┘  │
└─────────┴────────────────────────────────────────────────┘
```

### 7.8 Excuse Slip Review Queue (F5 — Admin)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │  Excuse Slip Review        3 pending            │
│         ├────────────────────────────────────────────────┤
│         │ [Pending▾]  [🔍 Search...]  [Date▾]            │
│         │                                                 │
│         │ Student          │ Dates    │ By     │ Status   │
│         │ ─────────────────┼──────────┼────────┼─────────│
│         │ Dela Cruz, Juan  │ Sep 5–6  │ Parent │ [Pend]  │
│         │ Santos, Maria    │ Sep 3    │ Teacher│ [Pend]  │
│         │ Reyes, Pedro     │ Aug28–30 │ Parent │ [Apprd] │
│         │                                                 │
│ Click row → RIGHT PANEL slides in:                       │
│         │ ┌────────────────────────────────────────────┐ │
│         │ │ Juan Dela Cruz — Sep 5–6, 2026             │ │
│         │ │ Submitted by: Parent (Maria Dela Cruz)     │ │
│         │ │                                            │ │
│         │ │ Reason:                                    │ │
│         │ │ Student had high fever. Medical            │ │
│         │ │ certificate attached.                      │ │
│         │ │                                            │ │
│         │ │ [📄 View Attachment]                       │ │
│         │ │                                            │ │
│         │ │ Affected records: 2 absences (Sep 5, 6)   │ │
│         │ │                                            │ │
│         │ │ Review notes:                              │ │
│         │ │ [________________________________]         │ │
│         │ │                                            │ │
│         │ │ [✗ Reject]           [✓ Approve]          │ │
│         │ └────────────────────────────────────────────┘ │
└─────────┴────────────────────────────────────────────────┘
```

### 7.9 Parent Alerts (F7)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │  Parent Alerts                                  │
│         ├────────────────────────────────────────────────┤
│         │ [History] [Settings]                            │
│         │                                                 │
│         │ ┌─────────┐┌─────────┐┌─────────┐             │
│         │ │  Sent   ││ Pending ││ Failed  │             │
│         │ │   142   ││    3    ││    1    │             │
│         │ └─────────┘└─────────┘└─────────┘             │
│         │                                                 │
│         │ [All▾]  [Type▾]  [🔍 Search student...]        │
│         │                                                 │
│         │ Student        │ Type   │ Date   │ Status      │
│         │ ───────────────┼────────┼────────┼─────────────│
│         │ Dela Cruz, J.  │ ABSENT │ Sep 7  │ [✓ Sent]   │
│         │ Santos, M.     │ TARDY  │ Sep 7  │ [⏳ Pending]│
│         │ Reyes, P.      │ ABSENT │ Sep 5  │ [✗ Failed] │
│         │                                                 │
│         │ SETTINGS TAB:                                   │
│         │ ☑ Send alert when student is tardy             │
│         │ ☑ Send alert when student is absent            │
│         │ ☑ Send alert for perfect attendance award      │
│         │ SMTP: [configured in system settings]          │
└─────────┴────────────────────────────────────────────────┘

PARENT PORTAL VIEW (parent login):
┌─────────┬────────────────────────────────────────────────┐
│ minimal │  My Child's Attendance                          │
│ sidebar │  Juan Dela Cruz · Grade 7 Section A            │
│         ├────────────────────────────────────────────────┤
│         │  [Calendar view — read only]                   │
│         │  [Recent Alerts History]                        │
│         │  [Submit Excuse Slip]                           │
└─────────┴────────────────────────────────────────────────┘
```

### 7.10 ML Analytics Dashboard (F8)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │  ML Analytics Dashboard                         │
│         │  Powered by Scikit-learn                        │
│         ├──────────────────────────┬─────────────────────┤
│         │                          │  FILTERS            │
│         │  Attendance Trend (90d)  │                     │
│         │  [Chart.js line chart]   │  Date Range         │
│         │  present/tardy/absent    │  [Aug 1 – Sep 7 ▾]  │
│         │  stacked over time       │                     │
│         ├──────────────────────────┤  Grade              │
│         │                          │  [All Grades ▾]     │
│         │  🔍 Detected Patterns    │                     │
│         │                          │  Section            │
│         │  ┌───────────────────┐   │  [All Sections ▾]  │
│         │  │ Monday Spike      │   │                     │
│         │  │ 3× above avg      │   │  [Apply Filters]   │
│         │  │ 12 students       │   │                     │
│         │  │ [View Details →]  │   │  ─────────────      │
│         │  └───────────────────┘   │  Model Info         │
│         │  ┌───────────────────┐   │  Algo: RandomForest │
│         │  │ At-Risk: 8 found  │   │  Accuracy: 87.3%   │
│         │  │ Chronic absence   │   │  Trained: Sep 5    │
│         │  │ indicators        │   │                     │
│         │  │ [View Students →] │   │                     │
│         │  └───────────────────┘   │                     │
│         ├──────────────────────────┴─────────────────────┤
│         │  ┌──────────────────────┐┌─────────────────────┐│
│         │  │ Absence by Day/Week  ││ By Grade Comparison ││
│         │  │ [heatmap/bar chart]  ││ [grouped bar chart] ││
│         │  └──────────────────────┘└─────────────────────┘│
└─────────┴────────────────────────────────────────────────┘
```

### 7.11 At-Risk Students (F8 sub-page)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │  At-Risk Students                               │
│         │  ML-identified · Scikit-learn analysis          │
│         ├────────────────────────────────────────────────┤
│         │  ┌──────────────────────────────────────────┐  │
│         │  │ ⚠ 8 students flagged at risk             │  │
│         │  │ Criteria: absence frequency, day patterns │  │
│         │  │ and 90-day trend analysis                 │  │
│         │  └──────────────────────────────────────────┘  │
│         │                                                 │
│         │ Student          │Absences│Risk   │Action       │
│         │ ─────────────────┼────────┼───────┼─────────── │
│         │ Santos, Maria    │ 8/30   │[HIGH] │[View] [📧] │
│         │ Cruz, Robert     │ 6/30   │[MED]  │[View] [📧] │
│         │ Lim, Jenny       │ 5/30   │[MED]  │[View] [📧] │
│         │                                                 │
│         │ HIGH = chronic absence likely (model prediction)│
│         │ MED  = early warning indicators present        │
│         │                                                 │
│         │                       [Export At-Risk List]    │
└─────────┴────────────────────────────────────────────────┘
```

### 7.12 Perfect Attendance Award Tool (F9)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │  Perfect Attendance Awards                      │
│         ├────────────────────────────────────────────────┤
│         │  ┌──────────────────────────────────────────┐  │
│         │  │  Award Period                            │  │
│         │  │  Type: [Monthly ▾]  [Quarterly] [Sem]   │  │
│         │  │                                          │  │
│         │  │  From: [📅 Sep 1, 2026]                 │  │
│         │  │  To:   [📅 Sep 30, 2026]                │  │
│         │  │                                          │  │
│         │  │  Grade: [All Grades ▾]                  │  │
│         │  │                                          │  │
│         │  │  [       Calculate Awards       ]        │  │
│         │  └──────────────────────────────────────────┘  │
│         │                                                 │
│         │  Results — September 2026                      │
│         │  ✓ 14 students qualify                         │
│         │                                                 │
│         │  ┌───────────────────┐ ┌───────────────────┐   │
│         │  │🏆 Dela Cruz, Juan │ │🏆 Garcia, Ana     │   │
│         │  │  Grade 7 Sec A   │ │  Grade 7 Sec B   │   │
│         │  │  22d · 0A · 0T   │ │  22d · 0A · 0T   │   │
│         │  │[📧 Notify][🖨 Cert]│ │[📧 Notify][🖨 Cert]│   │
│         │  └───────────────────┘ └───────────────────┘   │
│         │                                                 │
│         │  [📧 Notify All Parents]  [Export List CSV]    │
└─────────┴────────────────────────────────────────────────┘
```

### 7.13 Export Center (F10)
```
┌─────────┬────────────────────────────────────────────────┐
│ SIDEBAR │  Export Data                                    │
│         ├────────────────────────────────────────────────┤
│         │  ┌──────────────────────────────────────────┐  │
│         │  │  What to export                          │  │
│         │  │  ◉ Attendance Records                    │  │
│         │  │  ○ Tardy Log                             │  │
│         │  │  ○ Absence Log                           │  │
│         │  │  ○ Teacher Attendance                    │  │
│         │  │  ○ Excuse Slips                          │  │
│         │  │  ○ Perfect Attendance List               │  │
│         │  │                                          │  │
│         │  │  Date Range                              │  │
│         │  │  From [📅] To [📅]                      │  │
│         │  │                                          │  │
│         │  │  Filters (optional)                      │  │
│         │  │  Grade [All▾]  Section [All▾]            │  │
│         │  │  Status [All▾]  Student [All▾]           │  │
│         │  │                                          │  │
│         │  │  Format: ◉ CSV   ○ Excel (.xlsx)         │  │
│         │  │                                          │  │
│         │  │  Estimated rows: 312                     │  │
│         │  │  (Max 10,000 rows per export)            │  │
│         │  │                                          │  │
│         │  │  [Preview]      [⬇ Download Export]      │  │
│         │  └──────────────────────────────────────────┘  │
└─────────┴────────────────────────────────────────────────┘
```

---

## 8. Responsive Behavior

### 8.1 Breakpoints
```
Mobile:  < 640px   → sidebar hidden, hamburger → drawer, stacked layout
Tablet:  640–1024px → sidebar icons-only (collapsed), 2-col stats
Desktop: > 1024px  → full sidebar, all columns visible
```

### 8.2 Mobile Adaptations
- Sidebar → hamburger drawer from left
- Stat cards → 2×2 grid
- Tables → horizontal scroll, sticky student name column
- QR scanner → full-screen camera, large confirmation overlay
- Calendar → smaller cells, tap-to-modal
- Export → simplified form, same functionality

---

## 9. Accessibility Baseline

- Status: always color + text (never color alone)
- Focus rings: `focus:ring-2 focus:ring-teal-500 focus:ring-offset-2` on all interactive
- Forms: `<label for="">` on every field
- Tables: `<thead>` with `scope="col"`, row `scope="row"` for name col
- Modals: focus trap on open, Escape to close, return focus on close
- `prefers-reduced-motion`: disable all transitions/animations
- ARIA: `aria-live="polite"` on scan confirmation overlay for screen readers

---

## 10. ML UX Rules (F8 — Scikit-learn Output)

Never expose raw model output. Always translate:

| Raw | Display |
|---|---|
| coefficient: 2.87 | "3× higher than average" |
| prediction: 0.83 | "HIGH risk" |
| feature: day_of_week=0 | "Absences spike on Mondays" |
| n_samples: 90 | "Based on 90 days of data" |
| accuracy: 0.873 | "Model accuracy: 87.3%" |

Every ML card must have:
1. Finding (what was detected)
2. Evidence (based on X days / X records)
3. Scope (X students affected)
4. Action (button to do something about it)

---

## 11. JS File Map (per feature)

```
/assets/js/
  app.js        ← global: CSRF token, fetch wrapper, toast, modal, confirm dialog
  scanner.js    ← F2: html5-qrcode init, RFID listener, scan handler, overlay
  dashboard.js  ← F1/F8: Chart.js trend line, stat card auto-refresh
  calendar.js   ← F6: calendar grid render, month nav, day click modal
  analytics.js  ← F8: ML chart rendering, pattern cards, filter controls
  excuses.js    ← F5: form validation, file upload preview, review panel
  alerts.js     ← F7: alert list, status polling, settings toggle
  awards.js     ← F9: period picker, calculate trigger, card grid, notify
  exports.js    ← F10: form controls, row count preview, download trigger
  users.js      ← Admin: table, search, role toggle, delete confirm
```

---

## 12. Sprint Build Order (UI First)

| Sprint | Screens | Features |
|---|---|---|
| 1 | Login · Global shell · Dashboard skeleton | Foundation |
| 2 | Daily Attendance List · Manual Entry | F1 |
| 3 | RFID/QR Scan Kiosk · Confirmation overlay | F2 |
| 4 | Tardy Log · Absence Log (tabs on Daily) | F3 |
| 5 | Teacher Attendance page | F4 |
| 6 | Attendance Calendar | F6 |
| 7 | Excuse Slip Submit · Review Queue · Detail | F5 |
| 8 | Parent Alerts history · Settings · Parent portal | F7 |
| 9 | Analytics Dashboard · Patterns · At-Risk | F8 |
| 10 | Perfect Attendance Award Tool | F9 |
| 11 | Export Center | F10 |
| 12 | User Management · System Settings · Polish | Admin |

---

*Version: 2.0 — Updated with all 10 confirmed features*
*System: AI-Supported Attendance Management System — Bestlink College of the Philippines*
*Stack: PHP 8.2+ · Python/Scikit-learn · MySQL · Tailwind CSS · Vanilla JS*