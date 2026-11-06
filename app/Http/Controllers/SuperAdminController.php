<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuperAdminActionRequest;
use App\Models\BookingPayment;
use App\Models\Club;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\SuperAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['manual_review', 'paid', 'failed', 'initialized'])]]);

        return view('superadmin.index', [
            'statistics' => ['clubs' => Club::count(), 'users' => User::count(), 'suspended' => Club::where('access_override', 'deny')->count(),
                'amount' => SubscriptionPayment::where('status', 'paid')->whereIn('mode', ['production', 'payment_link'])->sum('amount')],
            'accounts' => User::with('club')->orderByDesc('id')->paginate(15, ['id', 'name', 'email', 'role', 'club_id', 'is_superadmin', 'created_at'], 'accounts_page'),
            'payments' => SubscriptionPayment::with('club')->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))->latest()->paginate(15, ['*'], 'payments_page')->withQueryString(),
            'bookings' => BookingPayment::with('club')->latest()->paginate(10, ['*'], 'bookings_page'),
            'clubs' => Club::withCount('courts')->orderBy('name')->paginate(15, ['*'], 'clubs_page'),
            'owners' => User::where('role', 'club_admin')->get(['club_id', 'email', 'subscription_checkout_required'])->groupBy('club_id'),
            'subscriptions' => Subscription::withoutGlobalScope('club')->with('plan')->latest('id')->get()->unique('club_id')->keyBy('club_id'),
            'paidClubIds' => SubscriptionPayment::where('status', 'paid')->where('mode', '!=', 'payment_link_test')->distinct()->pluck('club_id')->all(),
            'audits' => DB::table('admin_audits')->join('users', 'users.id', '=', 'admin_audits.actor_id')->join('clubs', 'clubs.id', '=', 'admin_audits.club_id')->select('admin_audits.*', 'users.email as actor_email', 'clubs.name as club_name')->orderByDesc('admin_audits.id')->limit(20)->get(),
            'pending' => SubscriptionPayment::where('status', 'manual_review')->count(),
        ]);
    }

    public function show(Club $club): View
    {
        return view('superadmin.account', [
            'club' => $club,
            'users' => User::where('club_id', $club->id)->orderBy('name')->get(),
            'subscription' => Subscription::withoutGlobalScope('club')->where('club_id', $club->id)->with('plan')->latest('id')->first(),
            'payments' => SubscriptionPayment::where('club_id', $club->id)->latest()->paginate(15),
            'firstPurchase' => SubscriptionPayment::where('club_id', $club->id)->where('status', 'paid')->where('mode', '!=', 'payment_link_test')->oldest()->first(),
        ]);
    }

    public function access(SuperAdminActionRequest $request, Club $club, SuperAdminService $service): RedirectResponse
    {
        $service->access($request->user(), $club, $request->validated());

        return to_route('superadmin.index', ['section' => 'clubs'])->with('success', 'Acceso del club actualizado.');
    }

    public function review(SuperAdminActionRequest $request, SubscriptionPayment $payment, SuperAdminService $service): RedirectResponse
    {
        $service->review($request->user(), $payment, $request->validated());

        return to_route('superadmin.index', ['section' => 'transactions'])->with('success', 'Revisión de pago registrada.');
    }
}
