<?php

namespace App\Services;

use App\Models\User;
use LogicException;

class AttendanceGeofence
{
    public function distanceFromWorkplace(User $user, float $latitude, float $longitude): float
    {
        $workplace = $this->configurationFor($user);

        $earthRadiusMeters = 6_371_000;
        $latitudeDelta = deg2rad($latitude - (float) $workplace['latitude']);
        $longitudeDelta = deg2rad($longitude - (float) $workplace['longitude']);
        $originLatitude = deg2rad((float) $workplace['latitude']);
        $destinationLatitude = deg2rad($latitude);
        $a = sin($latitudeDelta / 2) ** 2
            + cos($originLatitude) * cos($destinationLatitude) * sin($longitudeDelta / 2) ** 2;
        $distance = 2 * $earthRadiusMeters * asin(min(1, sqrt($a)));

        return $distance;
    }

    public function isWithinAllowedRadius(float $distanceMeters, float $accuracyMeters = 0): bool
    {
        return $this->measurementIsWithinRadius(
            $distanceMeters,
            $accuracyMeters,
            (float) config('attendance.geofence.radius_meters'),
        );
    }

    public function measurementIsWithinRadius(
        float $distanceMeters,
        float $accuracyMeters,
        float $radiusMeters,
    ): bool {

        if (! is_finite($radiusMeters) || $radiusMeters <= 0) {
            throw new LogicException('Attendance geofence radius must be configured as a positive number.');
        }

        if (! is_finite($distanceMeters)
            || ! is_finite($accuracyMeters)
            || $distanceMeters < 0
            || $accuracyMeters < 0) {
            return false;
        }

        return $distanceMeters + $accuracyMeters <= $radiusMeters;
    }

    /**
     * @return array{latitude: float, longitude: float, radius_meters: float, maximum_accuracy_meters: int}
     */
    public function configurationFor(User $user): array
    {
        if ($user->branch_id !== null) {
            $branch = $user->branch;
            if ($branch === null
                || ! is_numeric($branch->latitude)
                || ! is_numeric($branch->longitude)
                || ! is_numeric($branch->radius_m)
                || (float) $branch->radius_m <= 0) {
                throw new LogicException('The assigned branch does not have a complete attendance geofence.');
            }

            return [
                'latitude' => (float) $branch->latitude,
                'longitude' => (float) $branch->longitude,
                'radius_meters' => (float) $branch->radius_m,
                'maximum_accuracy_meters' => 100,
            ];
        }

        $workLocation = strtolower($user->work_location ?? 'office');
        $workplace = config("attendance.geofence.locations.{$workLocation}");
        $radius = config('attendance.geofence.radius_meters');

        if (! is_array($workplace)
            || ! isset($workplace['latitude'], $workplace['longitude'])
            || ! is_numeric($workplace['latitude'])
            || ! is_numeric($workplace['longitude'])
            || ! is_numeric($radius)
            || (float) $radius <= 0) {
            throw new LogicException("Attendance geofence is not configured for work location [{$workLocation}].");
        }

        return [
            'latitude' => (float) $workplace['latitude'],
            'longitude' => (float) $workplace['longitude'],
            'radius_meters' => (float) $radius,
            'maximum_accuracy_meters' => 100,
        ];
    }
}
