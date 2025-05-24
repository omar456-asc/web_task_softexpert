<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'assignee_id' => 'sometimes|required|exists:users,id',
            'due_date' => 'sometimes|required|date',
            'status' => 'sometimes|string|in:pending,completed,cancelled',
        ];
    }
}
