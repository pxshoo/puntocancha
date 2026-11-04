<x-dynamic-component :component="auth()->user()->role === 'player' ? 'player-layout' : 'app-layout'">
    <x-slot name="header">
        @if(auth()->user()->role === 'club_admin')<a href="{{ route('settings.index') }}" class="mb-3 inline-block text-sm text-brand-content">&larr; Configuraci&oacute;n</a>@endif
        <h2 class="font-semibold text-xl text-slate-900 dark:text-slate-100 leading-tight">
            {{ __('Cuenta y seguridad') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="surface-card p-4 sm:p-8 bg-white dark:bg-slate-900 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="surface-card p-4 sm:p-8 bg-white dark:bg-slate-900 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="surface-card p-4 sm:p-8 bg-white dark:bg-slate-900 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
