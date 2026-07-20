<x-app-layout>
    <div class="p-4">
        <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Dashboard',
                    'url'=>route('dashboard.dashboard.index')
                ],
                [
                    'label'=>'Technical'
                ]
            ]"/>
        </x-slot:breadcrumb>
        @include('components.technical', $technicalData)
    </div>
</x-app-layout>
