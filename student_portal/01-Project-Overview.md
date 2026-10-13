---
title: SPA Student Portal - Project Overview
type: overview
tags: [spa, portal, overview, v1.0]
source: ../SPA_Student_Portal_Project_Plan.pdf
---

# 01 - Project Overview

> San Pascual Academy (SPA) Student Portal — Planning Blueprint v1.0, Oct 2026.

See also: [[02-Tech-Stack]], [[03-Architecture]], [[04-Phase-Plan]], [[05-Agents-Skills-Tasks]]

## Purpose
Centralized online portal connecting school administration, faculty, and students for enrollment, class scheduling, quarterly grades, announcements, and student account access.

## Project
- **Name:** San Pascual Academy (SPA) Student Portal
- **Type:** Online, role-based school information system
- **Coverage:** Junior High + Senior High, Grades 7–12
- **Login:** Single shared login page, role-routed dashboards. Backend is authority — hiding buttons is not security.

## Objectives
1. One online access point for Admin/Principal, Faculty, Students.
2. Reduce repetitive work via student-list import + automatic account creation.
3. Keep enrollment, assignments, schedules, grades linked to correct academic year + section.
4. Approval workflows for schedules and quarterly grades.
5. Protect student data via RBAC, secure auth, audit logs, backups.

## Initial Scope (v1)
- Shared login + role dashboards
- Accounts/profiles, school years, grade levels, sections, rooms, subjects, enrollments
- CSV/Excel import, auto account generation, validation
- Faculty teaching assignments, schedule proposals, approval, timetable view
- Quarterly grades Q1–Q4: submit, review, correct, finalize
- Announcements, profile edit, password change, online deployment

## Deferred (unless SPA requests)
Attendance, parent accounts, payments, messaging, native mobile app, automated official report cards.

## Users, Roles & Permissions

| Role | Responsibilities | Restrictions |
|------|------------------|--------------|
| **Admin / Principal** | Users, years, levels, sections, rooms, subjects, enrollments, faculty assignments. Approve schedules, review/finalize grades, announcements, audit logs | Sensitive actions logged, limited to authorized admins |
| **Faculty** | Assigned classes + student lists, authorized imports, propose/adjust schedules, enter/submit/correct returned grades | Only assigned classes/sections. Cannot approve own schedule or finalize own grades |
| **Student** | Own profile, section, approved schedule, finalized grades, announcements. Change password, permitted fields/photo | Own records only. Cannot edit grades/enrollment/schedules |

## Functional Requirements (summary)
- **Auth:** shared login, logout, password change/reset, rate limiting, hashed passwords only, `status` check blocks deactivated accounts.
- **Admin:** manage school setup, faculty/student accounts, advisers, section assignments, import preview + duplicates, approve/reject schedules, return/finalize grades, announcements CRUD + archive, audit history.
- **Faculty:** view assignments + class lists, authorized imports only, draft/edit/submit schedule proposals + view review comments, draft/submit/resubmit grades.
- **Student:** generated `username` + initial password, forced password change before full dashboard, view schedule/grades/announcements, edit allowed fields + photo + password.

## Key Business Rules
- Enrollments tied to school year + section. History retained per school policy.
- Grade entry must match enrollment + teaching assignment + grading period / school year.
- DB constraints where practical + backend validation/transactions for cross-record rules.
- Details: [[03-Architecture#Core Workflows]]
