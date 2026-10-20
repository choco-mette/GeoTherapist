<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Therapist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'specialization',
        'rating',
        'review_count',
        'address_label',
        'subdistrict',
        'city',
        'latitude',
        'longitude',
        'status',
        'avatar_url',
    ];

    protected $casts = [
        'rating' => 'float',
        'review_count' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function dispatchLogs(): HasMany
    {
        return $this->hasMany(DispatchLog::class);
    }

    /**
     * Calculate straight line distance (Haversine) in KM
     */
    public static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate Tiered ETA buffer (riding time + preparation + address search)
     */
    public static function calculateTieredEta(float $roadKm, string $mode = 'motor'): array
    {
        if ($mode === 'motor') {
            $speed = 25.0; // km/h
            $travelMins = ($roadKm / $speed) * 60;

            if ($roadKm <= 2.5) {
                // Tier 1: Jarak dekat (<= 2.5 km) - Persiapan 3 mnt + cari gang/rumah 4 mnt
                $buffer = 7;
                $minMins = (int) max(8, round($travelMins + 6));
                $maxMins = (int) max($minMins + 3, round($travelMins + 9));
            } elseif ($roadKm <= 7.0) {
                // Tier 2: Jarak menengah (2.5 - 7 km) - Persiapan 3 mnt + traffic 2 mnt + cari cluster 4 mnt
                $buffer = 9;
                $minMins = (int) round($travelMins + 8);
                $maxMins = (int) max($minMins + 4, round($travelMins + 12));
            } else {
                // Tier 3: Jarak jauh (> 7 km) - Persiapan 3 mnt + lampu merah/macet 4 mnt + cari alamat 5 mnt
                $buffer = 12;
                $minMins = (int) round($travelMins + 10);
                $maxMins = (int) max($minMins + 5, round($travelMins + 15));
            }
            $targetMins = (int) round(($minMins + $maxMins) / 2);
        } else {
            // Car Mode
            $speed = 18.0; // km/h
            $travelMins = ($roadKm / $speed) * 60;
            $buffer = $roadKm <= 2.5 ? 10 : ($roadKm <= 7.0 ? 14 : 18);
            $minMins = (int) max(12, round($travelMins + $buffer - 2));
            $maxMins = (int) max($minMins + 4, round($travelMins + $buffer + 3));
            $targetMins = (int) round(($minMins + $maxMins) / 2);
        }

        return [
            'minutes' => $targetMins,
            'min' => $minMins,
            'max' => $maxMins,
            'text' => "{$minMins} - {$maxMins} mnt",
            'buffer_minutes' => $buffer,
            'travel_minutes' => (int) round($travelMins),
        ];
    }

    /**
     * Calculate road distance & travel time estimation
     */
    public function calculateDistanceAndEta(float $customerLat, float $customerLng): array
    {
        $straightKm = self::haversineDistance($this->latitude, $this->longitude, $customerLat, $customerLng);
        $roadKm = round($straightKm * 1.34, 1);

        $motorEta = self::calculateTieredEta($roadKm, 'motor');
        $carEta = self::calculateTieredEta($roadKm, 'car');

        return [
            'straight_km' => round($straightKm, 2),
            'road_km' => $roadKm,
            'eta_motor_minutes' => $motorEta['minutes'],
            'eta_motor_text' => $motorEta['text'],
            'eta_car_minutes' => $carEta['minutes'],
            'eta_car_text' => $carEta['text'],
            'eta_breakdown' => [
                'travel_minutes' => $motorEta['travel_minutes'],
                'buffer_minutes' => $motorEta['buffer_minutes'],
            ],
        ];
    }

    /**
     * Generate WhatsApp order dispatch URL
     */
    public function generateWhatsAppUrl(string $customerAddress, float $customerLat, float $customerLng, float $roadKm, int|string $motorEta): string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $this->phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $etaDisplay = is_numeric($motorEta) ? "{$motorEta} menit" : $motorEta;

        $message = "Halo {$this->name}, ada order masuk:\n"
            . "- Alamat Pelanggan: {$customerAddress}\n"
            . "- Jarak Tempuh: {$roadKm} km dari home base kamu\n"
            . "- Estimasi Waktu (Motor): {$etaDisplay} (termasuk persiapan & cari alamat)\n"
            . "- Maps Lokasi: https://maps.google.com/?q={$customerLat},{$customerLng}\n\n"
            . "Mohon konfirmasi kesiapannya segera. Terima kasih!";

        return "https://wa.me/{$cleanPhone}?text=" . rawurlencode($message);
    }
}
