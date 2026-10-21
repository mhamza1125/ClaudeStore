<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    public function theme(Request $request)
    {
        $data = $request->validate(['theme' => ['required', Rule::in(['light', 'dark', 'system'])]]);
        $request->user()->forceFill(['theme' => $data['theme']])->save();

        return response()->noContent();
    }
}
