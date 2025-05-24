<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Requests\FilterTasksRequest;
use App\Http\Requests\AddTaskDependenciesRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskController extends Controller
{
    use AuthorizesRequests;

    // GET /tasks
    public function index(FilterTasksRequest $request)
    {
        $query = Task::query();
        $validated = $request->validated();
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (isset($validated['due_from']) && isset($validated['due_to'])) {
            $query->whereBetween('due_date', [$validated['due_from'], $validated['due_to']]);
        }
        if (isset($validated['assignee_id'])) {
            $query->where('assignee_id', $validated['assignee_id']);
        }
        // Users can only see their own tasks
        if (Auth::user()->hasRole('User')) {
            $query->where('assignee_id', Auth::id());
        }
        return response()->json($query->with('assignee')->get());
    }

    // POST /tasks
    // Create a new task with title, description, assignee_id, due_date, and status
    public function store(StoreTaskRequest $request)
    {
        $this->authorize('create', Task::class);
        $data = $request->validated();
        // Ensure all required fields are present: title, description, assignee_id, due_date, status
        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'assignee_id' => $data['assignee_id'],
            'due_date' => $data['due_date'],
            'status' => $data['status'],
        ]);
        return response()->json($task, 201);
    }

    // GET /tasks/{id}
    public function show($id)
    {
        $task = Task::with(['assignee', 'dependencies'])->findOrFail($id);
        $this->authorize('view', $task);
        return response()->json($task);
    }

    // PUT /tasks/{id}
    // Update task details: title, description, assignee_id, due_date, status
    public function update(UpdateTaskRequest $request, $id)
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);
        $data = $request->validated();
        $task->update($data);
        return response()->json($task);
    }

    // PATCH /tasks/{id}/status
    public function updateStatus(UpdateTaskStatusRequest $request, $id)
    {
        $task = Task::with('dependencies')->findOrFail($id);
        $this->authorize('updateStatus', $task);
        $validated = $request->validated();
        // Check dependencies
        if ($validated['status'] === 'completed') {
            foreach ($task->dependencies as $dep) {
                if ($dep->status !== 'completed') {
                    return response()->json(['error' => 'All dependencies must be completed first.'], 422);
                }
            }
        }
        $task->status = $validated['status'];
        $task->save();
        return response()->json($task);
    }

    // POST /tasks/{id}/dependencies
    public function addDependencies(AddTaskDependenciesRequest $request, $id)
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);
        $task->dependencies()->syncWithoutDetaching($request->validated()['dependencies']);
        return response()->json($task->load('dependencies'));
    }
}
