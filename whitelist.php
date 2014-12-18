<?php
/*
	XenForo / Whitelist Script
	--------------------------
	Uses the members list in XenForo to sync up the whitelist on a Minecraft Server

	(c) 2014, stephanie harms <stephanie.olivia.harms@gmail.com>
*/

//
// SCRIPT SETTINGS
//
$hostname = 'hostname';
$username = 'username';
$password = 'password';
$database = 'database';

$screenid = 'minecraft';
$listfile = './whitelist.json';

// -------------------------------

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

// build a list of currently whitelisted names form whitelist.json
foreach($data as $players) {
	array_push($list, $players['name']);
}

// loop through the xenforo users
while($row = $xen->fetch_assoc()) {

	$name = $row['username'];

	// if the member's name isn't in the whitelist...
	if(!in_array($name, $list)) {

		// add member + reload whitelist
		$cmd = `screen -S {$screenid} -X eval 'stuff "whitelist add {$name}"'\015`;
		$cmd = `screen -S {$screenid} -X eval 'stuff "whitelist reload"'\015`;

		// log this!
		$log = "[ADD] ".$name." - ".date('F j, Y, g:i:s a') . PHP_EOL;
		file_put_contents('./log/'.date('Y.n.j').'.log', $log, FILE_APPEND);
	} else {
		// they're already whitelisted - check if they've been banned recently
		if($row['is_banned'] == 1) {
			$cmd = `screen -S {$screenid} -X eval 'stuff "whitelist remove {$name}"'\015`;
			$cmd = `screen -S {$screenid} -X eval 'stuff "whitelist reload"'\015`;

			$log = "----- " . PHP_EOL
				 . "[BAN] ".$name." - ".date('F j, Y, g:i:s a') . PHP_EOL
				 . "----- " . PHP_EOL;
			file_put_contents('./log/'.date('Y.n.j').'.log', $log, FILE_APPEND);
		}
	}
}

$connection->close();

// end of file
?>
