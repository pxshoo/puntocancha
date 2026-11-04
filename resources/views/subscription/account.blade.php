<x-auth-shell title="Activa tu club" subtitle="Crea tu cuenta o ingresa para continuar." :selection="$selection">
    <ol class="mb-6 flex gap-3 text-xs text-slate-500" aria-label="Pasos de contratación"><li class="font-bold text-brand-content">1. Cuenta</li><li>2. Políticas</li><li>3. Pago</li></ol>
    @auth
        <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-950">Usa una cuenta administradora de club para contratar la plataforma.</p><form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<x-secondary-button type="submit">Cerrar sesión</x-secondary-button></form>
    @else
        <a href="{{ route('register') }}" class="action-feedback flex min-h-14 items-center justify-center rounded-xl bg-brand px-6 font-bold text-brand-on">Crear cuenta</a>
        <a href="{{ route('login') }}" class="action-feedback mt-4 flex min-h-14 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-6 font-semibold text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200">Ya tengo cuenta · Iniciar sesión</a>
    @endauth
    <p class="mt-5 text-center text-xs text-slate-500">Revisa las políticas y el total antes de pagar.</p>
</x-auth-shell>
