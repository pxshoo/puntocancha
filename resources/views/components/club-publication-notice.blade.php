@if(auth()->user()?->role === 'club_admin')
    @php($ownClub = auth()->user()->club)
    @if($ownClub && (!$ownClub->marketplace_enabled || !$ownClub->onboarding_completed_at || !$ownClub->city || !$ownClub->address || !$ownClub->phone || !$ownClub->opens_at || !$ownClub->closes_at))
        <section class="flex flex-wrap items-center justify-between gap-5 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100" aria-label="Publicación de tu club">
            <div><h2 class="font-bold">Tu club aún no aparece en el buscador</h2><p class="mt-2 max-w-2xl text-sm">Tener canchas activas no publica el recinto. Completa la ubicación y los horarios del club, activa “Publicar mi club” y guarda los cambios.</p></div>
            <a href="{{ route('club.settings.edit') }}" class="inline-flex min-h-14 items-center justify-center rounded-xl bg-brand px-6 font-bold text-brand-on hover:bg-brand-hover">Configurar y publicar mi club →</a>
        </section>
    @endif
@endif
