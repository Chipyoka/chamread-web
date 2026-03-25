<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-3 bg-primary border border-transparent rounded-sm font-semibold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:opacity-95 active:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
