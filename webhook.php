<?php
	
	require '.config.php';
	
	function incorrect()
	{
		header("HTTP/1.0 404 Not Found");
		echo "<html>";
		echo "<head><title>404 Not Found</title></head>";
		echo "<body>";
		echo "<center><h1>404 Not Found</h1></center>";
		echo "<hr>";
		echo "</body>";
		echo "</html>";

		exit();
	}
	
	function incorrect_data()
	{
		header("HTTP/1.0 403 Forbidden");
		echo "<html>";
		echo "<head><title>403 Forbidden</title></head>";
		echo "<body>";
		echo "<center><h1>403 Forbidden</h1></center>";
		echo "<hr>";
		echo "</body>";
		echo "</html>";
		
		exit();
	}
	
	//only accept POST method
	if($_SERVER['REQUEST_METHOD']!=="POST")incorrect();
	
	if(!is_array($repo_list))incorrect();
	
	$git_server = "https://github.com/";
	$git_pull = "git pull";
	$git_get_remote = "git remote get-url ";
	
	$data = file_get_contents('php://input');
	$data_json = json_decode($data, true);
	
	
	$git_fullname = $data_json['repository']['full_name'];
	if(!array_key_exists($git_fullname, $repo_list))incorrect();
	
	$hmac_sha1 = hash_hmac('sha1', $data, $repo_list[$git_fullname][3]);
	$hmac_sha256 = hash_hmac('sha256', $data, $repo_list[$git_fullname][3]);
	

	//test headers
	if(!isset($_SERVER['HTTP_X_HUB_SIGNATURE']) || $_SERVER['HTTP_X_HUB_SIGNATURE']!==("sha1=".$hmac_sha1))incorrect();
	if(!isset($_SERVER['HTTP_X_HUB_SIGNATURE_256']) || $_SERVER['HTTP_X_HUB_SIGNATURE_256']!==("sha256=".$hmac_sha256))incorrect();

	//test user agent
	if(!isset($_SERVER['HTTP_USER_AGENT']))incorrect();
	if(!preg_match("/^GitHub-Hookshot\/.+$/i", $_SERVER['HTTP_USER_AGENT']))incorrect();
	
	//react only when push
	if(!isset($_SERVER['HTTP_X_GITHUB_EVENT']) && $_SERVER['HTTP_X_GITHUB_EVENT']!=="push")incorrect();
	
	
	//test rcv data
	if($data_json['repository']['url'] !== $git_server.$git_fullname)incorrect_data();
	if($data_json['ref'] !== $repo_list[$git_fullname][2])incorrect_data();
	
	$o = NULL;
	$r = NULL;
	
	exec("cd ".$repo_list[$git_fullname][0]." && ".$git_get_remote.$repo_list[$git_fullname][1], $o, $r);
	
	if($o[0] !== $data_json['repository']['url'])incorrect_data();
	
	$o=NULL;
	$r=NULL;
	
	exec("cd ".$repo_list[$git_fullname][0]." && ".$git_pull." ".$repo_list[$git_fullname][1]." ".$repo_list[$git_fullname][2]." 2>&1", $o, $r);
	
	if($r !== 0)
	{
		header("HTTP/1.0 500 Internal Server Error");
		echo "<html>";
		echo "<head><title>500 Internal Server Error</title></head>";
		echo "<body>";
		echo "<center><h1>500 Internal Server Error</h1></center>";
		echo "<hr>";
		echo "</body>";
		echo "</html>";
		
		exit();
	}
?>
