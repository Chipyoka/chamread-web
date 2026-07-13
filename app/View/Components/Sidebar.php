<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class Sidebar extends Component
{
    public array $groups;

    public function __construct()
    {
        $this->groups = $this->buildGroups();
    }

    /**
     * Central place to define nav structure. Edit here, not in the view.
     */
    protected function definitions(): array
    {
        return [
            [
                'type'  => 'link',
                'name'  => 'Dashboard',
                'route' => 'dashboard.index',
                'pattern' => 'dashboard.*',
                'icon'  => 'layout-dashboard',
                'roles' => ['CSA', 'SUPERVISOR', 'ADMIN'],
            ],
            [
                'type'  => 'group',
                'name'  => 'Readings',
                'roles' => ['SUPERVISOR', 'ADMIN'],
                'children' => [
                    ['name' => 'CSAs',     'route' => 'admin.csas.index',    'pattern' => 'admin.csas.*',    'icon' => 'users',     'roles' => ['SUPERVISOR', 'ADMIN']],
                    ['name' => 'Accounts', 'route' => 'admin.accounts.index','pattern' => 'admin.accounts.*','icon' => 'file-text', 'roles' => ['SUPERVISOR', 'ADMIN']],
                    ['name' => 'Readings', 'route' => 'readings.index',      'pattern' => 'readings.*',      'icon' => 'list-todo', 'roles' => ['SUPERVISOR', 'ADMIN']],
                ],
            ],
            [
                'type'  => 'group',
                'name'  => 'Management',
                'roles' => ['ADMIN'],
                'children' => [
                    ['name' => 'Cycles',                 'route' => 'admin.cycles.index',                'pattern' => 'admin.cycles.*',                'icon' => 'repeat',       'roles' => ['ADMIN']],
                    ['name' => 'ERP',                    'route' => 'admin.erp.index',                   'pattern' => 'admin.erp.*',                   'icon' => 'layers',       'roles' => ['ADMIN']],
                    ['name' => 'Functional Definition',  'route' => 'admin.functional-definitions.index','pattern' => 'admin.functional-definitions.*','icon' => 'shapes',       'roles' => ['ADMIN']],
                    ['name' => 'Analytics',              'route' => 'analytics.index',                   'pattern' => 'analytics.*',                   'icon' => 'bar-chart-2',  'roles' => ['ADMIN']],
                ],
            ],
            [
                'type'  => 'group',
                'name'  => 'Systems',
                'roles' => ['ADMIN'],
                'children' => [
                    ['name' => 'Users',   'route' => 'admin.users.index',   'pattern' => 'admin.users.*',   'icon' => 'user-cog', 'roles' => ['ADMIN']],
                    ['name' => 'MRC',     'route' => 'admin.mrc.index',     'pattern' => 'admin.mrc.*',     'icon' => 'server',    'roles' => ['ADMIN']],
                    ['name' => 'Utility', 'route' => 'admin.utility.index', 'pattern' => 'admin.utility.*', 'icon' => 'wrench',    'roles' => ['ADMIN']],
                ],
            ],
        ];
    }

    protected function buildGroups(): array
    {
        $role   = auth()->user()->role ?? 'CSA';
        $groups = [];

        foreach ($this->definitions() as $def) {
            if (! in_array($role, $def['roles'], true)) {
                continue;
            }

            if ($def['type'] === 'link') {
                if (! Route::has($def['route'])) {
                    continue;
                }

                $groups[] = [
                    'type'   => 'link',
                    'name'   => $def['name'],
                    'icon'   => $def['icon'],
                    'href'   => route($def['route']),
                    'active' => request()->routeIs($def['pattern']),
                ];

                continue;
            }

            $children = [];

            foreach ($def['children'] as $child) {
                if (! in_array($role, $child['roles'], true) || ! Route::has($child['route'])) {
                    continue;
                }

                $children[] = [
                    'name'   => $child['name'],
                    'icon'   => $child['icon'],
                    'href'   => route($child['route']),
                    'active' => request()->routeIs($child['pattern']),
                ];
            }

            if (empty($children)) {
                continue;
            }

            $groups[] = [
                'type'          => 'group',
                'name'          => $def['name'],
                'children'      => $children,
                'hasActiveChild'=> collect($children)->contains('active', true),
            ];
        }

        return $groups;
    }

    public function render()
    {
        return view('components.sidebar');
    }
}