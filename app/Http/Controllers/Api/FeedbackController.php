<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    // ================= ADMIN – LIST FEEDBACK =================
    public function index(Request $request)
    {
        Log::info('FEEDBACK INDEX HIT', [
            'search' => $request->search,
            'status' => $request->status,
            'page'   => $request->page,
        ]);
    
        $feedbacks = Feedback::with('user')
            ->when(
                $request->search,
                fn($q) =>
                $q->where('message', 'like', "%{$request->search}%")
            )
            ->when($request->status === 'pending', fn($q) => $q->whereNull('response'))
            ->when($request->status === 'responded', fn($q) => $q->whereNotNull('response'))
            ->latest()
            ->paginate(10);
    
    
        return response()->json($feedbacks);
    }

    // ================= USER – SUBMIT FEEDBACK =================
    public function store(Request $request)
    {
        $data = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'message' => 'required|string',
        ]);

        $data['user_id'] = auth()->id(); // nullable untuk guest

        return response()->json([
            'message' => 'Feedback submitted',
            'data'    => Feedback::create($data)->load('user'),
        ], 201);
    }

    // ================= DETAIL =================
    public function show($id)
    {
        return response()->json([
            'data' => Feedback::with('user')->findOrFail($id),
        ]);
    }

    // ================= ADMIN – RESPOND =================
    public function respond(Request $request, $id)
    {
        $request->validate([
            'response' => 'required|string',
        ]);

        $feedback = Feedback::findOrFail($id);

        $feedback->update([
            'response'     => $request->response,
            'responded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Response sent',
            'data'    => $feedback->load('user'),
        ]);
    }

    // ================= DELETE (OPTIONAL) =================
    public function destroy($id)
    {
        Feedback::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Deleted',
        ]);
    }
}
