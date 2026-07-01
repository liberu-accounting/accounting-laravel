# Approval Engine Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A team-scoped approval engine (threshold routing → sequential role chains → deadline escalation) for Invoice, Bill, Expense, JournalEntry.

**Architecture:** 3 tenant-scoped tables (`approval_rules`, `approval_requests`, `approval_steps`) + an `Approvable` trait on the four documents. `ApprovalService` runs approve/reject with a Shield-role guard and advances the chain. An `EscalateApprovalsJob` (scheduled) widens overdue steps to a fallback role. Filament App panel gets a Pending Approvals page and an ApprovalRule resource.

**Tech Stack:** Laravel 13, PHP 8.5, Filament 5, Spatie Permission (Shield), PHPUnit (extend `Tests\TestCase`, SQLite `:memory:`).

## Global Constraints

- `declare(strict_types=1);` on every PHP file; `#[\Override]` on overrides.
- All new tables carry `team_id`; scope every query by `team_id` (tenant = current team). Match GL/controller pattern: `->where('team_id', $teamId)`; when resolving from a user use `auth()->user()->current_team_id`.
- Tests: `docker compose exec -T php-fpm php artisan test --filter=<Name>` from repo root.
- PHPStan level max must stay green: run `docker compose exec -T php-fpm vendor/bin/phpstan analyse <files>`; type new closures/params. Regenerate baseline only for pre-existing-pattern residue.
- Approvable models: `Invoice`, `Bill`, `Expense`, `JournalEntry`. `approvable_type` stored as the short class name (`'Invoice'`, etc.) via a morph map OR full class — use Laravel's default morph (full class string) for simplicity; be consistent.
- Money-safe: only a user holding the step's role (or the rule's `fallback_role` on an escalated step) **on the current team** may act.

**Parallelization:** Task 1 → Task 2 → Task 3 are sequential (foundation). After Task 3, Tasks 4 (notification), 5 (job), 6 (rule resource), 7 (queue page) are file-disjoint and may run in parallel.

---

### Task 1: Schema + models

**Files:**
- Create: `database/migrations/2026_12_04_000001_create_approval_rules_table.php`
- Create: `database/migrations/2026_12_04_000002_create_approval_requests_table.php`
- Create: `database/migrations/2026_12_04_000003_create_approval_steps_table.php`
- Create: `app/Models/ApprovalRule.php`, `app/Models/ApprovalRequest.php`, `app/Models/ApprovalStep.php`
- Test: `tests/Unit/Models/ApprovalModelsTest.php`

**Interfaces produced:**
- `ApprovalRule`: `$fillable` incl. `team_id, approvable_type, min_amount, steps(json→array), deadline_days, fallback_role, is_active`; casts `steps=array`, `min_amount=decimal:2`, `is_active=bool`, `deadline_days=int`. Scope helper `ApprovalRule::matchFor(string $type, float $amount, int $teamId): ?self` — active rule for `(team_id, type)` with the greatest `min_amount <= $amount`, or null.
- `ApprovalRequest`: `$fillable` incl. `team_id, approvable_type, approvable_id, rule_id, status, current_step`; `approvable(): MorphTo`; `steps(): HasMany` (ordered by `position`); `rule(): BelongsTo`. Consts `STATUS_PENDING='pending'`, `STATUS_APPROVED='approved'`, `STATUS_REJECTED='rejected'`.
- `ApprovalStep`: `$fillable` incl. `approval_request_id, position, role, status, decided_by, decided_at, reason, deadline_at, escalated_at`; casts `decided_at/deadline_at/escalated_at=datetime`, `position=int`; `request(): BelongsTo`; `decider(): BelongsTo(User, decided_by)`. Consts `STATUS_PENDING`, `STATUS_APPROVED`, `STATUS_REJECTED`, `STATUS_ESCALATED`.

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);
namespace Tests\Unit\Models;

