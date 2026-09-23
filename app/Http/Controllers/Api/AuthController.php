<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::with('role')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
                'data' => null
            ], 422);
        }

        // Opsional: Validasi hanya Admin / Manager yang bisa login ke Web Admin
        if (!in_array($user->role?->name, ['Admin', 'Manager'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke Web Admin PointMart.',
                'data' => null
            ], 403);
        }

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Sukses login',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Register berhasil',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]
        ]);
    }
    
    public function profile(Request $request) {}

    public function updateProfile(Request $request) {}

    public function sendResetCode(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $code = rand(100000, 999999);
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $code, 'created_at' => Carbon::now()]
        );

        Mail::raw("Kode verifikasi reset password Anda adalah: $code", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Kode Verifikasi Reset Password');
        });

        return response()->json([
            'success' => true,
            'message' => 'Kode verifikasi telah dikirim ke email Anda.',
        ]);
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string|min:6|max:6',
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$reset) {
            return response()->json([
                'success' => false,
                'message' => 'Kode verifikasi salah atau tidak valid.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode verifikasi valid. Silakan atur kata sandi baru.',
        ]);
    }

    public function changePassword(Request $request)
    {
        $type = $request->input('type', 'auth');

        $rules = [
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/^[A-Za-z0-9]+$/',
                'confirmed',
            ],
        ];

        if ($type === 'reset') {
            $rules['email'] = 'required|email|exists:users,email';
            $rules['token'] = 'required|string|min:6|max:6';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($type === 'auth') {
            $user = $request->user();
        } else {
            $reset = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$reset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode verifikasi salah atau sudah kadaluarsa.',
                ], 400);
            }

            $user = User::where('email', $request->email)->first();
            DB::table('password_resets')->where('email', $request->email)->delete();
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }
}
