<?php

declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Models\Module;
use App\Modules\BaseModule;

/**
 * Inventory feature module — the reference conversion of an existing feature
 * into the module framework. The inventory models/migrations/services stay in
 * their current locations (DB table names unchanged); this module wraps them in
 * the enable/disable lifecycle and gates the Filament resource on its state.
 */
class InventoryModule extends BaseModule
{
    public const KEY = 'Inventory';

    /**
     * Whether the inventory feature is active. Reads the module's DB record;
     * when unmanaged (no record / table absent) it defaults to enabled so the
     * feature is non-breaking out of the box.
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
