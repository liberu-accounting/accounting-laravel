# Approval Engine — Design (P1-1)

**Date:** 2026-07-01
**Status:** Approved (design), pending implementation plan

## Goal

A generic, team-scoped approval engine for the four approvable documents — **Invoice, Bill, Expense, JournalEntry** — replacing today's duplicated, inconsistent ad-hoc approval fields. Supports **threshold routing**, **sequential role-based chains**, and **deadline escalation** (notify + widen to a fallback role, never auto-approve).

## Current state (what this replaces)

- Invoice / Bill / Expense each carry a near-identical `approval_status` / `rejection_reason` / `approved_by` / `approved_at` and duplicated `approve()` / `reject()` methods.
- JournalEntry diverges: `is_approved` (boolean) + `approved_at`.
- No routing, rules, chains, escalation, or approval service exist. One `ExpenseApprovalNotification`.

## Decisions (brainstorm)

1. **Full engine**: sequential chains + deadline escalation.
2. **Approver = Shield role** (per step). Anyone holding the role on the current team may approve. No per-user assignment.
3. **Rule trigger = document type + amount threshold** (`amount ≥ min_amount`). Below threshold → auto-approved.
4. **Escalation = notify + widen to fallback role**; never auto-approves.
5. **UI = Pending Approvals queue page + ApprovalRule config resource** (App panel).

## Data model (3 new tables, all `team_id`-scoped)

### `approval_rules`
Admin-configured routing rules.
- `team_id` (tenant)
- `approvable_type` — enum/string: `Invoice` | `Bill` | `Expense` | `JournalEntry`
- `min_amount` decimal(15,2) default 0 — rule applies when document amount `>= min_amount`
- `steps` json — ordered role list, e.g. `["manager","finance_director"]`
- `deadline_days` int nullable — per-step deadline; null = no escalation
- `fallback_role` string nullable — role that may approve once a step is escalated
- `is_active` bool default true

Matching: for a document, pick the active rule for its `(team_id, approvable_type)` with the **highest `min_amount` that is ≤ the document amount** (most-specific tier). No match → auto-approve.

### `approval_requests`
One per document instance under approval.
- `team_id`
- `approvable_type` / `approvable_id` — morph to the document
- `rule_id` — the matched rule
- `status` — `pending` | `approved` | `rejected`
- `current_step` int — pointer into the steps (1-based)

### `approval_steps`
The materialised chain (one row per step of a request).
- `approval_request_id`
- `position` int (1-based)
- `role` string — required Shield role for this step
- `status` — `pending` | `approved` | `rejected` | `escalated`
- `decided_by` user_id nullable / `decided_at` nullable / `reason` string nullable
- `deadline_at` datetime nullable — set when the step becomes current (`now + rule.deadline_days`)
- `escalated_at` datetime nullable

## `Approvable` trait (on all 4 models)

- `approvalAmount(): float` — the amount the threshold matches against. Each model maps its own field: Invoice → `total_amount`, Bill → `total`, Expense → `amount`, JournalEntry → total debits (entries are balanced). Resolves the "which amount" ambiguity per document.
- `approvalRequest(): MorphOne` — the active/last request.
- `submitForApproval(): void` — match the rule by type + amount for the team. **Match** → create `ApprovalRequest` + `ApprovalStep` rows from `rule.steps`, set the document's `approval_status = 'pending'`, set step-1 `deadline_at`, dispatch `ApprovalRequestedNotification` to step-1 role's users. **No match** → `markApproved()` immediately (auto-approve).
- `markApproved()` / `markRejected(?string $reason)` — set the document's `approval_status`/`approved_by`/`approved_at`(/`rejection_reason`) and fire `Approved` / `Rejected` events. Keeps the existing per-document columns for audit + backward compat.
- **JournalEntry migration**: convert `is_approved` (bool) → shared `approval_status` (string), preserving existing data (`true → 'approved'`, else `'draft'`).

## Behaviour — `ApprovalService`

- `approve(ApprovalStep $step, User $user, ?string $reason = null): void`
  - Guard (money-safe): `$step->status === 'pending'`; and the user holds `$step->role` **on the current team**, OR (`$step->status`/request is escalated AND user holds the rule's `fallback_role`). Otherwise 403.
  - Mark step `approved` (`decided_by`/`decided_at`).
  - If last step → request `approved` → `approvable->markApproved()`.
  - Else advance `current_step`, set next step `deadline_at`, notify next role.
- `reject(ApprovalStep $step, User $user, string $reason): void`
  - Same guard. Mark step + request `rejected`; `approvable->markRejected($reason)`.

All state changes wrapped in a DB transaction. Approval acts are recorded (step rows are the audit trail).

## Escalation — `EscalateApprovalsJob` (scheduled daily)

- Find `approval_steps` where `status = 'pending'` AND `deadline_at < now()` AND `escalated_at IS NULL` (rule has `deadline_days` + `fallback_role`).
- Set `escalated_at = now()`, mark step `escalated` (still actionable), notify current approvers + the `fallback_role`'s users.
- `ApprovalService::approve` then also accepts a user with `fallback_role` for an escalated step.
- **Never auto-approves.** A step stays actionable until a human decides.

## UI (Filament App panel)

- **Pending Approvals** page — lists `approval_steps` that are `pending`/`escalated` AND actionable by the current user (holds `step.role`, or `fallback_role` when escalated) for the current team. Row actions: **Approve** / **Reject (reason)** / **View document**.
- **ApprovalRule** resource (admin-gated) — CRUD on rules: `approvable_type`, `min_amount`, `steps` (role repeater), `deadline_days`, `fallback_role`, `is_active`. Team-scoped.

## Notifications

- `ApprovalRequestedNotification` — to a role's users on the team, via `mail` + `database` (in-app). Reused for escalation (same notification, escalation context).
- Fires on: step becomes current (routing), and on escalation.

## Tenancy & security

- All 3 tables `team_id`-scoped (rules are per-team). Uses the now-real Team scoping (P0-T).
- Approval authority = Shield role on the current team. No cross-team approval.

## Testing

- **Rule matching**: below threshold → auto-approved (no request); at/above → routed; most-specific tier wins.
- **Chain progression**: step-1 approve → step-2 pending → final approve → document `approved` + `Approved` event.
- **Rejection**: reject at any step → request + document `rejected` + `Rejected` event.
- **Role enforcement**: user without the step role → 403; correct role → succeeds.
- **Escalation**: overdue step → `escalated`, fallback role can approve, still never auto-approved.
- **Notification**: dispatched to the right role's users on routing + escalation.
- **JournalEntry data migration**: existing `is_approved=true` → `approval_status='approved'`.

## Out of scope (YAGNI)

- Per-user assignment (role-based only).
- Parallel / any-of steps (sequential chains only).
- Conditions beyond amount threshold (no vendor/category/account matching).
- Auto-approve on timeout.
- A separate `approval_rule_steps` table (JSON role list on the rule is sufficient).
