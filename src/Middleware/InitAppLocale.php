<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class InitAppLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Session::get('locale', config('app.locale'));

        App::setLocale($locale);

        return $next($request);
    }
}
