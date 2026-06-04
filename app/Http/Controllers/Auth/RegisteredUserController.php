<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $referrerId = null;
        if ($request->filled('ref')) {
            try {
                $decoded = base64_decode($request->ref);
                if (is_numeric($decoded)) {
                    $referrerId = (int) $decoded;
                }
            } catch (\Exception $e) {
                // Ignore invalid referrer code
            }
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->tangki_balance = 0;
        $user->tangki_oz = 0;
        $user->referrer_id = $referrerId;
        $user->save();

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Registered successfully.']);
        }

        return redirect()->route('dashboard');
    }
}