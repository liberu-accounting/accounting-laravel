<?php

declare(strict_types=1);

namespace App\Modules\Reconciliation;

use App\Models\Module;
use App\Modules\BaseModule;

/**
 * Reconciliation feature module. Gates the bank-statement reconcile workflow
 * (reconcile + discrepancies actions) by enable state, without relocating the
 * ReconciliationService or the BankStatement resource. Bank statements remain
 * available; only the reconciliation actions are gated.
 */
class ReconciliationModule extends BaseModule
{
    public const KEY = 'Reconciliation';

    /**
     * Whether the reconciliation feature is active. Reads the module's DB
     * record; defaults to enabled when unmanaged so it's non-breaking.
     */
    public static function isActive(): bool
    {
        try {
            $record = Module::findByName(self::KEY);

            if ($record !== null) {
                return (bool) $record->enabled;
            }
        } catch (\Throwable) {
            // modules table not migrated yet — treat as enabled.
        }

        return true;
    }

    protected function onEnable(): void
    {
        $this->setEnabled(true);
    }

    protected function onDisable(): void
    {
        $this->setEnabled(false);
    }

    private function setEnabled(bool $enabled): void
    {
        Module::updateOrCreate(
            ['name' => self::KEY],
            ['enabled' => $enabled, 'version' => $this->getVersion(), 'description' => $this->getDescription()],
        );
    }
}
