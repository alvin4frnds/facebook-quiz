<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Extras\Request;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', 'MiscController@test');
Route::post('/quiz-result', 'MiscController@quizResult');

Route::get('/connection', function() {
	$return = (new Request())->postOthers("https://fun2all.dev/wp-admin/admin-ajax.php", [
		"action" => "wq_submitFbInfo",
		"pid" => 74,
		"user" => [
			"name"=> "Praveen Kumar",
			"gender" => "male",
			"first_name" => "Praveen",
			"last_name" => "Kumar",
			"email" => "pkpraveen16@yahoo.com",
			"id" => "1563766890408821",
			"friends" => [
				[
					'name' => "krishan Tyagi",
					"id" => '1929082433787329',
				]
			]
		],
		"profile" => "user",
	]);
	
	if (is_string($return)) return $return;
	else dd($return);
	
	dd(DB::connection('wpdb')->getPdo());
	return "done";
});

Route::get('/fake-request', function() {
	$return = (new Request())->postOthers("https://facebook-quiz.dev/quiz-result", [
		"action" => "wq_submitFbInfo",
		"pid" => 74,
		"user" => [
			"name"=> "Praveen Kumar",
			"gender" => "male",
			"first_name" => "Praveen",
			"last_name" => "Kumar",
			"email" => "pkpraveen16@yahoo.com",
			"id" => "1563766890408821",
			"friends" => [
				[
					'name' => "krishan Tyagi",
					"id" => '1929082433787329',
				]
			]
		],
		"profile" => "user",
		"_token"  => csrf_token(),
	]);
	
	if (is_string($return)) return $return;
	else dd($return);
	
	return "done";
});