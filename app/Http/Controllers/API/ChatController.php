<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\SendMessage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // public function messages()
    // {
    //     try {
    //         $messages = Message::with('sender', 'receiver')->get();

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $messages
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'error' => 'Failed to retrieve messages.',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function messages()
    {
        try {
            $messages = Message::with(['sender' => function ($query) {
                $query->select('id', 'name', 'online_status'); 
            }, 'receiver' => function ($query) {
                $query->select('id', 'name', 'online_status'); 
            }])->get();

            return response()->json([
                'status' => 'success',
                'data' => $messages
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve messages.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function messageHistory(Request $request, $userId)
    {
        try {
            $authUser = $request->user();

            $receiver = User::find($userId);

            if (!$receiver) {
                return response()->json(['error' => 'User not found.'], 404);
            }

            $messages = Message::where(function ($query) use ($authUser, $userId) {
                $query->where('user_id', $authUser->id)
                    ->where('receiver_id', $userId);
            })->orWhere(function ($query) use ($authUser, $userId) {
                $query->where('user_id', $userId)
                    ->where('receiver_id', $authUser->id);
            })->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'messages' => $messages,
                    'authUser' => [
                        'id' => $authUser->id,
                        'name' => $authUser->name,
                        'online_status' => $authUser->online_status,
                    ],
                    'receiver' => [
                        'id' => $receiver->id,
                        'name' => $receiver->name,
                        'online_status' => $receiver->online_status,
                    ]
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'User not found.',
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve message history.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function messageStore(Request $request)
    {
        // dd($request->voice->getMimeType(), $request->voice->getClientOriginalExtension());
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'nullable|string|max:255',
                'receiver_id' => 'required|exists:users,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
               'voice' => 'nullable|file|mimetypes:audio/mpeg,audio/wav,audio/opus,audio/amr,audio/ogg,audio/mp4,video/3gpp|max:10240',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 403);
            }
    
            $sender = Auth::user();
            $receiver = User::findOrFail($request->receiver_id);
    
            $messageData = [
                'message' => $request->message,
                'receiver_id' => $receiver->id
            ];
    
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = $image->store('images', 'public');
                $messageData['image'] = $imagePath;
            }
    
            if ($request->hasFile('voice')) {
                $voice = $request->file('voice');
                $voicePath = $voice->store('voices', 'public');
                $messageData['voice'] = $voicePath;
            }
    
            $message = $sender->messages()->create($messageData);
    
            broadcast(new SendMessage($sender, $receiver, $message))->toOthers();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully',
                'data' => $message
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Receiver not found.',
                'message' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to send message.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
}
