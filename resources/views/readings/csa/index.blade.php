<x-app-layout>

<div x-data="csaForm()" class="p-6 space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-medium text-gray-600">
                CSA Management
            </h1>
            <p class="text-sm text-gray-500">
                Manage Customer Service Agents
            </p>
        </div>

        @if(Auth::user()->role === 'ADMIN')
            <button
                type="button"
                x-on:click="$dispatch('open-modal','create-csa')"
                class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-sm rounded-md"
            >
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                New CSA
            </button>
        @endif
    </div>


    <div class="bg-white rounded-md p-4 border">

        @if($csas->count())

        <table class="min-w-full">

            <thead>
                <tr class="text-left text-xs uppercase text-gray-500">
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Zone</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Last Login</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>


            <tbody>

            @foreach($csas as $csa)

            <tr class="border-t">

                <td class="px-6 py-4">
                    {{ $csa->name }}
                </td>


                <td class="px-6 py-4">
                    {{ $csa->activeAssignment->zone->name ?? '-' }}
                </td>


                <td class="px-6 py-4">

                    <span class="px-2 py-1 text-xs rounded
                    {{ 
                    $csa->status === 'ACTIVE'
                    ? 'bg-green-100 text-green-700'
                    :
                    ($csa->status === 'SUSPENDED'
                    ? 'bg-yellow-100 text-yellow-700'
                    :
                    'bg-gray-100 text-gray-700')
                    }}">

                    {{ ucfirst(strtolower($csa->status)) }}

                    </span>

                </td>


                <td class="px-6 py-4">
                    {{ 
                    $csa->last_login_at
                    ? $csa->last_login_at->diffForHumans()
                    : 'Never'
                    }}
                </td>


                <td class="px-6 py-4 text-right">

                    <button
                    type="button"
                    class="px-3 py-1 bg-gray-100 rounded text-xs"
                    x-on:click="
                        selectCsa(@js($csa));
                        $dispatch('open-modal','edit-csa');
                    "
                    >
                        Edit
                    </button>


                </td>


            </tr>


            @endforeach

            </tbody>


        </table>


        <div class="p-4">
            {{ $csas->links() }}
        </div>


        @else

        <div class="p-10 text-center text-gray-500">
            No CSAs found.
        </div>

        @endif


    </div>



    <!-- CREATE MODAL -->

    <x-modal name="create-csa" max-width="md" :closable="false">

        <div class="p-6">

            <h2 class="text-lg font-semibold">
                Add Meter Reader
            </h2>


            <form method="POST"
            action="{{route('readings.csas.store')}}"
            class="space-y-4">

            @csrf


            <div>
                <x-input-label value="Name"/>
                <x-text-input name="name" class="w-full"/>
            </div>


            <div>
                <x-input-label value="Username"/>
                <x-text-input name="username" class="w-full"/>
            </div>


            <div>
                <x-input-label value="Email"/>
                <x-text-input name="email" class="w-full"/>
            </div>


            <div class="flex justify-end">

                <button class="bg-primary text-white px-6 py-2 rounded">
                    Create CSA
                </button>

            </div>


            </form>


        </div>


    </x-modal>




    <!-- EDIT MODAL -->


    <x-modal name="edit-csa" max-width="md" :closable="false">


        <div class="p-6">


            <h2 class="text-lg font-semibold">
                Edit CSA
                <span class="text-gray-500"
                x-text="selectedCsa?.name">
                </span>
            </h2>



            <form
            x-ref="editForm"
            method="POST"
            x-bind:action="editFormAction"
            x-on:submit.prevent="submitEditForm()"
            class="space-y-4">

            @csrf
            @method('PUT')


            <div>

                <x-input-label value="Name"/>

                <x-text-input
                name="name"
                class="w-full"
                x-model="editName"
                required/>

            </div>



            <div>

                <x-input-label value="Username"/>

                <x-text-input
                name="username"
                class="w-full"
                x-model="editUsername"
                x-on:input="generateEditPassword()"
                required/>

            </div>



            <div>

                <x-input-label value="Email"/>

                <x-text-input
                name="email"
                class="w-full"
                x-model="editEmail"/>

            </div>




            <div>

                <x-input-label value="Status"/>


                <select
                name="status"
                x-model="editStatus"
                class="w-full rounded border-gray-300">

                    <option value="ACTIVE">
                        Active
                    </option>

                    <option value="SUSPENDED">
                        Suspended
                    </option>


                    <option value="INACTIVE">
                        Inactive
                    </option>

                </select>


            </div>




            <div>

                <x-input-label value="Password"/>


                <input
                readonly
                class="w-full bg-gray-100 border rounded"
                x-model="editPasswordDisplay">


                <input
                type="hidden"
                name="password"
                x-model="editPassword">


            </div>




            <div class="flex justify-end">

                <button
                type="submit"
                class="bg-primary text-white px-6 py-2 rounded">

                    Save Changes

                </button>


            </div>


            </form>



        </div>


    </x-modal>



</div>




@push('scripts')

<script>


document.addEventListener('alpine:init',()=>{


Alpine.data('csaForm',()=>({


    selectedCsa:null,


    editName:'',
    editUsername:'',
    editEmail:'',
    editStatus:'ACTIVE',

    editPassword:'',
    editPasswordDisplay:'',



    actionTemplate:
    "{{route('readings.csas.update',['csa'=>'CSA_ID'])}}",




    selectCsa(csa)
    {

        this.selectedCsa=csa;


        this.editName=csa.name ?? '';

        this.editUsername=csa.username ?? '';

        this.editEmail=csa.email ?? '';

        this.editStatus=csa.status ?? 'ACTIVE';


        this.generateEditPassword();

    },




    generateEditPassword()
    {

        let username=this.editUsername.trim();


        this.editPassword =
            username
            ? username+'1234'
            : '';


        this.editPasswordDisplay=this.editPassword;

    },





    get editFormAction()
    {

        if(!this.selectedCsa)
        {
            return '#';
        }


        return this.actionTemplate.replace(
            'CSA_ID',
            this.selectedCsa.id
        );

    },





    submitEditForm()
    {

        if(!this.selectedCsa)
        {
            return;
        }


        this.$refs.editForm.submit();

    }



}));



});

</script>

@endpush


</x-app-layout>