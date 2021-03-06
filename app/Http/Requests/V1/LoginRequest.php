<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public static $MESSAGES = [
        'account.required' => '账号不能为空',
        'account.between' => '账号不能小于2位或大于191位',
        'password.required' => '密码不能为空',
        'password.between' => '密码小于6位或大于25位',
    ];

    public static $RULES = [
        'account' => 'required|between:2,191',
        'password' => 'required|between:6,25',
    ];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return self::$RULES;
    }

    public function messages()
    {
        return self::$MESSAGES;
    }
}
