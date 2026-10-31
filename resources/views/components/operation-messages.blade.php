@if (session('success'))
    <p role="status" class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 p-4 text-sm text-emerald-800 dark:text-emerald-200">{{ session('success') }}</p>
@endif
@if ($errors->any())
    <div role="alert" class="rounded-xl border border-rose-400/20 bg-rose-400/10 p-4 text-sm text-rose-800 dark:text-rose-200">
        <p class="font-semibold">Revisa los siguientes datos:</p>
        <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
