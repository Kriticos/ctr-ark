<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateMenuRequest extends FormRequest
{
    public const MAX_50 = 'max:50';

    public const MAX_1000 = 'max:1000';

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
            'module_id' => ['nullable', 'exists:modules,id'],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'title' => ['required', 'string', self::MAX_255],
            'icon' => ['nullable', 'string', self::MAX_255],
            'route_name' => ['nullable', 'string', self::MAX_255],
            'url' => ['nullable', 'string', self::MAX_255],
            'permission_name' => ['nullable', 'string', self::MAX_255],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_divider' => ['boolean'],
            'target' => ['in:_self,_blank'],
            'badge' => ['nullable', 'string', self::MAX_50],
            'badge_color' => ['nullable', 'string', self::MAX_50],
            'description' => ['nullable', 'string', self::MAX_1000],
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
            'module_id' => 'módulo',
            'parent_id' => 'menu pai',
            'title' => 'título',
            'icon' => 'ícone',
            'route_name' => 'nome da rota',
            'url' => 'URL',
            'permission_name' => 'permissão',
            'order' => 'ordem',
            'is_active' => 'ativo',
            'is_divider' => 'divisor',
            'target' => 'alvo',
            'badge' => 'badge',
            'badge_color' => 'cor do badge',
            'description' => 'descrição',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active'),
            'is_divider' => $this->has('is_divider'),
        ]);
    }
}
