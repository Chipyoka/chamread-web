<x-app-layout>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-medium text-gray-600">Search Results</h1>
                    @if(!empty($query))
                        <p class="text-sm text-gray-500">
                            Results for: <span class="font-medium text-gray-700">{{ $query }}</span>
                        </p>
                    @endif
                </div>
                <x-micro-button
                        variant="edit"
                        href="{{ route('dashboard.index') }}"
                        icon="arrow-left"
                        size="md"
                    >
                        Back to home
                    </x-micro-button>
            </div>

            {{--  Layout --}}
            <div class="space-y-6 mt-6">

                {{-- CUSTOMER ACCOUNTS --}}
                <div class="bg-white rounded-md p-4">
                    <h2 class="text-xs uppercase text-gray-400 mb-4">
                        Customer Accounts ({{ $accounts->count() }})
                    </h2>

                    @forelse($accounts as $account)
                        <a href="{{ $account['url'] }}"
                           class="flex items-center justify-between p-3 rounded-sm hover:bg-gray-50/70 border-b border-gray-100 transition-all duration-300 ease-in-out">

                            <div class="flex items-center justify-start gap-2">
                                <div class="p-2 flex justify-center items-center bg-blue-50 rounded-full">
                                    <i data-lucide="file-text" class="w-5 h-5 text-primary"></i>
                                </div>
                                <div class="font-medium text-primary">
                                    {{ $account['title'] }} - {{ $account['subtitle'] }}
                                </div>
                            </div>
                            <i data-lucide="square-arrow-out-up-right" class="w-4 h-4 text-gray-400"></i>
                        </a>
                    @empty
                        <p class="text-xs text-gray-400">
                            No matching accounts found.
                        </p>
                    @endforelse
                </div>

                {{-- PEOPLE --}}
                <div class="bg-white rounded-md p-4">
                    <h2 class="text-xs uppercase text-gray-400 mb-4">
                        People ({{ $people->count() }})
                    </h2>

                    @forelse($people as $person)
                        <a href="{{ $person['url'] }}"
                           class="flex items-center justify-between p-3 rounded-sm hover:bg-gray-50/70 border-b border-gray-100 transition-all duration-300 ease-in-out">

                           <div class="flex items-center justify-start gap-2">
                                <div class="p-2 flex justify-center items-center bg-blue-50 rounded-full">
                                    <i data-lucide="user" class="w-5 h-5 text-primary"></i>
                                </div>
                                <div class="font-medium text-primary">
                                    {{ $person['title'] }} - {{ $person['role'] }}
                                </div>
                            </div>
                            <i data-lucide="square-arrow-out-up-right" class="w-4 h-4 text-gray-400"></i>
                        </a>
                    @empty
                        <p class="text-xs text-gray-400">
                            No matching users found.
                        </p>
                    @endforelse
                </div>

            </div>
        </div>
    </div>

</x-app-layout>