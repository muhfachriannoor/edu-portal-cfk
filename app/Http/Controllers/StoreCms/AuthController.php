<?php

namespace App\Http\Controllers\StoreCms;

use App\Models\Store;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{

    public function index(Store $store): View
    {
        return view('store_cms.auth.index', ['store' => $store]);
    }

    public function authenticate(Store $store, Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required'],
            'password' => ['required'],
        ]);

        $credentials['store_id'] = $store->id;

        if (Auth::guard('store_owner')->attempt($credentials)) {
            $user = Auth::guard('store_owner')->user();

            // Check if account is inactive
            if ($user->is_active == 0) {
                Auth::guard('store_owner')->logout(); // Important: Logout the user immediately
                return back()->withErrors([
                    'email' => 'Your account is currently inactive. Please contact the administrator for assistance.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->route('store_cms.dashboard', $store);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Store $store)
    {
        Auth::guard('store_owner')->logout();
        return redirect()->route('store_cms.login', $store);
    }
}
