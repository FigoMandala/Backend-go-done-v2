<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * List all categories for the authenticated user.
     */
    public function index()
    {
        $categories = Category::where('user_id', Auth::id())
            ->orderBy('category_name', 'asc')
            ->get();

        return response()->json($categories);
    }

    /**
     * Show a single category.
     */
    public function show($id)
    {
        $category = Category::where('user_id', Auth::id())->findOrFail($id);

        return response()->json($category);
    }

    /**
     * Create a new category.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
        ]);

        // Check for duplicates (case-insensitive) for this user
        $exists = Category::where('user_id', Auth::id())
            ->whereRaw('LOWER(category_name) = ?', [strtolower($request->category_name)])
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Category with this name already exists.',
            ], 422);
        }

        $category = Category::create([
            'user_id' => Auth::id(),
            'category_name' => $request->category_name,
        ]);

        return response()->json($category, 201);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, $id)
    {
        $category = Category::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'category_name' => 'required|string|max:255',
        ]);

        // Check for duplicates (excluding current one)
        $exists = Category::where('user_id', Auth::id())
            ->where('category_id', '!=', $id)
            ->whereRaw('LOWER(category_name) = ?', [strtolower($request->category_name)])
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Category with this name already exists.',
            ], 422);
        }

        $category->update([
            'category_name' => $request->category_name,
        ]);

        return response()->json($category);
    }

    /**
     * Delete a category.
     * Returns 400 if the category is used by any tasks.
     */
    public function destroy($id)
    {
        $category = Category::where('user_id', Auth::id())->findOrFail($id);

        // Check if any tasks reference this category
        $taskCount = Task::where('user_id', Auth::id())
            ->where('category_id', $id)
            ->count();

        if ($taskCount > 0) {
            return response()->json([
                'error' => "Cannot delete this category because it is used by {$taskCount} task(s). Please reassign or delete those tasks first.",
            ], 400);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
