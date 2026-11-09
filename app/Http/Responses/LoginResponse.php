<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();
        if ($user && $user->homePanelPath() === '/developer') {
            $intended = $request->session()->pull('url.intended');
            if ($intended && str_contains($intended, '/developer')) {
                return redirect()->intended('/developer');
            }

            return redirect('/developer');
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
