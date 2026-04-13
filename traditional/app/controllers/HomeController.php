<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Models\User;
use App\Validation\LoginValidation;
use Core\Request;
use Core\View;

class HomeController extends Controller
{
  public function index(Request $request)
  {
    $user = new User();
    $users = $user->query()->select(['giohang.MaTaiKhoan as ID_GioHang', 'taikhoan.MaTaiKhoan as ID_TaiKhoan'])->join('giohang', 'taikhoan.MaTaiKhoan', '=', 'giohang.MaTaiKhoan')->get();
    dd($users);

    var_dump($request->all());
    echo "
    <form method='POST' action='/'>
      <input type='text' name='name' placeholder='Enter your name'>
      <input type='text' name='email' placeholder='Enter your name'>
      <button type='submit'>Submit</button>
    </form>
    ";
  }
  public function create(Request $request)
  {
    $name = $request->input('name');
    $email = $request->input('email');
    $validator = new LoginValidation();
    $validator->validate([
      'email' => $email,
      'name' => $name
    ]);
    View::make('app/views/LoginView.php', 'app/views/layout/MasterLayout.php', [
      'errors' => $validator->getErrors(),
      'old' => [
        'name' => $validator->old('name'),
        'email' => $validator->old('email')
      ],
      'success' => $validator->getErrors() ? 'Validation failed' : 'Validation passed'
    ])->render();
  }
  public function show(Request $request, $id)
  {
    var_dump($request->all());
    echo "Showing item with ID: " . $id;
  }
  public function show2($id, $slug)
  {
    echo "Showing item with ID: " . $id . " and slug: " . $slug;
  }
  public function login()
  {
    echo "Login page";
  }
}
