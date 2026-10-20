<?php

namespace App\Http\Controllers;

use App\Models\Therapist;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $totalTherapists = Therapist::where('status', '!=', 'off')->count();
        $availableTherapists = Therapist::where('status', 'available')->count();
        $onDutyTherapists = Therapist::where('status', 'on_duty')->count();

        // Default center: Surabaya Town Square / Center (-7.2902, 112.7380)
        $defaultCustomer = [
            'lat' => -7.2902,
            'lng' => 112.7380,
            'address' => 'Surabaya Town Square, Wonokromo, Surabaya',
        ];

        return view('dashboard', compact(
            'totalTherapists',
            'availableTherapists',
            'onDutyTherapists',
            'defaultCustomer'
        ));
    }
}
