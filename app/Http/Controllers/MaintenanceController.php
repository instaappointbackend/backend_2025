<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class MaintenanceController extends Controller
{
    public function dumpAutoload(Request $request)
    {
        // Security check - verify a secret token
        if (!$request->has('token') || !Hash::check('your-secret-phrase', $request->token)) {
            abort(403, 'Unauthorized action.');
        }

        // Change directory to the Laravel root (one level up from public)
        $rootPath = base_path();

        // Path to composer
        $composerPath = env('COMPOSER_PATH', 'composer');

        // Execute the dump-autoload command from the root directory
        $output = [];
        $returnVar = 0;

        // Using cd to change to the root directory before running composer
        exec('cd ' . $rootPath . ' && ' . $composerPath . ' dump-autoload 2>&1', $output, $returnVar);

        // Return the results
        return response()->json([
            'success' => $returnVar === 0,
            'output' => $output,
            'return_code' => $returnVar,
            'path' => $rootPath
        ]);
    }
}
