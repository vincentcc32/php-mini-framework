<?php

namespace App\Validation;

use Core\Validation;

class LoginValidation extends Validation
{
  protected function rules()
  {
    return [
      'email' => 'required|email',
      'name' => 'required|min:2'
    ];
  }

  protected function messages()
  {
    return [
      'email.required' => 'Email is required',
      'email.email' => 'Email is not valid',
      'name.required' => 'name is required',
      'name.min' => 'name must be at least 2 characters'
    ];
  }
}
