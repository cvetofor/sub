<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CityController extends Controller {
    public function set(Request $request) {
        $request->validate([
            'city_id' => 'required|integer|exists:cities,id',
        ]);

        $request->session()->put('city_id', $request->city_id);

        return response()->json(['success' => true]);
    }
}
