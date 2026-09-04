<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContactMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $messages = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Contact messages retrieved successfully',
            'data' => ContactMessageResource::collection($messages),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $message = ContactMessage::create(array_merge($request->validated(), ['status' => 'pending']));

        return response()->json([
            'success' => true,
            'message' => 'Contact message submitted successfully',
            'data' => new ContactMessageResource($message),
        ], 201);
    }

    public function update(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        if (!Gate::allows('update', $contactMessage)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $status = $request->input('status', 'read');
        $contactMessage->update([
            'status' => $status,
            'replied_at' => $status === 'replied' ? now() : $contactMessage->replied_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact message updated successfully',
            'data' => new ContactMessageResource($contactMessage->fresh()),
        ]);
    }

    public function destroy(ContactMessage $contactMessage): JsonResponse
    {
        if (!Gate::allows('delete', $contactMessage)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $contactMessage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact message deleted successfully',
        ]);
    }
}
