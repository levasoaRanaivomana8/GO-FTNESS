<?php
declare(strict_types=1);
namespace App\Controllers;
use Core\Controller; use Core\Auth; use App\Models\User;
final class AuthController extends Controller {
 public function showLogin(): void { if(Auth::check()) Auth::redirectAfterLogin(); $this->render('auth/login',['baseUrl'=>BASE_URL,'error'=>null,'csrf'=>$this->csrfToken()]); }
 public function login(): void { $this->requireCsrf(); $login=trim((string)($_POST['username']??'')); $pass=(string)($_POST['password']??''); $err='Identifiants incorrects'; if($login===''||$pass===''){ $this->render('auth/login',['baseUrl'=>BASE_URL,'error'=>$err,'csrf'=>$this->csrfToken()]); return; } $u=User::findByLogin($login); if(!$u){ User::fail($login); $this->render('auth/login',['baseUrl'=>BASE_URL,'error'=>$err,'csrf'=>$this->csrfToken()]); return; } if((int)$u['is_active']!==1){ $this->render('auth/login',['baseUrl'=>BASE_URL,'error'=>'Compte désactivé','csrf'=>$this->csrfToken()]); return; } $ok=password_verify($pass,(string)$u['password_hash']) || hash_equals((string)$u['password_hash'],$pass); if(!$ok){ User::fail($login); $msg=((int)$u['failed_attempts']+1>=3)?'Trop de tentatives échouées. L’administrateur a été notifié.':$err; $this->render('auth/login',['baseUrl'=>BASE_URL,'error'=>$msg,'csrf'=>$this->csrfToken()]); return; } User::resetFails((int)$u['iduser']); session_regenerate_id(true); $_SESSION['user']=['iduser'=>(int)$u['iduser'],'username'=>(string)$u['username'],'matricule'=>(string)($u['matricule']??''),'email'=>(string)($u['email']??''),'role'=>(string)$u['role']]; Auth::redirectAfterLogin(); }
 public function logout(): void { Auth::logoutAndRedirect(); }
}
