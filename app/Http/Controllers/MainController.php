<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanOptions;
use Illuminate\Http\Request;

class MainController extends Controller {
    public function index() {
        $plans = Plan::where('is_custom', false)->active()->get();
        $options = PlanOptions::active()->get();

        return view('main.index', compact('plans', 'options'));
    }
}
