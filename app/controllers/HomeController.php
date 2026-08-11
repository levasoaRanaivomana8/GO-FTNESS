<?php
declare(strict_types=1);
namespace App\Controllers; use Core\Auth; use Core\Controller;
final class HomeController extends Controller { public function index(): void { Auth::redirectAfterLogin(); } }
