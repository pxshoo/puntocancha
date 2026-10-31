<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourtRequest;
use App\Models\Court;
use App\Models\Subscription;
use App\Services\CourtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourtController extends Controller
{
    public function index(): View
    {
        return view('courts.index', [
            'courts' => Court::orderByDesc('is_active')->orderBy('name')->paginate(12),
            'activeCount' => Court::where('is_active', true)->count(),
            'subscription' => Subscription::with('plan')->first(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->role === 'club_admin', 403);

        return view('courts.form', ['court' => new Court]);
    }

    public function edit(Request $request, Court $court): View
    {
        abort_unless($request->user()->role === 'club_admin', 403);

        return view('courts.form', compact('court'));
    }

    public function store(StoreCourtRequest $request, CourtService $service): RedirectResponse
    {
        $service->save($request->validated());

        return redirect()->route('courts.index')->with('success', 'Cancha creada. Ya puedes usarla en la agenda si está activa.');
    }

    public function update(StoreCourtRequest $request, Court $court, CourtService $service): RedirectResponse
    {
        $service->save($request->validated(), $court);

        return redirect()->route('courts.index')->with('success', 'Cancha y tarifa actualizadas. Las reservas anteriores conservan su precio.');
    }
}
