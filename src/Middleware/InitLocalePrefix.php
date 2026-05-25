<?php

declare(strict_types=1);

namespace Givanov95\LaravelTranslations\Middleware;

use Closure;
use Givanov95\LaravelTranslations\Translator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class InitLocalePrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        $segment = $request->segment(1);

        if ($segment && in_array($segment, Translator::getAllLocales(), true)) {
            App::setLocale($segment);
            Session::put('locale', $segment);
        } else {
            App::setLocale(Session::get('locale', config('app.locale')));
        }

        return $next($request);
    }
}
