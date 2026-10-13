// SPA prototype shell — role switcher, page router, in-memory actions.
const state = { role: null, page: "overview" };

const NAV = {
  admin: [
    ["overview", "Overview"],
    ["users", "Users"],
    ["setup", "School setup"],
    ["imports", "Imports / Enrollment"],
    ["schedules", "Schedule approvals"],
    ["grades", "Grade approvals"],
    ["announcements", "Announcements"],
    ["audit", "Audit logs"],
  ],
  faculty: [
    ["overview", "My classes"],
    ["classlist", "Class lists"],
    ["schedules", "Schedule proposals"],
    ["grades", "Quarterly grades"],
    ["announcements", "Announcements"],
  ],
  student: [
    ["overview", "Overview"],
    ["schedule", "My schedule"],
    ["grades", "My grades"],
    ["announcements", "Announcements"],
    ["profile", "My profile"],
  ],
};

function login(role) {
  state.role = role;
  state.page = role === "student" ? "overview" : "overview";
  const names = { admin: "Admin Principal", faculty: "Maria Santos", student: "John Enquig" };
  DB.me = { name: names[role], role };
  document.getElementById("login-view").classList.add("hidden");
  document.getElementById("app-view").classList.remove("hidden");
  render();
}

function logout() {
  state.role = null; DB.me = null;
  document.getElementById("app-view").classList.add("hidden");
  document.getElementById("login-view").classList.remove("hidden");
}

function go(page) { state.page = page; render(); }

function badge(status) {
  const colors = {
    pending: "bg-yellow-100 text-yellow-800", draft: "bg-gray-200 text-gray-700",
    approved: "bg-green-100 text-green-800", finalized: "bg-green-100 text-green-800",
    submitted: "bg-blue-100 text-blue-800", rejected: "bg-red-100 text-red-800",
    returned: "bg-orange-100 text-orange-800", ok: "bg-green-100 text-green-800",
    matched: "bg-blue-100 text-blue-800", duplicate: "bg-yellow-100 text-yellow-800",
    error: "bg-red-100 text-red-800", active: "bg-green-100 text-green-800",
  };
  return `<span class="px-2 py-0.5 rounded text-xs font-semibold ${colors[status] || "bg-gray-100"}">${status}</span>`;
}

function toast(msg) {
  const el = document.getElementById("toast");
  el.textContent = msg;
  el.classList.remove("hidden");
  setTimeout(() => el.classList.add("hidden"), 2200);
}

function render() {
  const { role, page } = state;
  document.getElementById("user-label").textContent = `${DB.me.name} · ${role}`;
  document.getElementById("year-label").textContent = DB.schoolYear.name;
  const nav = document.getElementById("nav");
  nav.innerHTML = NAV[role].map(([key, label]) =>
    `<button onclick="go('${key}')" class="w-full text-left px-3 py-2 rounded ${page === key ? "bg-blue-600 text-white" : "hover:bg-gray-100"}">${label}</button>`
  ).join("");
  document.getElementById("page").innerHTML = PAGES[role + ":" + page]();
}

function table(heads, rows) {
  return `<div class="overflow-x-auto bg-white rounded shadow"><table class="min-w-full text-sm">
    <thead><tr class="bg-gray-50">${heads.map(h => `<th class="text-left px-3 py-2 font-semibold">${h}</th>`).join("")}</tr></thead>
    <tbody>${rows.map(r => `<tr class="border-t">${r.map(c => `<td class="px-3 py-2">${c}</td>`).join("")}</tr>`).join("")}</tbody></table></div>`;
}
function card(title, value, sub) {
  return `<div class="bg-white rounded shadow p-4"><div class="text-sm text-gray-500">${title}</div><div class="text-2xl font-bold">${value}</div><div class="text-xs text-gray-400">${sub || ""}</div></div>`;
}
function announceList() {
  return DB.announcements.map(a => `<div class="bg-white rounded shadow p-4 mb-3">
    <div class="flex justify-between"><strong>${a.title}</strong><span class="text-xs text-gray-500">${a.date} · ${a.audience}</span></div>
    <p class="text-sm mt-1">${a.body}</p></div>`).join("");
}

