<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class MainController extends Controller {
    public function index() {
        $plans = Plan::where('is_custom', false)->active()->get();
        $options = PlanOption::active()->get();
        $flowers = File::files(public_path('images/flowers'));

        return view('main.index', compact('plans', 'options', 'flowers'));
    }
}