use App\Models\ApprovalRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_match_for_picks_the_most_specific_active_tier(): void
    {
        ApprovalRule::create(['team_id' => 1, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager'], 'is_active' => true]);
        ApprovalRule::create(['team_id' => 1, 'approvable_type' => 'Bill', 'min_amount' => 5000, 'steps' => ['manager', 'finance_director'], 'is_active' => true]);
        ApprovalRule::create(['team_id' => 1, 'approvable_type' => 'Bill', 'min_amount' => 5000, 'steps' => ['x'], 'is_active' => false]);

        $this->assertSame(['manager'], ApprovalRule::matchFor('Bill', 100, 1)?->steps);
        $this->assertSame(['manager', 'finance_director'], ApprovalRule::matchFor('Bill', 9000, 1)?->steps);
        $this->assertNull(ApprovalRule::matchFor('Bill', 9000, 2), 'other team has no rule');
    }
}
```

- [ ] **Step 2: Run test to verify it fails** — `--filter=ApprovalModels`; expected FAIL (class/table missing).

- [ ] **Step 3: Write the migrations**

`create_approval_rules_table.php`:
```php
<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->index();
            $table->string('approvable_type');
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->json('steps');
            $table->unsignedInteger('deadline_days')->nullable();
            $table->string('fallback_role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['team_id', 'approvable_type', 'is_active']);
        });
    }
    public function down(): void { Schema::dropIfExists('approval_rules'); }
};
```

`create_approval_requests_table.php`:
```php
<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->index();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->foreignId('rule_id')->constrained('approval_rules');
            $table->string('status')->default('pending');
            $table->unsignedInteger('current_step')->default(1);
            $table->timestamps();
            $table->index(['approvable_type', 'approvable_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('approval_requests'); }
};
```

`create_approval_steps_table.php`:
```php
<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('role');
            $table->string('status')->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'deadline_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('approval_steps'); }
};
```

- [ ] **Step 4: Write the models**

`ApprovalRule.php`:
```php
<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRule extends Model
{
    protected $fillable = ['team_id', 'approvable_type', 'min_amount', 'steps', 'deadline_days', 'fallback_role', 'is_active'];
    protected $casts = ['steps' => 'array', 'min_amount' => 'decimal:2', 'deadline_days' => 'integer', 'is_active' => 'boolean'];

    public static function matchFor(string $type, float $amount, int $teamId): ?self
    {
        return self::query()
            ->where('team_id', $teamId)
            ->where('approvable_type', $type)
            ->where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->orderByDesc('min_amount')
            ->first();
    }
}
```

`ApprovalRequest.php` and `ApprovalStep.php`: standard models with the `$fillable`, `$casts`, relations, and status consts listed in **Interfaces produced** above. `ApprovalRequest::steps()` = `hasMany(ApprovalStep::class)->orderBy('position')`. `ApprovalStep::request()` = `belongsTo(ApprovalRequest::class, 'approval_request_id')`.

- [ ] **Step 5: Run test to verify it passes** — `--filter=ApprovalModels`; expected PASS.
- [ ] **Step 6: Commit** — `git add database/migrations app/Models/Approval*.php tests/Unit/Models/ApprovalModelsTest.php && git commit -m "feat(approval): schema + models"`

---

### Task 2: Approvable trait + model wiring + JournalEntry data migration

**Files:**
- Create: `app/Concerns/Approvable.php`
- Modify: `app/Models/Invoice.php`, `app/Models/Bill.php`, `app/Models/Expense.php`, `app/Models/JournalEntry.php` (add `use Approvable;` + `approvalAmount()`; remove the duplicated `approve()`/`reject()` bodies, delegating to the trait's `markApproved()/markRejected()`).
- Create: `database/migrations/2026_12_04_000004_convert_journal_entry_is_approved.php` (adds `approval_status` string to `journal_entries`, backfills from `is_approved`).
- Create: `app/Events/ApprovableApproved.php`, `app/Events/ApprovableRejected.php` (generic; keep existing `InvoiceApproved` firing too for back-compat).
- Test: `tests/Feature/Approval/ApprovableTraitTest.php`

**Interfaces:**
- Consumes: `ApprovalRule::matchFor`, `ApprovalRequest`, `ApprovalStep` (Task 1); `ApprovalRequestedNotification` (Task 4 — reference by class name `App\Notifications\ApprovalRequestedNotification`; guard the dispatch so this task's tests pass before Task 4 lands by using `Notification::fake()` in tests).
- Produces: trait methods used by `ApprovalService` (Task 3) and Filament (Task 7):
  - `approvalAmount(): float`
  - `approvalRequest(): MorphOne` (latest request)
  - `submitForApproval(): void`
  - `markApproved(): void` / `markRejected(?string $reason): void`

- [ ] **Step 1: Write the failing test**

```php
<?php
declare(strict_types=1);
namespace Tests\Feature\Approval;

