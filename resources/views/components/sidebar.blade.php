<div class="min-h-[85dvh] max-h-dvh w-60 bg-white flex flex-col px-6 py-3 border-r border-gray-200">
    <nav class="flex-1 mt-4">
        @php
            $userRole = auth()->user()->role ?? 'CSA';
            $tabs = [
                ['name' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'roles' => ['CSA','SUPERVISOR','ADMIN'], 'pattern' => 'dashboard'],
                ['name' => 'CSAs', 'route' => 'admin.csas.index', 'icon' => 'users', 'roles' => ['SUPERVISOR','ADMIN'], 'pattern' => 'admin.csas.*'],
                ['name' => 'Accounts', 'route' => 'accounts.index', 'icon' => 'file-text', 'roles' => ['SUPERVISOR','ADMIN'], 'pattern' => 'accounts.*'],
                ['name' => 'Exceptions', 'route' => 'exceptions.index', 'icon' => 'alert-circle', 'roles' => ['SUPERVISOR','ADMIN'], 'pattern' => 'exceptions.*'],
                ['name' => 'Analytics', 'route' => 'analytics.index', 'icon' => 'bar-chart-2', 'roles' => ['ADMIN'], 'pattern' => 'analytics.*'],
                ['name' => 'Admin', 'route' => 'admin.settings', 'icon' => 'settings', 'roles' => ['ADMIN'], 'pattern' => 'ADMIN.*'],
                ['name' => 'Audit', 'route' => 'audit.index', 'icon' => 'clipboard', 'roles' => ['ADMIN'], 'pattern' => 'audit.*'],
            ];
        @endphp

        <ul class="space-y-2 text-gray-600">
            @foreach ($tabs as $tab)
                @if (in_array($userRole, $tab['roles']))
                    @php
                        $isActive = request()->routeIs($tab['pattern']);
                    @endphp
                    <li>
                        <a href="{{ route($tab['route']) }}"
                           class="flex items-center px-4 py-2 rounded-sm transition-all duration-200 ease-in {{ $isActive ? 'bg-primary text-white font-semibold' : 'text-gray-400 hover:bg-gray-100/80 hover:text-gray-500' }}">
                            <i data-lucide="{{ $tab['icon'] }}" class="w-5 h-5 mr-3"></i>
                            <span>{{ $tab['name'] }}</span>
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>

    </nav>
    <p class="text-center text-xs text-gray-400">Version: 2.0.0 | ChWSSCL</p>
</div>
