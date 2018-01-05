<?php

use App\PostMeta;
use Illuminate\Support\Facades\DB;

function get_post_meta($postId, $meta_key = null, $single = true) {
	if (is_null($meta_key)) return false;

	if ($single) {
		$meta_value = PostMeta::where('post_id', $postId)->where('meta_key', $meta_key)->first();

		if (! $meta_value)  return false;
		$meta_value = $meta_value->meta_value;

	} else {
		$meta_value = PostMeta::where('post_id', $postId)->where('meta_key', $meta_key)->pluck('meta_value')->toArray();

		if (! $meta_value) return [];
		$meta_value = array_values($meta_value);
		
	}
	
	return unserialized($meta_value);
}

function get_option($key = null) {
	
	if (is_null($key)) return false;
	
	$optionValue = DB::table(env("WPDB_PREFIX")."options")->where('option_name')->first();
	if ($optionValue) return unserialized($optionValue->option_value);
	else return false;
}

function plugin_dir_path() {
	return base_path('app/Extras/wp-quiz-pro');
}

function unserialized($string) {
	$data = @unserialize($string);
	if ($string === 'b:0;' || $data !== false) {
		return $data;
	} else {
		return $string;
	}
}

function upload_dir() {
	list($year, $month, $day) = explode("-", date('Y-m-d'));
	
	$base = "uploads/{$year}/{$month}/{$day}/";
	
	return [
		'basedir' => storage_path("app/public/{$base}"),
		'baseurl' => asset("storage/{$base}"),
	];
}

function download_tmp_image($url) {
	$tmpDirPath = storage_path('temp');
	
	if (! is_dir($tmpDirPath)) mkdir($tmpDirPath);
	
	$filePath = $tmpDirPath. "/". totally_random_file_name().".png";
	
	$content = file_get_contents($url);
	
	file_put_contents($filePath, $content);
	
	return $filePath;
}

function totally_random_file_name() {
	return str_replace( ".", "-", microtime( true ) ) . "-" . rand( 0, 1000 );
}