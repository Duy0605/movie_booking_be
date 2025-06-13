<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Add CORS headers
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173, movie-ticket-murex.vercel.app');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        // Handle preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            $response->setStatusCode(200);
            $response->headers->set('Content-Length', '0');
            $response->setContent(null);
        }

        return $response;
    }
}