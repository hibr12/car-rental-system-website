<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.string' => 'Category name must be text.',
            'name.max' => 'Category name must not exceed 255 characters.',
            'name.unique' => 'A category with this name already exists.',
            'description.string' => 'Description must be text.',
            'description.max' => 'Description must not exceed 1000 characters.',
        ];
    }
}
