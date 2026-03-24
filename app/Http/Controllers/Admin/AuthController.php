<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\Auth\LoginController as BackpackLoginController;
use Illuminate\Http\Request;

class AuthController extends BackpackLoginController
{
    /**
     * Validate the user login request. Only require email (password bypassed).
     */
    protected function validateLogin(Request $request): void
    {
        $request->validate([
            $this->username() => 'required|string',
        ]);
    }

    /**
     * Attempt to log the user into the application without checking password.
     */
    protected function attemptLogin(Request $request): bool
    {
        $user = $this->guard()->getProvider()->getModel()::query()
            ->where($this->username(), $request->input($this->username()))
            ->first();

        if ($user === null) {
            return false;
        }

        $this->guard()->login($user, $request->filled('remember'));

        return true;
    }
}
