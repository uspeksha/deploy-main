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
        return response()->json([
            'success' => true,
            'data' => $geofence->configurationFor($request->user()),
        ]);
    }
}
