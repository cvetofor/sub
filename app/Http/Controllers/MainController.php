<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class MainController extends Controller {
    public function index() {
        $plans = Plan::where('is_custom', false)->active()->get();

        return view('main.index', compact('plans'));
    }
}
