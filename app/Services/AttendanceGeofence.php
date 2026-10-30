<?php

namespace App\Services;

use App\Models\User;
use InvalidArgumentException;
use LogicException;

class AttendanceGeofence
{
    public function distanceFromWorkplace(User $user, float $latitude, float $longitude): float
    {
        $workLocation = strtolower($user->work_location ?? 'office');
        $workplace = config("attendance.geofence.locations.{$workLocation}");

        if (! is_array($workplace)
            || ! isset($workplace['latitude'], $workplace['longitude'])
            || ! is_numeric($workplace['latitude'])
            || ! is_numeric($workplace['longitude'])) {
            throw new LogicException("Attendance geofence is not configured for work location [{$workLocation}].");
        }

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
        $radiusMeters = config('attendance.geofence.radius_meters');

        if (! is_numeric($radiusMeters) || (float) $radiusMeters <= 0) {
            throw new LogicException('Attendance geofence radius must be configured as a positive number.');
        }

        if (! is_finite($distanceMeters)
            || ! is_finite($accuracyMeters)
            || $distanceMeters < 0
            || $accuracyMeters < 0) {
            return false;
        }

        return $distanceMeters + $accuracyMeters <= (float) $radiusMeters;
    }
}
