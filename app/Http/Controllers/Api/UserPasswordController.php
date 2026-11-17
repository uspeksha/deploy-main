<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserPasswordController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string', 'current_password:sanctum'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->password = Hash::make($validator->validated()['password']);
        $user->save();
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password berhasil diperbarui. Silakan login kembali.',
        ]);
    }
}
