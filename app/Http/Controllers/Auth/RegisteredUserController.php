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
            'ref' => ['nullable', 'uuid'],
        ]);

        $referrerId = null;
        $referrer = null;
        if ($request->filled('ref')) {
            $referrer = User::where('referral_code', $request->ref)->first();
            $referrerId = $referrer?->id;
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->tangki_balance = 0;
        $user->tangki_oz = 0;
        $user->referrer_id = $referrerId;
        $user->referred_by = $referrerId;
        $user->save();

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Registered successfully.']);
        }

        return redirect()->route('dashboard');
    }
}
