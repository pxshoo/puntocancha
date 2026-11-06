<x-superadmin-layout title="Acceso del propietario">
<div class="mx-auto max-w-md px-5 py-12 sm:py-20">
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg dark:border-slate-800 dark:bg-slate-900 sm:p-8">
<p class="text-xs font-bold uppercase tracking-widest text-violet-600 dark:text-violet-300">Acceso de propietario</p>
<h1 class="mt-3 text-3xl font-extrabold">Superadministrador</h1>
<p class="mt-3 text-sm text-slate-500">Administra PuntoCancha, sus clubes, pagos y permisos desde un único lugar.</p>
<x-operation-messages />
<form method="POST" action="{{ route('superadmin.login.store') }}" class="mt-7 grid gap-5">
@csrf
<label class="grid gap-2 text-sm font-semibold">Correo del administrador<input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="min-h-12 rounded-xl border-slate-200 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950"></label>
<label class="grid gap-2 text-sm font-semibold">Contraseña<input name="password" type="password" required autocomplete="current-password" class="min-h-12 rounded-xl border-slate-200 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950"></label>
<label class="flex items-center gap-3 text-sm"><input type="checkbox" name="remember" value="1" class="rounded text-emerald-600 focus:ring-emerald-500">Recordar sesión</label>
<button class="min-h-14 rounded-xl bg-emerald-600 px-6 font-bold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">Entrar a superadministración</button>
</form>
<a href="{{ route('password.request') }}" class="mt-5 block text-center text-sm text-emerald-700 underline dark:text-emerald-300">Recuperar contraseña</a>
</div>
<a href="{{ route('login') }}" class="mt-6 block text-center text-sm text-slate-500 underline">Acceso para clubes y jugadores</a>
</div>
</x-superadmin-layout>
