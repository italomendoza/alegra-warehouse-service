<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckIngredientsRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Cambia esto según tu lógica de autorización
    }

    public function rules()
    {
        return [
            'ingredients' => 'required|array',
            'ingredients.*.ingredient_name' => 'required|string|exists:ingredients,name',
            'ingredients.*.quantity' => 'required|integer|min:1'
        ];
    }

    public function messages()
    {
        return [
            'ingredients.required' => 'The ingredients field is required.',
            'ingredients.array' => 'The ingredients field must be an array.',
            'ingredients.*.ingredient_name.required' => 'Each ingredient must have a name.',
            'ingredients.*.ingredient_name.string' => 'The ingredient name must be a string.',
            'ingredients.*.ingredient_name.exists' => 'The ingredient name must exist in the database.',
            'ingredients.*.quantity.required' => 'Each ingredient must have a quantity.',
            'ingredients.*.quantity.integer' => 'The quantity must be an integer.',
            'ingredients.*.quantity.min' => 'The quantity must be at least 1.',
        ];
    }
}
