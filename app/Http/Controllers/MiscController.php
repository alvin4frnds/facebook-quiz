<?php

namespace App\Http\Controllers;

use App\Extras\PluginAutoload;
use App\Extras\ResultGenerator;
use Illuminate\Http\Request;

class MiscController extends Controller
{
    public function test (Request $request) {
    	return view('test');
    }

    public function quizResult (Request $request) {
    	// save the user's info
    	// 
    	$return = [];
    	$user = $request->all()['user'];

    	if ( 'user' === $request->profile ) {
			$return = (new ResultGenerator())->generate_result_user_image( $request->pid, $user );
		} else {
			$return = (new ResultGenerator())->generate_result_friend_image( $request->pid, $user );
		}

    	die(json_encode(array($request->all(), $return)));
    	dd($request->all());
    }
}
