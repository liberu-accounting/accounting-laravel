<?php

declare(strict_types=1);

namespace App\Modules\Traits;

trait HasModuleHooks
{
    protected array $hooks = [];

    public function registerHook(string $hookName, callable $callback, int $priority = 10): void
    {
        $this->hooks[$hookName][] = ['callback' => $callback, 'priority' => $priority];
        usort($this->hooks[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    public function executeHook(string $hookName, mixed ...$args): mixed
    {
        if (! isset($this->hooks[$hookName])) {
            return null;
        }

        $result = null;
        foreach ($this->hooks[$hookName] as $hook) {
            $result = ($hook['callback'])(...$args);
        }

        return $result;
    }

    public function hasHook(string $hookName): bool
    {
        return ! empty($this->hooks[$hookName]);
    }

    public function clearHook(string $hookName): void
    {
        unset($this->hooks[$hookName]);
    }

    public function getHooks(): array
    {
        return array_keys($this->hooks);
    }
}
