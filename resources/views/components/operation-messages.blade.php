@if (session('success'))
    <p role="status" class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-emerald-900">{{ session('success') }}</p>
@endif
@if ($errors->any())
    <div role="alert" class="rounded-lg bg-red-50 border border-red-200 p-4 text-red-900">
        <p class="font-semibold">Revisa los siguientes datos:</p>
        <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
