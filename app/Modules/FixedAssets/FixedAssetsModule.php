<?php

declare(strict_types=1);

namespace App\Modules\FixedAssets;

use App\Models\Module;
use App\Modules\BaseModule;

/**
 * Fixed Assets feature module. Like InventoryModule, this gates the existing
 * asset code/resources by enable state without relocating models, migrations
 * or renaming tables.
 */
class FixedAssetsModule extends BaseModule
{
    public const KEY = 'FixedAssets';

    /**
     * Whether the fixed-assets feature is active. Reads the module's DB record;
     * defaults to enabled when unmanaged so the feature is non-breaking.
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