use App\Models\ApprovalRule;
use App\Models\Bill;
use App\Models\User;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApprovableTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_below_threshold_auto_approves_no_request(): void
    {
        Notification::fake();
        $team = $this->actingTeam();
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 5000, 'steps' => ['manager'], 'is_active' => true]);

        $bill = Bill::factory()->create(['team_id' => $team, 'total' => 100, 'approval_status' => 'draft']);
        $bill->submitForApproval();

        $this->assertSame('approved', $bill->fresh()->approval_status);
        $this->assertNull($bill->approvalRequest()->first());
    }

    public function test_at_threshold_creates_request_and_pending_steps(): void
    {
        Notification::fake();
        $team = $this->actingTeam();
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 5000, 'steps' => ['manager', 'finance_director'], 'deadline_days' => 3, 'is_active' => true]);

        $bill = Bill::factory()->create(['team_id' => $team, 'total' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();

        $req = $bill->approvalRequest()->first();
        $this->assertNotNull($req);
        $this->assertSame('pending', $bill->fresh()->approval_status);
        $this->assertCount(2, $req->steps);
        $this->assertNotNull($req->steps->first()->deadline_at, 'step 1 has a deadline');
    }

    private function actingTeam(): int
    {
        $user = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($user);
        $user = $user->fresh();
        $this->actingAs($user);
        return (int) $user->current_team_id;
    }
}
```
(If `Bill` factory lacks `total`/`approval_status`, set them explicitly; confirm the Bill amount column name — spec maps Bill→`total`.)

- [ ] **Step 2: Run to verify it fails** — `--filter=ApprovableTrait`; FAIL (`submitForApproval` missing).

- [ ] **Step 3: Write the trait**

```php
<?php
declare(strict_types=1);
namespace App\Concerns;

use App\Events\ApprovableApproved;
use App\Events\ApprovableRejected;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRule;
use App\Models\ApprovalStep;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

trait Approvable
{
    abstract public function approvalAmount(): float;

    public function approvalRequest(): MorphOne
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
    }

    public function submitForApproval(): void
    {
        $teamId = (int) ($this->team_id ?? auth()->user()?->current_team_id);
        $type = class_basename($this);
        $rule = ApprovalRule::matchFor($type, $this->approvalAmount(), $teamId);

        if (! $rule instanceof ApprovalRule) {
            $this->markApproved();
            return;
        }

        DB::transaction(function () use ($rule, $teamId, $type): void {
            $request = ApprovalRequest::create([
                'team_id' => $teamId,
                'approvable_type' => $this->getMorphClass(),
                'approvable_id' => $this->getKey(),
                'rule_id' => $rule->getKey(),
                'status' => ApprovalRequest::STATUS_PENDING,
                'current_step' => 1,
            ]);

            foreach ($rule->steps as $i => $role) {
                ApprovalStep::create([
                    'approval_request_id' => $request->getKey(),
                    'position' => $i + 1,
                    'role' => $role,
                    'status' => ApprovalStep::STATUS_PENDING,
                    'deadline_at' => $i === 0 && $rule->deadline_days ? now()->addDays($rule->deadline_days) : null,
                ]);
            }

            $this->forceFill(['approval_status' => 'pending'])->save();
            ApprovalRequestedNotification::dispatchToRole($request->steps()->first(), $teamId);
        });
    }

    public function markApproved(): void
    {
        $this->forceFill([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ])->save();
        event(new ApprovableApproved($this));
    }

    public function markRejected(?string $reason): void
    {
        $this->forceFill([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
        ])->save();
        event(new ApprovableRejected($this, $reason));
    }
}
```
Note: `ApprovalRequestedNotification::dispatchToRole(ApprovalStep $step, int $teamId): void` is defined in Task 4. Until Task 4 lands, tests use `Notification::fake()` so no mail/db is sent; the static method must still exist — so **Task 4 must merge before this task's notification line executes in a non-faked path**. Build order: 1 → 4 → 2 → 3, OR stub `dispatchToRole` in Task 4 first. Recommended: do Task 4 immediately after Task 1, before Task 2.

- [ ] **Step 4: Wire the four models** — add `use Approvable;`, implement `approvalAmount()` (Invoice `return (float) $this->total_amount;`, Bill `return (float) $this->total;`, Expense `return (float) $this->amount;`, JournalEntry `return (float) $this->lines()->sum('debit_amount');`). Replace each existing `approve()`/`reject()` body with a call to `markApproved()`/`markRejected()` (keep the public method names for back-compat; Invoice keeps firing `InvoiceApproved` too).

- [ ] **Step 5: JournalEntry data migration** — add `approval_status` string default `'draft'` to `journal_entries`; `up()` also runs `DB::table('journal_entries')->where('is_approved', true)->update(['approval_status' => 'approved'])`. Leave `is_approved` column (drop in a later cleanup).

- [ ] **Step 6: Run to verify pass** — `--filter=ApprovableTrait`; PASS.
- [ ] **Step 7: Commit** — `git commit -m "feat(approval): Approvable trait + model wiring + JE status migration"`

---

### Task 3: ApprovalService (approve / reject / chain progression / role guard)

**Files:**
- Create: `app/Services/ApprovalService.php`
- Create: `app/Exceptions/ApprovalDeniedException.php` (extends `\RuntimeException`)
- Test: `tests/Feature/Approval/ApprovalServiceTest.php`

**Interfaces:**
- Consumes: `ApprovalStep`, `ApprovalRequest`, the `Approvable` trait's `markApproved/markRejected`, Spatie `$user->hasRole($role)` (Shield), `ApprovalRequestedNotification::dispatchToRole` (Task 4).
- Produces:
  - `approve(ApprovalStep $step, User $user, ?string $reason = null): void`
  - `reject(ApprovalStep $step, User $user, string $reason): void`
  - `canAct(ApprovalStep $step, User $user): bool`

- [ ] **Step 1: Write the failing test** (chain progression + role guard + reject)

```php
<?php
declare(strict_types=1);
namespace Tests\Feature\Approval;

