<?php

namespace App\Http\Controllers;

use App\Extras\PluginAutoload;
use Illuminate\Http\Request;

class MiscController extends Controller
{
    public function test (Request $request) {
    	return view('test');
    }
}
