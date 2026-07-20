<x-app-layout>
    <div class="p-4">
        <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Dashboard',
                    'url'=>route('dashboard.dashboard.index')
                ],
                [
                    'label'=>'Supervisor'
                ]
            ]"/>
        </x-slot:breadcrumb>
        @include('components.supervisor', $overviewData)
    </div>
</x-app-layout>
