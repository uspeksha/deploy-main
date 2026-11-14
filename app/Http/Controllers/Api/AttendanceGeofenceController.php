<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceGeofence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceGeofenceController extends Controller
{
    public function show(Request $request, AttendanceGeofence $geofence): JsonResponse
    {
        $configuration = $geofence->configurationFor($request->user());
        $configuration['is_enforced'] = $geofence->isEnforcedFor($request->user());

        return response()->json([
            'success' => true,
            'data' => $configuration,
        ]);
    }
}
