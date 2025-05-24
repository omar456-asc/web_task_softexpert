<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTaskDependenciesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'dependencies' => 'required|array',
            'dependencies.*' => 'exists:tasks,id',
        ];
    }
}
