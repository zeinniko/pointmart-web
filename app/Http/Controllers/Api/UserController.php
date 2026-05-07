<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return response()->json([
            'data' => User::with('role')
                ->when(
                    $request->search,
                    fn($q) =>
                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%")
                )
                ->when(
                    $request->role,
                    fn($q) =>
                    $q->whereHas('role', fn($r) => $r->where('name', $request->role))
                )
                ->paginate(10)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json([
            'message' => 'User created',
            'data' => $user->load('role')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if ($id === 'me') {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        }

        $user = User::with(['role', 'addresses', 'devices'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'role_id' => 'sometimes|exists:roles,id',
            'name'    => 'sometimes|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'email'   => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated',
            'data' => $user->load('role')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json([
            'message' => 'User deleted'
        ]);
    }

    public function me()
    {
        $user = User::with(['role', 'addresses', 'devices'])
            ->findOrFail(auth()->id());


        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function drivers()
    {
        $drivers = User::whereHas('role', function ($q) {
            $q->where('name', 'Deliver');
        })->get();

        return response()->json([
            'data' => $drivers
        ]);
    }
}
