<?php 
require 'config.php';


function _fdb_get_checksum($collection,$key){
	$file = fdb_config_location().'/_sys/_checksum/'.$collection.'/'.$key;

	if(file_exists($file)){

		$checksum = file_get_contents($file);

		return array('status' => 'ok','data' => $checksum);

	}else{

		return array('status' => 'error', 'messg' => 'there is no checksum');

	}

}

function _fdb_set_checksum($collection,$key){
	$dir_col = fdb_config_location().'/_sys/_checksum/'.$collection;

	if(!file_exists($dir_col)){
		mkdir($dir_col);
	}
	
	$file = $dir_col.'/'.$key;

	$curr_checksum = _fdb_get_checksum($collection,$key);

	if($curr_checksum['status'] == 'error'){

		$checksum = rand(1,1000);

		file_put_contents($file,$checksum);

		return array('status'=>'ok','data'=>$checksum);

	}else{
		$checksum = $curr_checksum['data'];
		while($checksum == $curr_checksum['data']){
			$checksum = rand(1,1000);
		}

		file_put_contents($file,$checksum);
		return array('status'=>'ok','data'=>$checksum);


	}

}


function fdb_set($collection,$key,$data,$checksum){
	
	$curr_checksum = _fdb_get_checksum($collection,$key);
	if($curr_checksum['status'] != "ok"){
		$curr_checksum = _fdb_set_checksum($collection,$key);
	}

	if($checksum != $curr_checksum['data']){
		return array('status' => 'error', 'messg' => 'checksum missmatch');
	}

	return fdb_set_force($collection,$key,$data);	
}

function fdb_set_force($collection,$key,$data){
	$dir_col = fdb_config_location().'/data/'.$collection;
	$file = $dir_col.'/'.$key;

	if(!file_exists($dir_col)){
		mkdir($dir_col);
	}

	// unset sys fields
	unset($data['_id']);
	unset($data['_checksum']);

	$data_json = json_encode($data);

	file_put_contents($file,$data_json);

	$curr_checksum = _fdb_set_checksum($collection,$key);

	return array('status' => 'ok', 'data' => $curr_checksum['data']);

}

function fdb_get($collection,$key){
	$dir_col = fdb_config_location().'/data/'.$collection;
	$file = $dir_col.'/'.$key;

	if(!file_exists($file)){
		return array('status' => 'error', 'messg' => 'key not found');
	}

	$json_data = file_get_contents($file);
	$data = json_decode($json_data,TRUE);
	$data['_id'] = $key;

	$curr_checksum = _fdb_get_checksum($collection,$key);
	$data['_checksum'] = $curr_checksum['data'];

	return array('status' => 'ok', 'data' => $data);

}

function fdb_get_all($collection){
	$dir_col = fdb_config_location().'/data/'.$collection;

	if(!file_exists($dir_col)){
		return array('status' => 'error', 'messg' => 'collection not found');
	}

	$files = array_values(array_filter(glob($dir_col."/*"), 'is_file'));

	$data = array();

	foreach($files as $file){
		$file = basename($file);
		$data[] = fdb_get($collection,$file);
	}

	return array('status' => 'ok', 'data' => $data);

}

