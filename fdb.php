<?php 
require 'config.php';


function _fdb_get_checksum(String $collection,String $key){
	$file = fdb_config_location().'/_sys/_checksum/'.$collection.'/'.$key;

	if(file_exists($file)){

		$checksum = file_get_contents($file);

		return array('status' => 'ok','data' => $checksum);

	}else{

		return array('status' => 'error', 'messg' => 'there is no checksum');

	}

}

function _fdb_set_checksum(String $collection,String $key){
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

function _fdb_del_checksum(String $collection,String $key){
	$file = fdb_config_location().'/_sys/_checksum/'.$collection.'/'.$key;
	unlink($file);

	$checksum = _fdb_get_checksum($collection,$key);
	if($checksum['status'] == 'error'){
		return array('status' => 'ok');
	}

	return array('status' => 'error', 'messg' => 'cannot remove checksum');
}


function fdb_set(String $collection,String $key,Array $data,String $checksum){

	$curr_checksum = _fdb_get_checksum($collection,$key);
	if($curr_checksum['status'] != "ok"){
		$curr_checksum = _fdb_set_checksum($collection,$key);
	}

	if($checksum != $curr_checksum['data']){
		return array('status' => 'error', 'messg' => 'checksum missmatch');
	}

	return fdb_set_force($collection,$key,$data);	
}

function fdb_set_force(String $collection,String $key,Array $data){
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

function fdb_get(String $collection,String $key){
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

function fdb_del(String $collection,String $key,String $checksum){
	$curr_checksum = _fdb_get_checksum($collection,$key);
	if($curr_checksum['status'] != "ok"){
		$curr_checksum = _fdb_set_checksum($collection,$key);
	}

	if($checksum != $curr_checksum['data']){
		return array('status' => 'error', 'messg' => 'checksum missmatch');
	}

	return fdb_del_force($collection,$key);
}	

function fdb_del_force(String $collection,String $key){
	$dir_col = fdb_config_location().'/data/'.$collection;
	$file = $dir_col.'/'.$key;

	if(!file_exists($file)){
		return array('status' => 'warning', 'messg' => 'key not found');
	}

	unlink($file);

	$result = fdb_get($collection,$key);
	if($result['status'] == 'error'){
		$checksum = _fdb_del_checksum($collection,$key);
		if($checksum['status'] == 'ok'){
			return array('status' => 'ok');
		}else{
			return $checksum;
		}
	}

	return array('status' => 'error', 'messg' => 'cannot delete key');

}



function fdb_get_all(String $collection){
	$dir_col = fdb_config_location().'/data/'.$collection;

	if(!file_exists($dir_col)){
		return array('status' => 'error', 'messg' => 'collection not found');
	}

	$files = array_values(array_filter(glob($dir_col."/*"), 'is_file'));

	$data = array();

	foreach($files as $file){
		$file = basename($file);
		$d = fdb_get($collection,$file);
		$data[] = $d['data'];
	}

	return array('status' => 'ok', 'data' => $data);

}

function fdb_get_by(String $collection,String $key,String $operator,String $value){

	$operator_valid = array("=",">",">=","<","<=","!=","contains");
	if(!in_array($operator,$operator_valid)){
		return array('status' => 'error', 'messg' => 'invalid operator');
	}

	$data = array();

	$files = fdb_get_all($collection);
	if($files['status'] == 'error'){
		return $files;
	}
	foreach($files['data'] as $file){
		if($operator == "="){
			if($file[$key] == $value){
				$data[] = $file;
			}
		}elseif($operator == ">"){
			if($file[$key] > $value){
				$data[] = $file;
			}

		}elseif($operator == ">="){
			if($file[$key] >= $value){
				$data[] = $file;
			}

		}elseif($operator == "<"){
			if($file[$key] < $value){
				$data[] = $file;
			}

		}elseif($operator == "<="){
			if($file[$key] <= $value){
				$data[] = $file;
			}

		}elseif($operator == "!="){
			if($file[$key] != $value){
				$data[] = $file;
			}

		}elseif($operator == "contains"){
			if(strrpos($file[$key],$value) !== false){
				$data[] = $file;
			}
		}
	}

	if(count($data) > 0){

		return array('status' => 'ok', 'data' =>$data);

	}

	return array('status' => 'warning', 'data' => $data, 'messg' => 'empty result');
}

