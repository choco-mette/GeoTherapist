<?php

namespace App\Http\Controllers;

use App\Models\DispatchLog;
use App\Models\Therapist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TherapistApiController extends Controller
{
    /**
     * Get therapists ordered by road distance from customer lat/lng.
     * Supports OSRM Table API matrix routing with automatic Haversine fallback.
     */
    public function getNearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'status' => 'nullable|string|in:all,available,on_duty,off',
            'customer_address' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:50',
            'engine' => 'nullable|string|in:osrm,haversine',
        ]);

        $customerLat = (float) $validated['lat'];
        $customerLng = (float) $validated['lng'];
        $statusFilter = $validated['status'] ?? 'all';
        $customerAddress = $validated['customer_address'] ?? 'Surabaya';
        $limit = (int) ($validated['limit'] ?? 20);
        $preferredEngine = $validated['engine'] ?? 'osrm';

        $query = Therapist::query()->where('status', '!=', 'off');

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $allTherapists = $query->get();

        if ($allTherapists->isEmpty()) {
            return response()->json([
                'success' => true,
                'routing_engine' => 'none',
                'customer' => [
                    'lat' => $customerLat,
                    'lng' => $customerLng,
                    'address' => $customerAddress,
                ],
                'count' => 0,
                'data' => [],
            ]);
        }

        $usedEngine = 'haversine';
        $osrmMatrix = null;

        // Attempt OSRM Table API Matrix if requested
        if ($preferredEngine === 'osrm') {
            try {
                // Coordinate format: lng,lat
                $coordsList = ["{$customerLng},{$customerLat}"];
                foreach ($allTherapists as $t) {
                    $coordsList[] = "{$t->longitude},{$t->latitude}";
                }
                $coordString = implode(';', $coordsList);

                $osrmUrl = "https://router.project-osrm.org/table/v1/driving/{$coordString}?sources=0&annotations=distance,duration";

                $response = Http::timeout(3.5)->get($osrmUrl);

                if ($response->successful()) {
                    $resJson = $response->json();
                    if (($resJson['code'] ?? '') === 'Ok' && isset($resJson['distances'][0])) {
                        $osrmMatrix = [
                            'distances' => $resJson['distances'][0],
                            'durations' => $resJson['durations'][0] ?? [],
                        ];
                        $usedEngine = 'osrm';
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('OSRM Table API matrix failed, falling back to Haversine: ' . $e->getMessage());
                $usedEngine = 'haversine_fallback';
            }
        }

        // Map therapists with calculated distances and ETA
        $mapped = $allTherapists->values()->map(function (Therapist $therapist, int $index) use ($customerLat, $customerLng, $customerAddress, $osrmMatrix, $usedEngine) {
            $straightKm = round(Therapist::haversineDistance($therapist->latitude, $therapist->longitude, $customerLat, $customerLng), 2);

            // Calculate road distance (default Haversine fallback)
            $roadKm = round($straightKm * 1.34, 1);

            // If OSRM matrix available for this therapist, use exact turn-by-turn road distance
            $destIdx = $index + 1;
            if ($usedEngine === 'osrm' && isset($osrmMatrix['distances'][$destIdx])) {
                $meters = (float) $osrmMatrix['distances'][$destIdx];
                if ($meters > 0) {
                    $roadKm = round($meters / 1000, 1);
                }
            }

            // Real Surabaya Urban Speed & Tiered Buffer Model (Riding + Prep + Address Search)
            $motorEta = Therapist::calculateTieredEta($roadKm, 'motor');
            $carEta = Therapist::calculateTieredEta($roadKm, 'car');

            $whatsappUrl = $therapist->generateWhatsAppUrl(
                customerAddress: $customerAddress,
                customerLat: $customerLat,
                customerLng: $customerLng,
                roadKm: $roadKm,
                motorEta: $motorEta['text']
            );

            return [
                'id' => $therapist->id,
                'name' => $therapist->name,
                'phone' => $therapist->phone,
                'specialization' => $therapist->specialization,
                'rating' => $therapist->rating,
                'review_count' => $therapist->review_count,
                'address_label' => $therapist->address_label,
                'subdistrict' => $therapist->subdistrict,
                'city' => $therapist->city,
                'latitude' => $therapist->latitude,
                'longitude' => $therapist->longitude,
                'status' => $therapist->status,
                'avatar_url' => $therapist->avatar_url,
                'straight_km' => $straightKm,
                'road_km' => $roadKm,
                'eta_motor_minutes' => $motorEta['minutes'],
                'eta_motor_text' => $motorEta['text'],
                'eta_car_minutes' => $carEta['minutes'],
                'eta_car_text' => $carEta['text'],
                'eta_breakdown' => [
                    'travel_minutes' => $motorEta['travel_minutes'],
                    'buffer_minutes' => $motorEta['buffer_minutes'],
                ],
                'whatsapp_url' => $whatsappUrl,
            ];
        });

        // Sort ascending by real road distance
        $sorted = $mapped->sortBy('road_km')->values();

        // Assign Rank #1, #2, ...
        $ranked = $sorted->map(function ($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        });

        if ($limit > 0) {
            $ranked = $ranked->take($limit)->values();
        }

        return response()->json([
            'success' => true,
            'routing_engine' => $usedEngine,
            'customer' => [
                'lat' => $customerLat,
                'lng' => $customerLng,
                'address' => $customerAddress,
            ],
            'count' => $ranked->count(),
            'data' => $ranked,
        ]);
    }

    /**
     * Store dispatch log when user clicks dispatch WA
     */
    public function logDispatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'therapist_id' => 'required|exists:therapists,id',
            'customer_name' => 'nullable|string|max:150',
            'customer_address' => 'required|string',
            'customer_lat' => 'required|numeric',
            'customer_lng' => 'required|numeric',
            'distance_km' => 'required|numeric',
            'est_minutes_motor' => 'required|integer',
        ]);

        $log = DispatchLog::create([
            ...$validated,
            'status' => 'sent',
        ]);

        return response()->json([
            'success' => true,
            'log' => $log,
        ]);
    }
}
