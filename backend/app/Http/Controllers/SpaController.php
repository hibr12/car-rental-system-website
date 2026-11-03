<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SpaController extends Controller
{
    public function __invoke(): Response
    {
        $indexPath = public_path('index.html');

        if (!is_file($indexPath)) {
            return response()->view('welcome');
        }

        return response(file_get_contents($indexPath), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