// ---- actions (in-memory only) ----
function reviewSchedule(id, action) {
  const p = DB.scheduleProposals.find(x => x.id === id);
  p.status = action === "approve" ? "approved" : "rejected";
  toast(`Schedule #${id} ${p.status}`);
  render();
}
function reviewGrades(id, action) {
  const g = DB.gradeBatches.find(x => x.id === id);
  g.status = action === "finalize" ? "finalized" : "returned";
  toast(`Grade batch #${id} ${g.status}`);
  render();
}
function submitGrades(id) {
  const g = DB.gradeBatches.find(x => x.id === id);
  g.status = "submitted";
  toast(`Grade batch #${id} submitted for review`);
  render();
}

const PAGES = {
  // ---------- ADMIN ----------
  "admin:overview": () => `
    <h2 class="text-xl font-bold mb-3">Admin overview · ${DB.schoolYear.name}</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
      ${card("Students", "95", "3 sections")}${card("Faculty", "12", "8 assignments")}
      ${card("Pending schedules", DB.scheduleProposals.filter(p => p.status === "pending").length, "awaiting approval")}
      ${card("Grade batches", DB.gradeBatches.length, "1 submitted")}
    </div>
    <p class="text-sm text-gray-600">Prototype: approve schedules and finalize grades below. Backend policies are not enforced here.</p>`,

  "admin:users": () => `
    <h2 class="text-xl font-bold mb-3">Users</h2>
    ${table(["Name", "Username", "Role", "Status"], DB.users.map(u => [u.name, u.username, u.role, badge(u.status)]))}
    <p class="text-xs text-gray-500 mt-2">Student pattern: <code>Enquig.john</code> · initial password = Student ID · forced change on first login.</p>`,

  "admin:setup": () => `
    <h2 class="text-xl font-bold mb-3">School setup</h2>
    <h3 class="font-semibold mb-1">Sections</h3>
    ${table(["Section", "Adviser", "Room", "Enrolled"], DB.sections.map(s => [s.name, s.adviser, s.room, s.enrolled]))}
    <h3 class="font-semibold mt-4 mb-1">Subjects</h3>
    ${table(["Code", "Name", "Level"], DB.subjects.map(s => [s.code, s.name, s.level]))}`,

  "admin:imports": () => `
    <h2 class="text-xl font-bold mb-3">Import preview — Grade 7 upload.csv</h2>
    ${table(["Row", "Name", "ID", "Result", "Detail"], DB.importPreview.map(r => [r.row, r.name, r.id, badge(r.result), r.detail]))}
    <div class="mt-3 flex gap-2">
      <button onclick="toast('Committed 1 new + 1 matched (prototype)') " class="bg-blue-600 text-white px-4 py-2 rounded">Commit valid rows</button>
      <span class="text-sm text-gray-500 self-center">Duplicates/errors are reported, nothing corrupts existing records.</span>
    </div>`,

  "admin:schedules": () => `
    <h2 class="text-xl font-bold mb-3">Schedule approvals</h2>
    ${table(["ID", "Class", "Ver", "By", "Status", "Action"], DB.scheduleProposals.map(p =>
      [p.id, p.class, "v" + p.version, p.by, badge(p.status),
       p.status === "pending" ? `<button onclick="reviewSchedule(${p.id},'approve')" class="text-green-700 underline">Approve</button> <button onclick="reviewSchedule(${p.id},'reject')" class="text-red-700 underline ml-2">Reject</button>` : `<span class="text-xs text-gray-400">${p.note || "—"}</span>`]))}
    <p class="text-xs text-gray-500 mt-2">Rule: overlap recheck on approve; approved stays active until replacement approved; students see approved only.</p>`,

  "admin:grades": () => `
    <h2 class="text-xl font-bold mb-3">Grade approvals</h2>
    ${table(["ID", "Class", "Quarter", "By", "Status", "Action"], DB.gradeBatches.map(g =>
      [g.id, g.class, g.quarter, g.by, badge(g.status),
       g.status === "submitted" ? `<button onclick="reviewGrades(${g.id},'finalize')" class="text-green-700 underline">Finalize</button> <button onclick="reviewGrades(${g.id},'return')" class="text-orange-700 underline ml-2">Return</button>` : "—"]))}`,

  "admin:announcements": () => `<h2 class="text-xl font-bold mb-3">Announcements</h2>${announceList()}`,
  "admin:audit": () => `
    <h2 class="text-xl font-bold mb-3">Audit logs</h2>
    ${table(["When", "Actor", "Action", "Entity"], DB.audit.map(a => [a.when, a.actor, a.action, a.entity]))}`,

  // ---------- FACULTY ----------
  "faculty:overview": () => `
    <h2 class="text-xl font-bold mb-3">My classes — Maria Santos</h2>
    <div class="grid md:grid-cols-2 gap-3">
      <div class="bg-white rounded shadow p-4"><strong>Mathematics 7 · Grade 7 – St. Anne</strong><p class="text-sm text-gray-500">32 students · Room 101</p>
      <div class="mt-2 flex gap-2"><button onclick="go('classlist')" class="text-blue-700 underline text-sm">View class list</button><button onclick="go('grades')" class="text-blue-700 underline text-sm">Enter grades</button></div></div>
      <div class="bg-white rounded shadow p-4"><strong>Adviser · Grade 7 – St. Anne</strong><p class="text-sm text-gray-500">Authorized sections only — cannot approve own schedule.</p></div>
    </div>`,

  "faculty:classlist": () => `
    <h2 class="text-xl font-bold mb-3">Class list — Math 7 · St. Anne</h2>
    ${table(["Student No", "Name", "Username"], DB.classList.map(s => [s.no, s.name, s.username]))}`,

  "faculty:schedules": () => `
    <h2 class="text-xl font-bold mb-3">Schedule proposals</h2>
    ${table(["ID", "Class", "Ver", "Status", "Note"], DB.scheduleProposals.filter(p => p.by === "Maria Santos" || p.by === "Jose Cruz").map(p => [p.id, p.class, "v" + p.version, badge(p.status), p.note || "—"]))}
    <p class="text-xs text-gray-500 mt-2">Faculty can draft/edit/submit; approval is admin-only.</p>`,

  "faculty:grades": () => `
    <h2 class="text-xl font-bold mb-3">Quarterly grades — Math 7 · Q2 (draft)</h2>
    ${table(["Student", "Q1 (final)", "Q2 draft"], DB.gradeEntries.map(g => [g.student, g.q1, `<input value="${g.q2 || ""}" class="border rounded px-2 py-1 w-20" />`]))}
    <div class="mt-3"><button onclick="submitGrades(2)" class="bg-blue-600 text-white px-4 py-2 rounded">Submit Q2 for review</button></div>`,

  "faculty:announcements": () => `<h2 class="text-xl font-bold mb-3">Announcements</h2>${announceList()}`,

  // ---------- STUDENT ----------
  "student:overview": () => `
    <h2 class="text-xl font-bold mb-3">Good day, John Enquig</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
      ${card("Section", "St. Anne", "Grade 7 · A.Y. 2026–2027")}${card("Approved classes", "5", "weekly timetable")}${card("Finalized", "Q1", "Q2 not yet released")}
    </div>`,

  "student:schedule": () => `
    <h2 class="text-xl font-bold mb-3">My schedule (approved only)</h2>
    ${table(["Day", "Time", "Subject", "Teacher", "Room"], DB.timetable.map(t => [t.day, t.time, t.subject, t.teacher, t.room]))}`,

  "student:grades": () => `
    <h2 class="text-xl font-bold mb-3">My grades (finalized only)</h2>
    ${table(["Subject", "Q1", "Q2", "Q3", "Q4", "Note"], DB.myGrades.map(g => [g.subject, g.q1 ?? "—", g.q2 ?? "—", g.q3 ?? "—", g.q4 ?? "—", `<span class="text-xs">${g.status}</span>`]))}
    <p class="text-xs text-gray-500 mt-2">Draft/submitted grades are hidden until finalized — per blueprint.</p>`,

  "student:announcements": () => `<h2 class="text-xl font-bold mb-3">Announcements</h2>${announceList()}`,

  "student:profile": () => `
    <h2 class="text-xl font-bold mb-3">My profile</h2>
    <div class="bg-white rounded shadow p-4 max-w-md">
      <p><strong>John Enquig</strong> · <code>Enquig.john</code></p>
      <p class="text-sm text-gray-600">Student No: 2026-0001 · Grade 7 – St. Anne · A.Y. 2026–2027</p>
      <div class="mt-3 flex gap-2">
        <button onclick="toast('Photo upload is stubbed in prototype')" class="border px-3 py-1.5 rounded text-sm">Update photo</button>
        <button onclick="toast('Password changed (prototype)') " class="border px-3 py-1.5 rounded text-sm">Change password</button>
      </div>
      <p class="text-xs text-gray-500 mt-2">Editable: contact, photo, password only. Grades/enrollment/schedules are read-only.</p>
    </div>`,
};
