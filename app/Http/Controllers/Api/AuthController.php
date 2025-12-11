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

class AuthController extends Controller
{
    //
    public function login(Request $request)
    {
        $request->validate([
            /**
             * Email
             * @example zeinniko@edukarya.com
             */
            'email' => 'required|email',
            /**
             * Password
             * @example password
             */
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
                'data' => null
            ], 422);
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
        // Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        // Buat user baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Buat token API
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


    // ===========================
    //  UPDATE PROFILE
    // ===========================
    public function updateProfile(Request $request) {}

    // ===========================
    // 1️⃣ KIRIM KODE RESET PASSWORD
    // ===========================
    public function sendResetCode(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $code = rand(100000, 999999); // 6 digit angka
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $code, 'created_at' => Carbon::now()]
        );

        // kirim email
        Mail::raw("Kode verifikasi reset password Anda adalah: $code", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Kode Verifikasi Reset Password');
        });

        return response()->json([
            'success' => true,
            'message' => 'Kode verifikasi telah dikirim ke email Anda.',
        ]);
    }


    // ===========================
    // 2️⃣ VERIFIKASI KODE RESET
    // ===========================
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

        // Jika valid
        return response()->json([
            'success' => true,
            'message' => 'Kode verifikasi valid. Silakan atur kata sandi baru.',
        ]);
    }


    // ===========================
    // 3️⃣ GANTI PASSWORD (Bisa untuk 2 mode)
    // ===========================
    public function changePassword(Request $request)
    {
        $type = $request->input('type', 'auth'); // default = auth

        $rules = [
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',        // harus ada huruf besar
                'regex:/[0-9]/',        // harus ada angka
                'regex:/^[A-Za-z0-9]+$/', // hanya huruf dan angka
                'confirmed',            // harus ada password_confirmation
            ],
        ];

        if ($type === 'reset') {
            // reset password via email
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
            // 🔒 User sedang login
            $user = $request->user();
        } else {
            // ✉️ Lupa password: verifikasi token dulu
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

            $user = \App\Models\User::where('email', $request->email)->first();

            // hapus kode reset setelah berhasil digunakan
            DB::table('password_resets')->where('email', $request->email)->delete();
        }

        // Ubah password
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }
}
