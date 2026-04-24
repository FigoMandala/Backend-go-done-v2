<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * List all tasks for the authenticated user.
     * Supports optional query params: ?search=, ?priority=, ?status=, ?category_id=
     */
    public function index(Request $request)
    {
        $query = Task::with('category')->where('user_id', Auth::id());

        // Search by title or description
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by priority
        if ($priority = $request->query('priority')) {
            $query->where('priority', strtolower($priority));
        }

        // Filter by status
        if ($status = $request->query('status')) {
            $normalized = strtolower($status);
            if ($normalized === 'active') {
                $query->where('status', 'pending');
            } elseif ($normalized === 'done' || $normalized === 'completed') {
                $query->whereIn('status', ['done', 'completed']);
            } else {
                $query->where('status', $normalized);
            }
        }

        // Filter by category
        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Order by: pending first, then by deadline ascending
        $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
              ->orderBy('deadline', 'asc')
              ->orderBy('created_at', 'desc');

        $tasks = $query->get()->map(function ($task) {
            return [
                'task_id'       => $task->task_id,
                'category_id'   => $task->category_id,
                'category_name' => $task->category ? $task->category->category_name : null,
                'title'         => $task->title,
                'description'   => $task->description,
                'deadline'      => $task->deadline,
                'priority'      => $task->priority,
                'status'        => $task->status === 'completed' ? 'done' : $task->status,
                'created_at'    => $task->created_at,
                'updated_at'    => $task->updated_at,
            ];
        });

        return response()->json($tasks);
    }

    /**
     * Show a single task.
     */
    public function show($id)
    {
        $task = Task::with('category')->where('user_id', Auth::id())->findOrFail($id);

        return response()->json([
            'task_id'       => $task->task_id,
            'category_id'   => $task->category_id,
            'category_name' => $task->category ? $task->category->category_name : null,
            'title'         => $task->title,
            'description'   => $task->description,
            'deadline'      => $task->deadline,
            'priority'      => $task->priority,
            'status'        => $task->status === 'completed' ? 'done' : $task->status,
            'created_at'    => $task->created_at,
            'updated_at'    => $task->updated_at,
        ]);
    }

    /**
     * Create a new task.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,category_id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|date',
            'priority'    => 'nullable|string|in:low,medium,high,Low,Medium,High',
            'status'      => 'nullable|string|in:pending,done,completed',
        ]);

        $categoryBelongsToUser = Category::where('category_id', $request->category_id)
            ->where('user_id', Auth::id())
            ->exists();

        if (!$categoryBelongsToUser) {
            return response()->json([
                'message' => 'Kategori tidak valid untuk akun ini.',
            ], 422);
        }

        $normalizedStatus = strtolower($request->status ?? 'pending');
        if ($normalizedStatus === 'completed') {
            $normalizedStatus = 'done';
        }

        $task = Task::create([
            'user_id'     => Auth::id(),
            'category_id' => $request->category_id,
            'title'       => $request->title,
            'description' => $request->description,
            'deadline'    => $request->deadline,
            'priority'    => strtolower($request->priority ?? 'medium'),
            'status'      => $normalizedStatus,
        ]);

        $task->load('category');

        return response()->json([
            'task_id'       => $task->task_id,
            'category_id'   => $task->category_id,
            'category_name' => $task->category ? $task->category->category_name : null,
            'title'         => $task->title,
            'description'   => $task->description,
            'deadline'      => $task->deadline,
            'priority'      => $task->priority,
            'status'        => $task->status === 'completed' ? 'done' : $task->status,
            'created_at'    => $task->created_at,
            'updated_at'    => $task->updated_at,
        ], 201);
    }

    /**
     * Update an existing task.
     */
    public function update(Request $request, $id)
    {
        $task = Task::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'category_id' => 'sometimes|exists:categories,category_id',
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|date',
            'priority'    => 'sometimes|string|in:low,medium,high,Low,Medium,High',
            'status'      => 'sometimes|string|in:pending,done,completed',
        ]);

        $data = $request->only(['category_id', 'title', 'description', 'deadline', 'priority', 'status']);

        if (array_key_exists('category_id', $data) && $data['category_id'] !== null) {
            $categoryBelongsToUser = Category::where('category_id', $data['category_id'])
                ->where('user_id', Auth::id())
                ->exists();

            if (!$categoryBelongsToUser) {
                return response()->json([
                    'message' => 'Kategori tidak valid untuk akun ini.',
                ], 422);
            }
        }

        // Normalize priority to lowercase before saving
        if (isset($data['priority'])) {
            $data['priority'] = strtolower($data['priority']);
        }

        // Normalize status to lowercase before saving
        if (isset($data['status'])) {
            $data['status'] = strtolower($data['status']);

            if ($data['status'] === 'completed') {
                $data['status'] = 'done';
            }
        }

        $task->update($data);
        $task->load('category');

        return response()->json([
            'task_id'       => $task->task_id,
            'category_id'   => $task->category_id,
            'category_name' => $task->category ? $task->category->category_name : null,
            'title'         => $task->title,
            'description'   => $task->description,
            'deadline'      => $task->deadline,
            'priority'      => $task->priority,
            'status'        => $task->status === 'completed' ? 'done' : $task->status,
            'created_at'    => $task->created_at,
            'updated_at'    => $task->updated_at,
        ]);
    }

    /**
     * Delete a task.
     */
    public function destroy($id)
    {
        $task = Task::where('user_id', Auth::id())->findOrFail($id);
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    /**
     * Get task statistics for the authenticated user.
     * Returns counts by status and priority.
     */
    public function stats()
    {
        $userId = Auth::id();
        $today = now()->toDateString();

        $stats = Task::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status IN (\'done\', \'completed\') THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = \'pending\' AND priority = \'high\' THEN 1 ELSE 0 END) as high_pending,
                SUM(CASE WHEN status = \'pending\' AND priority = \'medium\' THEN 1 ELSE 0 END) as medium_pending,
                SUM(CASE WHEN status = \'pending\' AND priority = \'low\' THEN 1 ELSE 0 END) as low_pending,
                SUM(CASE WHEN status = \'pending\' AND deadline IS NOT NULL AND deadline < ? THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN status = \'pending\' AND deadline = ? THEN 1 ELSE 0 END) as today_due
            ', [$today, $today])
            ->first();

        return response()->json([
            'total'          => (int) ($stats->total ?? 0),
            'pending'        => (int) ($stats->pending ?? 0),
            'completed'      => (int) ($stats->completed ?? 0),
            'overdue'        => (int) ($stats->overdue ?? 0),
            'today_due'      => (int) ($stats->today_due ?? 0),
            'high_pending'   => (int) ($stats->high_pending ?? 0),
            'medium_pending' => (int) ($stats->medium_pending ?? 0),
            'low_pending'    => (int) ($stats->low_pending ?? 0),
        ]);
    }
}
