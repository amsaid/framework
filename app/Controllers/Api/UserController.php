<?php

namespace App\Controllers\Api;

use Core\Request;
use Core\Response\JsonResponse;

class UserController
{
    public function index()
    {
        // Sample data - replace with actual database query
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
        ];

        return JsonResponse::success($users);
    }

    public function show(int $id)
    {
        // Sample data - replace with actual database query
        $user = ['id' => $id, 'name' => 'John Doe', 'email' => 'john@example.com'];
        
        return JsonResponse::success($user);
    }

    public function store(Request $request)
    {
        $data = $request->json();
        
        // Validate required fields
        if (empty($data['name']) || empty($data['email'])) {
            return JsonResponse::error('Name and email are required', 422);
        }

        // Sample response - replace with actual database insert
        $user = [
            'id' => 3,
            'name' => $data['name'],
            'email' => $data['email']
        ];

        return JsonResponse::success($user, 201);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->json();
        
        // Sample response - replace with actual database update
        $user = [
            'id' => $id,
            'name' => $data['name'] ?? 'John Doe',
            'email' => $data['email'] ?? 'john@example.com'
        ];

        return JsonResponse::success($user);
    }

    public function destroy(int $id)
    {
        // Sample response - replace with actual database delete
        return JsonResponse::success(null, 204);
    }
}
