<?php

return [
    'geofence' => [
        'radius_meters' => (int) env('ATTENDANCE_GEOFENCE_RADIUS_METERS', 100),
        'locations' => [
            'office' => [
                'latitude' => (float) env('ATTENDANCE_OFFICE_LATITUDE', -6.3357454),
                'longitude' => (float) env('ATTENDANCE_OFFICE_LONGITUDE', 106.9457406),
            ],
            'produksi' => [
                'latitude' => (float) env('ATTENDANCE_PRODUCTION_LATITUDE', -6.3357454),
                'longitude' => (float) env('ATTENDANCE_PRODUCTION_LONGITUDE', 106.9457406),
            ],
        ],
    ],
];
