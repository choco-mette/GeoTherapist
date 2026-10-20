<?php

namespace App\Http\Controllers;

use App\Models\Therapist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TherapistController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $city = $request->input('city');
        $subdistrict = $request->input('subdistrict');

        $query = Therapist::query()->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('specialization', 'like', "%{$search}%")
                    ->orWhere('address_label', 'like', "%{$search}%");
            });
        }

        if ($status && in_array($status, ['available', 'on_duty', 'off'])) {
            $query->where('status', $status);
        }

        if ($city) {
            $query->where('city', $city);
        }

        if ($subdistrict) {
            $query->where('subdistrict', $subdistrict);
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 25, 50, 100])) {
            $perPage = 10;
        }

        $therapists = $query->paginate($perPage)->withQueryString();

        $subdistricts = Therapist::select('subdistrict')
            ->whereNotNull('subdistrict')
            ->when($city, fn($q) => $q->where('city', $city))
            ->distinct()
            ->orderBy('subdistrict')
            ->pluck('subdistrict');

        $stats = [
            'total' => Therapist::count(),
            'available' => Therapist::where('status', 'available')->count(),
            'on_duty' => Therapist::where('status', 'on_duty')->count(),
            'off' => Therapist::where('status', 'off')->count(),
        ];

        return view('therapists.index', compact('therapists', 'subdistricts', 'stats'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:25',
            'specialization' => 'required|string|max:100',
            'rating' => 'nullable|numeric|between:1,5',
            'address_label' => 'required|string|max:255',
            'city' => 'required|string|in:Surabaya,Sidoarjo',
            'subdistrict' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'status' => 'required|in:available,on_duty,off',
            'avatar_url' => 'nullable|url|max:255',
        ]);

        if (empty($validated['avatar_url'])) {
            $validated['avatar_url'] = 'https://ui-avatars.com/api/?name=' . urlencode($validated['name']) . '&background=1d4ed8&color=ffffff&bold=true';
        }

        $therapist = Therapist::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'therapist' => $therapist]);
        }

        return redirect()->route('therapists.index')->with('success', "Terapis {$therapist->name} berhasil ditambahkan.");
    }

    public function update(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $therapist = Therapist::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:25',
            'specialization' => 'required|string|max:100',
            'rating' => 'nullable|numeric|between:1,5',
            'address_label' => 'required|string|max:255',
            'city' => 'required|string|in:Surabaya,Sidoarjo',
            'subdistrict' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'status' => 'required|in:available,on_duty,off',
            'avatar_url' => 'nullable|url|max:255',
        ]);

        $therapist->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'therapist' => $therapist]);
        }

        return redirect()->route('therapists.index')->with('success', "Data terapis {$therapist->name} berhasil diperbarui.");
    }

    public function updateStatus(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $therapist = Therapist::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:available,on_duty,off',
        ]);

        $therapist->update(['status' => $validated['status']]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $therapist->status]);
        }

        return back()->with('success', "Status terapis {$therapist->name} diubah menjadi {$therapist->status}.");
    }

    public function destroy(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $therapist = Therapist::findOrFail($id);
        $name = $therapist->name;
        $therapist->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('therapists.index')->with('success', "Terapis {$name} berhasil dihapus.");
    }
}