use App\Models\ApprovalRule;
use App\Models\ApprovalStep;
use App\Models\Bill;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\TeamManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_chain_approval_marks_document_approved(): void
    {
        Notification::fake();
        [$team, $manager] = $this->userWithRole('manager');
        $fd = $this->userWithRoleOnTeam('finance_director', $team);
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager', 'finance_director'], 'is_active' => true]);

        $bill = Bill::factory()->create(['team_id' => $team, 'total' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();
        $svc = app(ApprovalService::class);

        $svc->approve($bill->approvalRequest()->first()->steps()->where('position', 1)->first(), $manager);
        $this->assertSame('pending', $bill->fresh()->approval_status, 'still pending after step 1');

        $svc->approve($bill->approvalRequest()->first()->steps()->where('position', 2)->first(), $fd);
        $this->assertSame('approved', $bill->fresh()->approval_status);
    }

    public function test_wrong_role_is_denied(): void
    {
        Notification::fake();
        [$team, $manager] = $this->userWithRole('manager');
        $intruder = $this->userWithRoleOnTeam('clerk', $team);
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager'], 'is_active' => true]);
        $bill = Bill::factory()->create(['team_id' => $team, 'total' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();
        $step = $bill->approvalRequest()->first()->steps()->first();

        $this->expectException(\App\Exceptions\ApprovalDeniedException::class);
        app(ApprovalService::class)->approve($step, $intruder);
    }

    public function test_reject_marks_document_rejected(): void
    {
        Notification::fake();
        [$team, $manager] = $this->userWithRole('manager');
        ApprovalRule::create(['team_id' => $team, 'approvable_type' => 'Bill', 'min_amount' => 0, 'steps' => ['manager'], 'is_active' => true]);
        $bill = Bill::factory()->create(['team_id' => $team, 'total' => 9000, 'approval_status' => 'draft']);
        $bill->submitForApproval();

        app(ApprovalService::class)->reject($bill->approvalRequest()->first()->steps()->first(), $manager, 'over budget');
        $this->assertSame('rejected', $bill->fresh()->approval_status);
        $this->assertSame('over budget', $bill->fresh()->rejection_reason);
    }

    /** @return array{0:int,1:User} */
    private function userWithRole(string $role): array
    {
        $user = User::factory()->create();
        app(TeamManagementService::class)->createPersonalTeamForUser($user);
        $user = $user->fresh();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);
        return [(int) $user->current_team_id, $user];
    }

    private function userWithRoleOnTeam(string $role, int $team): User
    {
        $user = User::factory()->create(['current_team_id' => $team]);
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);
        return $user;
    }
}
```

- [ ] **Step 2: Run to verify it fails** — `--filter=ApprovalService`; FAIL.

- [ ] **Step 3: Write the service**

```php
<?php
declare(strict_types=1);
namespace App\Services;

use App\Exceptions\ApprovalDeniedException;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function canAct(ApprovalStep $step, User $user): bool
    {
        if (! in_array($step->status, [ApprovalStep::STATUS_PENDING, ApprovalStep::STATUS_ESCALATED], true)) {
            return false;
        }
        if ($user->hasRole($step->role)) {
            return true;
        }
        $fallback = $step->request->rule->fallback_role;
        return $step->status === ApprovalStep::STATUS_ESCALATED && $fallback && $user->hasRole($fallback);
    }

    public function approve(ApprovalStep $step, User $user, ?string $reason = null): void
    {
        $this->guard($step, $user);
        DB::transaction(function () use ($step, $user, $reason): void {
            $step->forceFill(['status' => ApprovalStep::STATUS_APPROVED, 'decided_by' => $user->getKey(), 'decided_at' => now(), 'reason' => $reason])->save();
            $request = $step->request;
            $next = $request->steps()->where('position', '>', $step->position)->orderBy('position')->first();

            if ($next instanceof ApprovalStep) {
                $rule = $request->rule;
                $next->forceFill(['deadline_at' => $rule->deadline_days ? now()->addDays($rule->deadline_days) : null])->save();
                $request->forceFill(['current_step' => $next->position])->save();
                ApprovalRequestedNotification::dispatchToRole($next, (int) $request->team_id);
                return;
            }

            $request->forceFill(['status' => ApprovalRequest::STATUS_APPROVED])->save();
            $request->approvable->markApproved();
        });
    }

    public function reject(ApprovalStep $step, User $user, string $reason): void
    {
        $this->guard($step, $user);
        DB::transaction(function () use ($step, $user, $reason): void {
            $step->forceFill(['status' => ApprovalStep::STATUS_REJECTED, 'decided_by' => $user->getKey(), 'decided_at' => now(), 'reason' => $reason])->save();
            $request = $step->request;
            $request->forceFill(['status' => ApprovalRequest::STATUS_REJECTED])->save();
            $request->approvable->markRejected($reason);
        });
    }

    private function guard(ApprovalStep $step, User $user): void
    {
        if (! $this->canAct($step, $user)) {
            throw new ApprovalDeniedException('User may not act on this approval step.');
        }
    }
}
```

- [ ] **Step 4: Run to verify pass** — `--filter=ApprovalService`; PASS.
- [ ] **Step 5: PHPStan** — `phpstan analyse app/Services/ApprovalService.php app/Concerns/Approvable.php`; green (type any closures).
- [ ] **Step 6: Commit** — `git commit -m "feat(approval): ApprovalService approve/reject + role guard"`

---

### Task 4: ApprovalRequestedNotification (mail + database)

**Files:**
- Create: `app/Notifications/ApprovalRequestedNotification.php`
- Test: `tests/Feature/Approval/ApprovalNotificationTest.php`

**Interfaces produced:**
- `ApprovalRequestedNotification` implements `ShouldQueue`, `via = ['mail','database']`.
- Static `dispatchToRole(ApprovalStep $step, int $teamId): void` — resolves users holding `$step->role` (Spatie `role()` scope) who belong to team `$teamId`, and `Notification::send($users, new self($step))`. (Team membership: users where `current_team_id = $teamId` OR members of that team — use the simplest correct query available; confirm against `User`/`team_user`.)

- [ ] **Step 1: Write the failing test** — assert `Notification::fake()`; after `dispatchToRole`, `assertSentTo` the role-holding user, not others.
- [ ] **Step 2: Run — FAIL.**
- [ ] **Step 3: Implement** the notification (mail: "Approval required for {type} #{id}"; database payload: `approval_step_id`, `approvable_type`, `approvable_id`, `role`). Model after `app/Notifications/ExpenseApprovalNotification.php`.
- [ ] **Step 4: Run — PASS.**
- [ ] **Step 5: Commit** — `git commit -m "feat(approval): ApprovalRequestedNotification"`

---

### Task 5: EscalateApprovalsJob + schedule

**Files:**
- Create: `app/Jobs/EscalateApprovalsJob.php`
- Modify: `app/Console/Kernel.php` (or `bootstrap/app.php` schedule) — `$schedule->job(EscalateApprovalsJob::class)->daily();`
- Test: `tests/Feature/Approval/EscalateApprovalsJobTest.php`

**Interfaces:**
- Consumes: `ApprovalStep`, `ApprovalRule.fallback_role`, `ApprovalService::canAct` (fallback path), `ApprovalRequestedNotification`.

- [ ] **Step 1: Write the failing test** — create a routed request with `deadline_days`+`fallback_role`; set step-1 `deadline_at` to the past; run the job; assert step-1 `status='escalated'` + `escalated_at` set; assert a user with only `fallback_role` can now `approve()` (previously denied); assert the document is NOT auto-approved by the job.
- [ ] **Step 2: Run — FAIL.**
- [ ] **Step 3: Implement** — `handle()`: `ApprovalStep::where('status','pending')->whereNotNull('deadline_at')->where('deadline_at','<',now())->whereNull('escalated_at')->each(fn($s) => ...)` → set `escalated_at`, `status='escalated'`, `ApprovalRequestedNotification::dispatchToRole($s, $s->request->team_id)` (+ notify fallback role). Never approve.
- [ ] **Step 4: Run — PASS.**
- [ ] **Step 5: Commit** — `git commit -m "feat(approval): escalation job + daily schedule"`

---

### Task 6: Filament ApprovalRule resource (admin config)

**Files:**
- Create: `app/Filament/App/Resources/ApprovalRules/ApprovalRuleResource.php` + `Pages/{List,Create,Edit}ApprovalRule.php`
- Test: `tests/Feature/Approval/ApprovalRuleResourceTest.php` (Livewire/Filament resource smoke test: create a rule via the form; assert persisted with `team_id`).

**Interfaces:** Consumes `ApprovalRule` (Task 1). Team-scoped like other App resources (it carries `team_id` → Filament stamps/scopes it). Form: `approvable_type` (Select: Invoice/Bill/Expense/JournalEntry), `min_amount` (numeric), `steps` (Repeater of role Select → stored as array), `deadline_days` (numeric, nullable), `fallback_role` (Select role, nullable), `is_active` (Toggle).

- [ ] Steps: model an existing App resource (e.g. `PaymentTerms/PaymentTermResource.php`) for structure; write the smoke test first (FAIL → implement → PASS); commit. Gate visibility to an admin role.

---

### Task 7: Filament Pending Approvals page (queue + actions)

**Files:**
- Create: `app/Filament/App/Pages/PendingApprovals.php` + view `resources/views/filament/app/pages/pending-approvals.blade.php`
- Test: `tests/Feature/Approval/PendingApprovalsPageTest.php`

**Interfaces:** Consumes `ApprovalStep`, `ApprovalService` (Task 3), `ApprovalService::canAct`. Query: steps where `status IN ('pending','escalated')` in the current team's requests AND `canAct(currentUser)` — i.e. `role` held by user, or `escalated` + `fallback_role`. Actions per row: **Approve** (`ApprovalService::approve`), **Reject** (form with reason → `ApprovalService::reject`), **View** (link to the document resource).

- [ ] Steps: write a test that a manager sees a pending step for their team and approving it advances the chain (reuse ApprovalServiceTest helpers); FAIL → implement the Filament page (table of steps, actions calling the service) → PASS; commit. Model the page after an existing Filament custom page if one exists; otherwise a standard `Filament\Pages\Page` with a table.

---

## Self-Review

- **Spec coverage:** rules/threshold (T1 `matchFor`, T2 auto-vs-route) · chains (T3 progression) · role guard (T3) · escalation notify+fallback, never auto-approve (T5) · JournalEntry migration (T2) · notifications mail+db (T4) · queue page + rule resource (T6, T7) · tenancy team_id (all) · tests (each task). Covered.
- **Placeholder scan:** T6/T7 reference "model an existing resource/page" rather than reproducing ~200 lines of Filament boilerplate — intentional (Global Constraint: follow existing patterns); the interfaces + form fields + query + actions are specified exactly. All logic-bearing tasks (T1–T5) have complete code.
- **Type consistency:** `dispatchToRole(ApprovalStep, int)`, `approve(ApprovalStep, User, ?string)`, `reject(ApprovalStep, User, string)`, `canAct(ApprovalStep, User): bool`, `approvalAmount(): float`, status consts — consistent across tasks.
- **Build order note:** run **Task 4 immediately after Task 1** (before Task 2/3) so `ApprovalRequestedNotification::dispatchToRole` exists when the trait/service dispatch it. Then 2 → 3, then 5/6/7 in parallel.
