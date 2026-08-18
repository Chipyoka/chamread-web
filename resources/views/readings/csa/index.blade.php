<x-app-layout>

<div x-data="csaForm()" class="p-6 space-y-6">
        <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Readings'
                ],
                [
                    'label'=>'CSAs'
                ]
            ]"/>
        </x-slot:breadcrumb>

    <!-- Header -->
    <div class="flex items-center justify-between">

        <div>
            <h1 class="text-2xl font-medium text-gray-500">
                CSA Management
            </h1>

            <p class="text-xs text-gray-500">
                Manage Customer Service Agents <span class="bg-gray-200/70 text-gray-500 px-2 py-0.5 rounded-sm font-medium">{{ $csasTotal ?? 0 }} total</span> 
            </p>
        </div>


        @if(Auth::user()->role === 'ADMIN')

            <button
                type="button"
                x-on:click="$dispatch('open-modal','create-csa')"
                class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
            >

                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>

                New CSA

            </button>

        @endif


    </div>





    <!-- Table -->

    <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">
          <!-- Filter Section -->
            <form method="GET" action="{{ route('readings.csas.index') }}" class="flex flex-wrap items-center gap-3 pb-4 border-b border-gray-100">
                <!-- Search by name -->
                <div class="flex items-center space-x-2 flex-1 max-w-xs">
                    <div class="relative flex-1">
                        <input 
                            type="text" 
                            id="search" 
                            name="search"
                            placeholder="Search by Name..."
                            class="w-full text-xs border-gray-200 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 pl-8"
                            value="{{ request('search') }}"
                        >
                        <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"></i>
                    </div>
                </div>

                <!-- Zone Filter -->
                <div class="flex items-center space-x-2">
                    <select 
                        id="zone_filter" 
                        name="zone"
                        class="text-xs border-gray-200 w-48 focus:ring-primary focus:border-primary text-gray-500 bg-gray-50 px-3 py-1.5 rounded-sm"
                        onchange="this.form.submit()"
                    >
                        <option value="">All Zones</option>
                        @foreach($zones ?? [] as $zone)
                            <option value="{{ $zone->id }}" {{ request('zone') == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                

                <!-- Submit Button -->
                <button type="submit" class="text-xs bg-primary hover:opacity-95 text-white px-4 py-1.5 rounded-sm transition-colors">
                    Filter
                </button>

                <!-- Clear Filters -->
                @if(request('zone') || request('search') || request('category'))
                    <a 
                        href="{{ route('readings.csas.index') }}"
                        class="text-xs text-gray-400 hover:text-gray-500 transition-colors flex items-center space-x-1"
                    >
                        <i data-lucide="x" class="w-3 h-3"></i>
                        <span>Clear Filters</span>
                    </a>
                @endif

                <!-- Active Filters Count Badge -->
                @php
                    $activeFilters = collect([
                        request('zone'),
                        request('search'),
                        request('category')
                    ])->filter()->count();
                @endphp
                
                @if($activeFilters > 0)
                    <span class="text-[10px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                        {{ $activeFilters }} filter{{ $activeFilters > 1 ? 's' : '' }} active
                    </span>
                @endif

            
            </form>
        @if($csas->count() > 0)
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">

                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">


                    <th class="px-6 py-3">
                        Name
                    </th>


                    <th class="px-6 py-3">
                        Zone
                    </th>


                    <th class="px-6 py-3">
                        Status
                    </th>


                    <th class="px-6 py-3">
                        Last Login
                    </th>


                    <th class="px-6 py-3 text-right">
                        Actions
                    </th>


                </tr>

            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach($csas as $csa)


                    <tr class="hover:bg-gray-50 transition">



                        <!-- Name -->

                        <td class="px-6 py-3 text-xs text-gray-500 font-medium">

                            {{ $csa->name }}

                        </td>





                        <!-- Zone -->

                        <td class="px-6 py-3 text-xs text-gray-500">

                            {{ $csa->activeAssignment->zone->name ?? '-' }}

                        </td>
                        <!-- Status -->

                        <td class="px-6 py-3 uppercase">


                            @if($csa->status === 'ACTIVE')


                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">

                                    Active

                                </span>


                            @elseif($csa->status === 'SUSPENDED')


                                <span class="px-2 py-1 text-xs font-medium bg-amber-50 text-amber-600 rounded">

                                    Suspended

                                </span>


                            @else


                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-500 rounded">

                                    Inactive

                                </span>


                            @endif


                        </td>







                        <!-- Last Login -->


                        <td class="px-6 py-3 text-xs text-gray-500">


                            {{ 
                                $csa->last_login_at
                                ? $csa->last_login_at->diffForHumans()
                                : 'Never'
                            }}


                        </td>







                        <!-- Actions -->

                        <td class="px-6 py-3 text-right text-xs space-x-2">


                    @if(Auth::user()->role === 'ADMIN')

                        <!-- Edit -->

                        <x-micro-button

                            type="button"

                            color="gray"

                            icon="edit"

                            size="sm"

                            data-csa="{{ $csa->toJson() }}"

                            x-on:click="
                                selectCsa(JSON.parse($el.dataset.csa));
                                $dispatch('open-modal','edit-csa');
                            "

                        >
                            edit
                        </x-micro-button>
                    @endif






                            <!-- Profile -->

                            <x-micro-button

                                href="{{ route('readings.csas.show',$csa) }}"

                                color="blue"

                                icon="user"

                                size="sm"

                            >

                                Profile

                            </x-micro-button>







                            <!-- Readings -->

                            <x-micro-button

                                href="{{ route('readings.csas.readings',$csa) }}"

                                color="purple"

                                icon="list-todo"

                                size="sm"

                            >

                                Readings

                            </x-micro-button>





                            @if($csa->activeAssignment )

                            <!-- Accounts -->

                            <x-micro-button

                                href="{{ route('readings.csas.accounts',$csa) }}"

                                color="slate"

                                icon="file-text"

                                size="sm"

                            >

                                Accounts

                            </x-micro-button>

                            @endif


                        </td>



                    </tr>



                @endforeach
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="p-4">
            {{ $csas->links() }}
        </div>
        @else
            <div class="p-10 text-center">

                <div class="flex flex-col items-center space-y-3">


                    <i data-lucide="users" class="w-10 h-10 text-gray-300"></i>


                    <p class="text-gray-500 text-xs">
                        No CSAs found.
                    </p>



                </div>


            </div>
        @endif
    </div>












<!-- ======================================================
     CREATE CSA MODAL
====================================================== -->


<x-modal name="create-csa" max-width="md" :closable="false">


<div class="p-6">


<h2 class="text-lg font-semibold text-gray-900">

    Add Meter Reader (CSA)

</h2>





<form

method="POST"

action="{{ route('readings.csas.store') }}"

class="space-y-4"


>


@csrf




<!-- Name -->

<div>

<x-input-label for="name" value="Name"/>


<x-text-input

id="name"

name="name"

type="text"

class="mt-1 block w-full"

value="{{old('name')}}"

required

autofocus

/>


<x-input-error :messages="$errors->get('name')" class="mt-2"/>


</div>







<!-- Username -->

<div>

<x-input-label for="username" value="Username"/>


<x-text-input
    id="username"
    name="username"
    type="text"
    class="mt-1 block w-full"
    value="{{old('username')}}"
    x-on:input="generateCreatePassword()"
    x-model="createUsername"
    required
/>


<x-input-error :messages="$errors->get('username')" class="mt-2"/>


</div>






<!-- Email -->


<div>


<x-input-label for="email" value="Email"/>


<x-text-input

id="email"

name="email"

type="email"

class="mt-1 block w-full"

value="{{old('email')}}"

/>


<x-input-error :messages="$errors->get('email')" class="mt-2"/>


</div>





<!-- Password -->

<div>


<x-input-label value="Password (auto-generated)"/>



<div class="flex items-center space-x-2 mt-1">


<input
id="passwordDisplay"
type="text"
readonly
x-model="createPasswordDisplay"
class="block w-full px-3 py-2 border bg-gray-100 text-gray-700 cursor-not-allowed"
/>


<button
type="button"
x-on:click="copyCreatePassword($event)"
class="px-3 py-2 text-gray-500"
>

<i data-lucide="copy" class="w-5 h-5"></i>

</button>



</div>


<p class="text-gray-500 text-xs mt-1">

Default password format:

<code>[username]1234</code>

</p>



</div>





<input
type="hidden"
name="password"
x-model="createPassword"
/>







<div class="flex justify-end">


<button

type="submit"

class="bg-primary text-white px-6 py-2 rounded"

>

Create CSA

</button>


</div>




</form>


</div>


</x-modal>

<!-- ======================================================
     EDIT CSA MODAL
====================================================== -->


<x-modal name="edit-csa" max-width="md" :closable="false">

<div class="p-6">


<h2 class="text-lg font-semibold text-gray-900">

    Edit Meter Reader (CSA)


</h2>





<form

x-ref="editForm"

method="POST"

x-bind:action="editFormAction"

x-on:submit.prevent="submitEditForm()"

class="space-y-4"

>


@csrf

@method('PUT')





<!-- Name -->

<div>


<x-input-label for="edit_name" value="Name"/>


<x-text-input

id="edit_name"

name="name"

type="text"

class="mt-1 block w-full"

x-model="editName"

required

/>


<x-input-error :messages="$errors->get('name')" class="mt-2"/>


</div>








<!-- Username -->

<div>


<x-input-label for="edit_username" value="Username"/>



<x-text-input

id="edit_username"

name="username"

type="text"

class="mt-1 block w-full"

x-model="editUsername"

x-on:input="generateEditPassword()"

required

/>



<x-input-error :messages="$errors->get('username')" class="mt-2"/>


</div>







<!-- Email -->


<div>


<x-input-label for="edit_email" value="Email"/>



<x-text-input

id="edit_email"

name="email"

type="email"

class="mt-1 block w-full"

x-model="editEmail"

/>


<x-input-error :messages="$errors->get('email')" class="mt-2"/>


</div>







<!-- Status -->


<div>


<x-input-label for="edit_status" value="Status"/>


<select

id="edit_status"

name="status"

x-model="editStatus"

required

class="mt-1 block w-full border-gray-300 shadow-sm focus:ring-primary focus:border-primary"


>


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



<x-input-error :messages="$errors->get('status')" class="mt-2"/>


</div>








<!-- Password -->


<div>


<x-input-label value="Password (auto-generated)"/>



<div class="flex items-center space-x-2 mt-1">


<input

type="text"

readonly

x-model="editPasswordDisplay"

class="block w-full px-3 py-2 border bg-gray-100 text-gray-700 cursor-not-allowed"

/>



<button

type="button"

x-on:click="copyEditPassword($event)"

class="px-3 py-2 text-gray-500 rounded hover:bg-gray-100"

>

<i data-lucide="copy" class="w-5 h-5"></i>

</button>


</div>



<p class="text-gray-500 text-xs mt-1">

Updates automatically when username changes.

</p>



</div>







<input

type="hidden"

name="password"

x-model="editPassword"

/>









<div class="flex justify-end">


<button

type="submit"

class="bg-primary text-white px-6 py-2 rounded"

>


Save Changes


</button>


</div>





</form>



</div>


</x-modal>





@push('scripts')

<script>


document.addEventListener('alpine:init',()=>{


Alpine.data('csaForm',()=>({


/*
|--------------------------------------------------------------------------
| Create CSA State
|--------------------------------------------------------------------------
*/

createUsername:'',

createPassword:'',

createPasswordDisplay:'',
/*
|--------------------------------------------------------------------------
| Edit CSA State
|--------------------------------------------------------------------------
*/


selectedCsa:null,


editName:'',

editUsername:'',

editEmail:'',

editStatus:'ACTIVE',


editPassword:'',

editPasswordDisplay:'',






/*
|--------------------------------------------------------------------------
| Laravel route placeholder
|--------------------------------------------------------------------------
*/


actionTemplate:
"{{ route('readings.csas.update',['csa'=>'CSA_ID']) }}",






/*
|--------------------------------------------------------------------------
| Populate Edit Modal
|--------------------------------------------------------------------------
*/


selectCsa(csa)
{


this.selectedCsa = csa;



this.editName = csa.name ?? '';

this.editUsername = csa.username ?? '';

this.editEmail = csa.email ?? '';

this.editStatus = csa.status ?? 'ACTIVE';



this.generateEditPassword();



},








/*
|--------------------------------------------------------------------------
| Password generator
|--------------------------------------------------------------------------
*/
generateCreatePassword()
{

    let username = (this.createUsername || '').trim();


    this.createPassword =
        username
        ? username + '1234'
        : '';


    this.createPasswordDisplay =
        this.createPassword;

},

copyCreatePassword(event)
{

    if(!this.createPasswordDisplay)
    {
        return;
    }


    navigator.clipboard.writeText(
        this.createPasswordDisplay
    );


    const button = event.currentTarget;

    const original = button.innerHTML;


    button.textContent='Copied!';


    setTimeout(()=>{

        button.innerHTML = original;

    },2000);

},

generateEditPassword()
{


let username = (this.editUsername || '').trim();



this.editPassword =
username
? username + '1234'
: '';



this.editPasswordDisplay =
this.editPassword;



},







/*
|--------------------------------------------------------------------------
| Dynamic PUT route
|--------------------------------------------------------------------------
*/


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








/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/


submitEditForm()
{


if(!this.selectedCsa)
{

return;

}



this.$refs.editForm.submit();



},







/*
|--------------------------------------------------------------------------
| Copy Password
|--------------------------------------------------------------------------
*/


copyEditPassword(event)
{


if(!this.editPasswordDisplay)
{

return;

}



navigator.clipboard.writeText(
this.editPasswordDisplay
);



const button = event.currentTarget;


const original = button.innerHTML;



button.textContent='Copied!';



setTimeout(()=>{


button.innerHTML=original;


},2000);



}



}));



});

</script>

@endpush


</div>


</x-app-layout>