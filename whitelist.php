<?php
/*
	XenForo / Whitelist Script
	--------------------------
	This takes advantage of punching in a username into the MC console to add someone
	to the whitelist, it then reloads the whitelist immediately

	(c) stephanie harms, 2014
*/

// database connection details
$hostname = 'hostname';	// a.k.a. server name - this is usually 'localhost'
$username = 'username';
$password = 'password';
$database = 'database';

// screen session name
$screenid = 'minecraft';
$listfile = './whitelist.json';

// mysql connection
$connection = new mysqli($hostname, $username, $password, $database);
if ($connection->connect_error) {
	echo "Failed to connect to database: " . $connection->connect_error;
}

// select all users in xenforo
$sql = "SELECT * FROM xf_user ORDER BY user_id ASC";
$xen = $connection->query($sql);

// get the whitelist from minecraft
$json = file_get_contents($listfile);
$data = json_decode($json, true);

$list = array();

foreach($data as $mcp) {
	array_push($list, $mcp['name']);
}

// loop through the xenforo users
while($row = $xen->fetch_assoc()) {

	$name = $row['username'];
	if(!in_array($name, $list)) {
		$cmd = `screen -S {$screenid} -X eval 'stuff "whitelist add {$name}"'\015`;
		$cmd = `screen -S {$screenid} -X eval 'stuff "whitelist reload"'\015`;

		$log = "[ADD] ".$name." - ".date('F j, Y, g:i:s a') . PHP_EOL;
		file_put_contents('./log/'.date('Y.n.j').'.log', $log, FILE_APPEND);
	} else {
		// they're already whitelisted - check if they've been banned recently
		if($row['is_banned'] == 1) {
			$cmd = `screen -S {$screenid} -X eval 'stuff "whitelist remove {$name}"'\015`;
			$cmd = `screen -S {$screenid} -X eval 'stuff "whitelist reload"'\015`;

			$log = "----- " .PHP_EOL
				 . "[BAN] ".$name." - ".date('F j, Y, g:i:s a') . PHP_EOL
				 . "----- " . PHP_EOL;
			file_put_contents('./log/'.date('Y.n.j').'.log', $log, FILE_APPEND);
		}
	}
}

$connection->close();

// end of file
?>
