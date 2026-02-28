<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BeforeRequestMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Увеличиваем лимиты для загрузки файлов
        ini_set('upload_max_filesize', '2048M');
        ini_set('post_max_size', '2048M');
        ini_set('max_execution_time', '600');
        ini_set('max_input_time', '600');
        ini_set('memory_limit', '2048M');

        return $next($request);
    }
}
