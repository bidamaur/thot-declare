<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Ces identifiants ne correspondent à aucun compte.'],
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user('web');

        return response()->json([
            'user' => $this->safeUser($user),
            'redirect' => '/',
            'must_change_password' => $user->must_change_password,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Déconnecté.']);
    }

    public function user(Request $request)
    {
        $user = $request->user('web');

        if (!$user) {
            return response()->json(['user' => null], 401);
        }

        return response()->json([
            'user' => $this->safeUser($user),
        ]);
    }

    public function register(Request $request)
    {
        if (!$request->user('web') || !$request->user('web')->isAdmin()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(User::ROLES)],
        ], [
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'user' => $this->safeUser($user),
            'message' => 'Utilisateur créé.',
        ], 201);
    }

    public function update(Request $request)
    {
        $user = $request->user('web');

        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->filled('password') && !Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        if ($request->filled('name')) {
            $user->name = $validated['name'];
        }

        if ($request->filled('email')) {
            $user->email = $validated['email'];
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
            $user->must_change_password = false;
        }

        $user->save();

        return response()->json([
            'user' => $this->safeUser($user),
            'message' => 'Profil mis à jour.',
        ]);
    }

    public function users(Request $request)
    {
        $user = $request->user('web');
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $users = User::select('id', 'name', 'email', 'role', 'created_at')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'created_at' => $u->created_at,
            ];
        });

        return response()->json(['users' => $users]);
    }

    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user('web');
        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }

    public function resetPassword(Request $request, User $user)
    {
        $currentUser = $request->user('web');
        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'Utilisez votre profil pour modifier votre propre mot de passe.'], 422);
        }

        $temporaryPassword = 'Thot-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)) . '!';
        $user->password = Hash::make($temporaryPassword);
        $user->must_change_password = true;
        $user->save();

        return response()->json([
            'message' => 'Mot de passe temporaire généré. Il doit être remplacé à la prochaine connexion.',
            'temporary_password' => $temporaryPassword,
        ]);
    }

    public function switchUser(Request $request, User $user)
    {
        $currentUser = $request->user('web');
        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::guard('web')->login($user, true);

        return response()->json([
            'user' => $this->safeUser($user),
            'redirect' => '/',
        ]);
    }

    private function safeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'must_change_password' => (bool) $user->must_change_password,
        ];
    }
}
