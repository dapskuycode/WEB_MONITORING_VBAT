<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user && $user->isSponsor()) {
            session()->forget('url.intended');

            return redirect()->route('sponsor.dashboard');
        }

        if ($user && $user->isInstructor()) {
            session()->forget('url.intended');

            return redirect()->route('admin.content');
        }

        $intended = session()->pull('url.intended');
        if (is_string($intended) && ! in_array(parse_url($intended, PHP_URL_PATH), ['/', '/login', ''], true)) {
            return redirect()->to($intended);
        }

        return redirect()->route('dashboard');
    }
}
