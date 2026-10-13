// Fake in-memory DB for SPA prototype — no backend, resets on reload.
const DB = {
  me: null,
  users: [
    { id: 1, name: "Admin Principal", username: "admin", role: "admin", status: "active" },
    { id: 2, name: "Maria Santos", username: "santos.maria", role: "faculty", status: "active" },
    { id: 3, name: "John Enquig", username: "Enquig.john", role: "student", status: "active", mustChange: false },
  ],
  schoolYear: { name: "A.Y. 2026–2027", status: "active" },
  sections: [
    { name: "Grade 7 – St. Anne", adviser: "Maria Santos", room: "Room 101", enrolled: 32 },
    { name: "Grade 10 – St. Thomas", adviser: "Jose Cruz", room: "Room 204", enrolled: 35 },
    { name: "Grade 12 – STEM A", adviser: "Ana Reyes", room: "Room 301", enrolled: 28 },
  ],
  subjects: [
    { code: "MATH7", name: "Mathematics 7", level: "Grade 7" },
    { code: "SCI10", name: "Science 10", level: "Grade 10" },
    { code: "ENG12", name: "English 12", level: "Grade 12" },
  ],
  assignments: [
    { faculty: "Maria Santos", subject: "Mathematics 7", section: "Grade 7 – St. Anne" },
    { faculty: "Jose Cruz", subject: "Science 10", section: "Grade 10 – St. Thomas" },
  ],
  // Student timetable (approved only)
  timetable: [
    { day: "Mon", time: "08:00–09:00", subject: "Mathematics 7", teacher: "Maria Santos", room: "Room 101" },
    { day: "Mon", time: "09:00–10:00", subject: "English 7", teacher: "Ana Reyes", room: "Room 101" },
    { day: "Tue", time: "08:00–09:00", subject: "Science 7", teacher: "Jose Cruz", room: "Room 101" },
    { day: "Wed", time: "10:00–11:00", subject: "Filipino 7", teacher: "Liza Ramos", room: "Room 101" },
    { day: "Thu", time: "08:00–09:00", subject: "Mathematics 7", teacher: "Maria Santos", room: "Room 101" },
  ],
  scheduleProposals: [
    { id: 1, class: "Math 7 – St. Anne", version: 2, status: "pending", by: "Maria Santos", note: "Move Thu Math to avoid room conflict" },
    { id: 2, class: "Science 10 – St. Thomas", version: 1, status: "draft", by: "Jose Cruz", note: "" },
    { id: 3, class: "English 12 – STEM A", version: 3, status: "approved", by: "Ana Reyes", note: "Active timetable" },
  ],
  classList: [
    { no: "2026-0001", name: "John Enquig", username: "Enquig.john" },
    { no: "2026-0002", name: "Jane Dela Cruz", username: "Delacruz.jane" },
    { no: "2026-0003", name: "Mark Villanueva", username: "Villanueva.mark" },
    { no: "2026-0004", name: "Sara Mendoza", username: "Mendoza.sara" },
  ],
  importPreview: [
    { row: 1, name: "John Enquig", id: "2026-0001", result: "matched", detail: "Exists — update enrollment only" },
    { row: 2, name: "Jane Dela Cruz", id: "2026-0002", result: "ok", detail: "New — will create Enquig.john-style account" },
    { row: 3, name: "Jane Dela Cruz", id: "2026-0002", result: "duplicate", detail: "Duplicate student ID in file" },
    { row: 4, name: "(blank)", id: "???", result: "error", detail: "Missing required name + ID" },
  ],
  gradeBatches: [
    { id: 1, class: "Math 7 – St. Anne", quarter: "Q1", status: "submitted", by: "Maria Santos" },
    { id: 2, class: "Math 7 – St. Anne", quarter: "Q2", status: "draft", by: "Maria Santos" },
    { id: 3, class: "Science 10 – St. Thomas", quarter: "Q1", status: "finalized", by: "Jose Cruz" },
  ],
  gradeEntries: [
    { student: "John Enquig", q1: 89, q2: 91, q3: null, q4: null },
    { student: "Jane Dela Cruz", q1: 93, q2: 90, q3: null, q4: null },
    { student: "Mark Villanueva", q1: 85, q2: 87, q3: null, q4: null },
    { student: "Sara Mendoza", q1: 90, q2: 92, q3: null, q4: null },
  ],
  // What the student sees: finalized only
  myGrades: [
    { subject: "Mathematics 7", q1: 89, q2: null, q3: null, q4: null, status: "finalized Q1 only" },
    { subject: "English 7", q1: 92, q2: null, q3: null, q4: null, status: "finalized Q1 only" },
    { subject: "Science 7", q1: 88, q2: null, q3: null, q4: null, status: "finalized Q1 only" },
  ],
  announcements: [
    { title: "Enrollment for A.Y. 2026–2027 is open", audience: "All", date: "2026-10-01", body: "Proceed to the registrar or wait for your section import. New accounts use surname.firstname login." },
    { title: "Q1 grade submission deadline — Oct 24", audience: "Faculty", date: "2026-10-05", body: "Submit Q1 batches for review. Returned batches must be corrected and resubmitted." },
    { title: "Intramurals schedule (approved timetable applies)", audience: "Grade 7", date: "2026-10-08", body: "Classes follow the approved timetable. Draft proposals are not in effect." },
  ],
  audit: [
    { when: "2026-10-10 09:12", actor: "admin", action: "grade.finalized", entity: "grade_submissions#3" },
    { when: "2026-10-09 15:40", actor: "admin", action: "schedule.approved", entity: "schedule_proposals#3 v3" },
    { when: "2026-10-08 11:02", actor: "santos.maria", action: "grade.submitted", entity: "grade_submissions#1 Q1" },
  ],
};
