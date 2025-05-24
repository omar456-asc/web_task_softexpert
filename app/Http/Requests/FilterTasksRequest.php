<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterTasksRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'sometimes|string|in:pending,completed,canceled',
            'due_from' => 'sometimes|date',
            'due_to' => 'sometimes|date|after_or_equal:due_from',
            'assignee_id' => 'sometimes|exists:users,id',
        ];
    }
}
