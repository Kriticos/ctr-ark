<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreModuleRequest extends FormRequest
{
    public const MAX_255 = 'max:255';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', self::MAX_255],
            'slug' => ['required', 'string', self::MAX_255, 'unique:modules', 'regex:/^[a-z0-9\-]+$/'],
            'icon' => ['nullable', 'string', self::MAX_255],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'slug' => 'slug',
            'icon' => 'ícone',
            'description' => 'descrição',
            'order' => 'ordem',
        ];
    }
}
