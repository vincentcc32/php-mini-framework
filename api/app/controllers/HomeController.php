<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Validation\LoginValidation;
use Core\Request;

class HomeController extends Controller
{
  public function index(Request $request) {}
  public function create(Request $request)
  {
    $name = $request->input('name');
    $email = $request->input('email');
    $validator = new LoginValidation();
    $validator->validate([
      'email' => $email,
      'name' => $name
    ]);
  }
  public function show(Request $request, $id) {}
  public function show2($id, $slug) {}
  public function login() {}
}
