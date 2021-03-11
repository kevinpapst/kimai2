<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Entity\User;

/**
 * This event is triggered once per side load.
 * It stores all toolbar items, which should be rendered in the upper right corner.
 */
class PageActionsEvent extends ThemeEvent
{
    private $action;
    private $view;
    private $divider = 0;

    public function __construct(User $user, array $payload, string $action, string $view)
    {
        // only for BC reasons, do not access it directly!
        if (!\array_key_exists('actions', $payload)) {
            $payload['actions'] = [];
        }
        // only for BC reasons, do not access it directly!
        if (!\array_key_exists('view', $payload)) {
            $payload['view'] = $view;
        }
        parent::__construct($user, $payload);
        $this->action = $action;
        $this->view = $view;
    }

    public function getActionName(): string
    {
        return $this->action;
    }

    public function isView(string $view): bool
    {
        return $this->view === $view;
    }

    public function isIndexView(): bool
    {
        return $this->isView('index');
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function getActions(): array
    {
        $actions = $this->payload['actions'];

        // move trash to end of list
        if (\array_key_exists('trash', $actions)) {
            $delete = $actions['trash'];
            unset($actions['trash']);
            $actions += ['trash' => $delete];
        }

        return $actions;
    }

    public function hasAction(string $key): bool
    {
        return \array_key_exists($key, $this->payload['actions']);
    }

    public function hasSubmenu(string $submenu): bool
    {
        if (!\array_key_exists($submenu, $this->payload['actions'])) {
            return false;
        }

        return \array_key_exists('children', $this->payload['actions'][$submenu]);
    }

    public function addSubmenu(string $submenu, array $children): void
    {
        $this->payload['actions'][$submenu] = ['children' => $children];
    }

    public function addActionToSubmenu(string $submenu, string $key, array $action): void
    {
        if (\array_key_exists($submenu, $this->payload['actions'])) {
            if (!\array_key_exists('children', $this->payload['actions'][$submenu])) {
                $this->payload['actions'][$submenu]['children'] = [];
            }
        }
        $this->payload['actions'][$submenu]['children'][$key] = $action;
    }

    public function replaceAction(string $key, array $action): void
    {
        $this->payload['actions'][$key] = $action;
    }

    public function addAction(string $key, array $action): void
    {
        if (!\array_key_exists($key, $this->payload['actions'])) {
            $this->payload['actions'][$key] = $action;
        }
    }

    public function removeAction(string $key): void
    {
        if (\array_key_exists($key, $this->payload['actions'])) {
            unset($this->payload['actions'][$key]);
        }
    }

    public function setActions(array $actions): void
    {
        $this->payload['actions'] = $actions;
    }

    public function addDivider(): void
    {
        $key = 'divider' . $this->divider++;
        $this->payload['actions'][$key] = null;
    }

    public function addSearchToggle(): void
    {
        $this->addAction('search', ['class' => 'search-toggle visible-xs-inline']);
    }

    public function addQuickExport(string $url): void
    {
        $this->addAction('download', ['url' => $url, 'class' => 'toolbar-action']);
    }

    public function addCreate(string $url, bool $modal = true): void
    {
        $this->addAction('create', ['url' => $url, 'class' => ($modal ? 'modal-ajax-form' : '')]);
    }

    public function addHelp(string $url): void
    {
        $this->addAction('help', ['url' => $url, 'target' => '_blank']);
    }

    public function addBack(string $url): void
    {
        $this->addAction('back', ['url' => $url, 'translation_domain' => 'actions']);
    }

    public function addDelete(string $url): void
    {
        $this->addAction('trash', ['url' => $url, 'class' => 'modal-ajax-form text-red']);
    }

    public function addColumnToggle(string $modal): void
    {
        $modal = '#' . ltrim($modal, '#');
        $this->addAction('visibility', ['modal' => $modal]);
    }

    public function countActions(?string $submenu = null): int
    {
        if ($submenu !== null) {
            if (!$this->hasAction($submenu)) {
                return 0;
            }

            return \count($this->payload['actions'][$submenu]);
        }

        return \count($this->payload['actions']);
    }
}
