<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Menu;
use Spatie\Menu\Item;
use Spatie\Menu\Laravel\Link;
use Spatie\Menu\Laravel\Menu as SpatieMenu;

class MenuService
{
    public function buildMenu()
    {
        $menuItems = Menu::whereNull('parent_id')->orderBy('order')->get();

        $menu = SpatieMenu::new()
            ->addClass('flex items-center space-x-4')
            ->addItemClass('px-4 py-2 rounded-md bg-green-700 text-white hover:bg-green-600 transition duration-300 ease-in-out');

        $this->createMenuItems($menuItems)->each(function (Item $item) use ($menu): void {
            $menu->add($item);
        });

        return $menu;
    }

    private function createMenuItems($items)
    {
        return $items->map(function ($item): \Spatie\Menu\Menu|Link {
            if ($item->children->count() > 0) {
                $submenu = SpatieMenu::new()
                    ->addClass('absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1')
                    ->addItemClass('block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100');

                $this->createMenuItems($item->children)->each(function (Item $subItem) use ($submenu): void {
                    $submenu->add($subItem);
                });

                return SpatieMenu::new()
                    ->add(Link::to($this->safeUrl($item->url), e($item->name))->addClass('relative group'))
                    ->add($submenu->addClass('hidden group-hover:block'));
            }

            return Link::to($this->safeUrl($item->url), e($item->name));
        });
    }

    // Spatie\Menu\Link renders text and href raw (see vendor render()), so escape
    // the user-supplied name with e() and reject dangerous URL schemes here.
    private function safeUrl(?string $url): string
    {
        $url = (string) $url;
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (in_array($scheme, ['javascript', 'data', 'vbscript'], true)) {
            return '#';
        }

        return e($url);
    }
}
