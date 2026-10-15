<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomRequest;
use App\Models\Room;
use App\Models\Section;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Room::orderBy('room_name')->paginate(20));
    }

    public function store(RoomRequest $request): JsonResponse
    {
        return response()->json(Room::create($request->validated()), 201);
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json($room);
    }

    public function update(RoomRequest $request, Room $room): JsonResponse
    {
        $room->update($request->validated());
        return response()->json($room->refresh());
    }

    public function destroy(Room $room): JsonResponse
    {
        if (Section::where('room_id', $room->id)->exists()) {
            return response()->json(['message' => 'Room is assigned to sections and cannot be deleted.'], 409);
        }
        $room->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
