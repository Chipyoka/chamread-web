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
                'type'  => 'group',
                'name'  => 'dashboard',
                'icon'  => 'layout-dashboard',
                'roles' => ['SUPERVISOR', 'ADMIN'],
                'children' => [
                    ['name' => 'Overview',     'route' => 'dashboard.dashboard.index',    'pattern' => 'dashboard.dashboard.*',    'icon' => 'layout-dashboard',     'roles' => ['SUPERVISOR', 'ADMIN']],
                    ['name' => 'Supervisor',     'route' => 'dashboard.supervisor.index',    'pattern' => 'dashboard.supervisor.*',    'icon' => 'hard-hat',     'roles' => ['SUPERVISOR', 'ADMIN']],
                    ['name' => 'Technical',     'route' => 'dashboard.technical.index',    'pattern' => 'dashboard.technical.*',    'icon' => 'wrench',     'roles' => ['SUPERVISOR', 'ADMIN']],
                ],
            ],
            [
                'type'  => 'group',
                'name'  => 'Readings',
                'icon'  => 'layout-dashboard',
                'roles' => ['SUPERVISOR', 'ADMIN'],
                'children' => [
                    ['name' => 'CSAs',     'route' => 'readings.csas.index',    'pattern' => 'readings.csas.*',    'icon' => 'users',     'roles' => ['SUPERVISOR', 'ADMIN']],
                    ['name' => 'Accounts', 'route' => 'readings.accounts.index','pattern' => 'readings.accounts.*','icon' => 'file-text', 'roles' => ['SUPERVISOR', 'ADMIN']],
                    ['name' => 'Meter Readings', 'route' => 'readings.meter-readings.index',      'pattern' => 'readings.meter-readings.*',      'icon' => 'list-todo', 'roles' => ['SUPERVISOR', 'ADMIN']],
                ],
            ],
            [
                'type'  => 'group',
                'name'  => 'Management',
                'icon'  => 'layout-dashboard',
                'roles' => ['ADMIN'],
                'children' => [
                    ['name' => 'ERP',      'route' => 'management.erp.index',                   'pattern' => 'management.erp.*',                   'icon' => 'layers',       'roles' => ['ADMIN']],
                    ['name' => 'Billing Cycles',  'route' => 'management.cycles.index',                'pattern' => 'management.cycles.*',                'icon' => 'calendar',       'roles' => ['ADMIN']],
                    ['name' => 'Zones',      'route' => 'management.zones.index',                   'pattern' => 'management.zones.*',                   'icon' => 'map',       'roles' => ['ADMIN']],
                    ['name' => 'Reports',              'route' => 'management.analytics.index',                   'pattern' => 'management.analytics.*',       'icon' => 'file-text',  'roles' => ['ADMIN']],
                ],
            ],
            [
                'type'  => 'group',
                'name'  => 'System Admin',
                'icon'  => 'layout-dashboard',
                'roles' => ['ADMIN'],
                'children' => [
                    ['name' => 'Users',   'route' => 'systems.users.index',   'pattern' => 'systems.users.*',   'icon' => 'users', 'roles' => ['ADMIN']],
                    ['name' => 'MRC',     'route' => 'systems.mrc.index',     'pattern' => 'systems.mrc.*',     'icon' => 'sliders-horizontal',    'roles' => ['ADMIN']],
                    ['name' => 'Devices',     'route' => 'systems.devices.index',     'pattern' => 'systems.devices.*',     'icon' => 'smartphone',    'roles' => ['ADMIN']],
                    ['name' => 'Flags',     'route' => 'systems.flags.index',     'pattern' => 'systems.flags.*',     'icon' => 'flag',    'roles' => ['ADMIN']],
                    ['name' => 'Utility', 'route' => 'systems.utility.index', 'pattern' => 'systems.utility.*', 'icon' => 'settings',    'roles' => ['ADMIN']],
                    ['name' => 'Support', 'route' => 'systems.support.index', 'pattern' => 'systems.support.*', 'icon' => 'headset',    'roles' => ['ADMIN']],
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