<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

class EnsureAdminAccount
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (!User::where('role', User::ROLE_ADMIN)->exists()) {
                $email = (string) env('ADMIN_EMAIL', 'admin@thot-declare.local');
                $user = User::firstOrNew(['email' => $email]);

                if (!$user->exists) {
                    $user->name = 'Administrateur';
                    $user->password = Hash::make((string) env('ADMIN_PASSWORD', 'admin123'));
                }

                $user->role = User::ROLE_ADMIN;
                $user->must_change_password = false;
                $user->save();
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return $next($request);
    }
}
