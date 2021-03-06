<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class AccountUpdateRequest extends FormRequest
{
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
        return [
            'account' => 'nullable|between:2,191',
            'nickname' => 'nullable|between:2,191',
            'email' => 'nullable|email|between:2,191',
            'phone' => 'nullable|regex:/^1\d{10}$/|size:11',
            'organization_id' => 'nullable|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'account.required' => '账号不能为空',
            'account.between' => '账号必须大于2位小于191位',
            'nickname.between' => '昵称小于2位或大于191位',
            'email.email' => '邮箱地址格式不正确',
            'email.between' => '邮箱地址小于2位或大于191位',
            'phone.regex' => '手机号格式不正确',
            'phone.size' => '手机号长度必须是11位',
            'organization_id.integer' => '机构编号必须是数字',
            'organization_id.min' => '机构编号不能小于1',
        ];
    }
}
