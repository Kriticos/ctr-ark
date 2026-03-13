<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', self::MAX_255, 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'avatar' => ['nullable', 'string', 'regex:/^data:image\/[^;]+;base64,.+/'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'sector_access' => ['nullable', 'array'],
            'sector_access.*.sector_id' => ['required', 'exists:sectors,id'],
            'sector_access.*.role' => ['nullable', 'in:manager,editor,reader'],
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
            'email' => 'e-mail',
            'password' => 'senha',
            'avatar' => 'foto',
            'roles' => 'roles',
            'sector_access' => 'setores de acesso',
        ];
    }
}
