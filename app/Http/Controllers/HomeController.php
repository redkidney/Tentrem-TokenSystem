<?php

namespace App\Http\Controllers;
use App\Models\Token;
use App\Models\Voucher;
use App\Models\ChargingSession;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $totalTokens = Token::count();
        $activeVouchers = Voucher::count();
        $chargingSessions = ChargingSession::count();

        $recentActivities = $this->getRecentActivities();

        return view('admin.dashboard', compact('totalTokens', 'activeVouchers', 'chargingSessions', 'recentActivities'));
    }

    private function getRecentActivities()
    {
        $activities = collect();

        $activities = $activities->merge(
            Token::latest()->take(3)->get()->map(function ($token) {
                return [
                    'type' => 'token',
                    'message' => "New token generated: {$token->token}",
                    'created_at' => $token->created_at,
                ];
            })
        );

        $activities = $activities->merge(
            Voucher::latest()->take(3)->get()->map(function ($voucher) {
                return [
                    'type' => 'voucher',
                    'message' => "Voucher created: {$voucher->voucher_name}",
                    'created_at' => $voucher->created_at,
                ];
            })
        );

        $activities = $activities->merge(
            ChargingSession::latest()->take(3)->get()->map(function ($session) {
                $details = [];
                if ($session->guest_name) {
                    $details[] = "Guest: {$session->guest_name}";
                }
                if ($session->room_no) {
                    $details[] = "Room: {$session->room_no}";
                }
                
                $detailsStr = !empty($details) ? " (" . implode(", ", $details) . ")" : "";
                
                return [
                    'type' => 'charging_session',
                    'message' => "Charging session at Port #{$session->charging_port}{$detailsStr}",
                    'created_at' => $session->created_at,
                ];
            })
        );

        return $activities->sortByDesc('created_at')->take(5);
    }
}

