<button {{ $attributes->merge(['type' => 'submit', 'class' => 'action-feedback inline-flex items-center min-h-11 px-5 py-3 bg-rose-600 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-rose-700 active:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition-all ease-in-out duration-200']) }}>
    {{ $slot }}
</button>
