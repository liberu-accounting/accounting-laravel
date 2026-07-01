<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Exceptions\ApprovalDeniedException;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\User;
use App\Services\ApprovalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PendingApprovals extends Page implements HasTable
{
    use InteractsWithTable;

    #[\Override]
    protected string $view = 'filament.app.pages.pending-approvals';

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    #[\Override]
    protected static ?string $title = 'Pending Approvals';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->actionableStepsQuery())
            ->columns([
                TextColumn::make('document')
                    ->label('Document')
                    ->state(function (ApprovalStep $record): string {
                        $request = $record->request;

                        if (! $request instanceof ApprovalRequest) {
                            return '—';
                        }

                        return class_basename((string) $request->approvable_type).' #'.(string) $request->approvable_id;
                    }),
                TextColumn::make('position')->label('Step'),
                TextColumn::make('role')->label('Role'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('deadline_at')->label('Deadline')->dateTime()->placeholder('—'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ApprovalStep $record): void {
                        $user = $this->currentUser();
                        $this->act(fn (ApprovalService $service) => $service->approve($record, $user), 'Step approved');
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')->required(),
                    ])
                    ->action(function (ApprovalStep $record, array $data): void {
                        $user = $this->currentUser();
                        $reasonInput = $data['reason'] ?? null;
                        $reason = is_string($reasonInput) ? $reasonInput : '';
                        $this->act(fn (ApprovalService $service) => $service->reject($record, $user, $reason), 'Step rejected');
                    }),
            ])
            ->emptyStateHeading('No pending approvals');
    }

    /**
     * Steps in the current user's team that are actionable by them right now
     * (pending/escalated + role-guarded via ApprovalService::canAct).
     *
     * @return Builder<ApprovalStep>
     */
    public function actionableStepsQuery(): Builder
    {
        $user = $this->currentUser();
        $teamId = (int) ($user->current_team_id ?? -1);
        $service = app(ApprovalService::class);

        $actionableIds = ApprovalStep::query()
            ->whereIn('status', [ApprovalStep::STATUS_PENDING, ApprovalStep::STATUS_ESCALATED])
            ->whereHas('request', fn (Builder $query): Builder => $query->where('team_id', $teamId))
            ->with('request.rule')
            ->get()
            ->filter(fn (ApprovalStep $step): bool => $service->canAct($step, $user))
            ->pluck('id');

        return ApprovalStep::query()->whereIn('id', $actionableIds);
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    private function act(\Closure $action, string $successMessage): void
    {
        try {
            $action(app(ApprovalService::class));
            Notification::make()->title($successMessage)->success()->send();
        } catch (ApprovalDeniedException $e) {
            Notification::make()->title('Action denied')->body($e->getMessage())->danger()->send();
        }
    }
}
