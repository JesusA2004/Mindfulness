<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $this->route('user'),
            'password' => $this->isMethod('POST')
                          ? 'required|string|min:6|confirmed'
                          : 'nullable|string|min:6|confirmed',
            'rol'      => 'required|string|in:admin,empleado,supervisor,user',
        ];
    }

}
