<?php 
require_once 'fdb.php';

if(count($argv) != 2){
	exit("invalid parameter");
}

$dir = $argv[1];

if(!file_exists($dir)){
	mkdir($dir);
}

$sys_dir = $dir.'/_sys';

if(!file_exists($sys_dir)){
	mkdir($sys_dir);
}

$checksum_dir = $sys_dir.'/_checksum';

if(!file_exists($checksum_dir)){
	mkdir($checksum_dir);
}

$data_dir = $dir.'/data';

if(!file_exists($data_dir)){
	mkdir($data_dir);
}
