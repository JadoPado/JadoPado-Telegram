<?
/*
Telegram background service
*/

require_once('config.php');
require_once('helper.php');

$telegram = new Helper();

$customer = false;
$x_verbose = false;
foreach ($argv as $k=>$arg) {

    $arg = trim($arg);

    if ($arg == '--customer' || $arg == '-c')
        $customer = true;

    if ($arg == '-v' || $arg == '--verbose') {
        $x_verbose = true;
        echo "\n\n".'Verbose mode           : ON'."\n";
    }

    
}

if(!$customer){
	$port = $telegram->admin_service_port;
	$cmd = "/root/tg/bin/telegram-cli -k /root/tg/tg-server.pub -c /root/tg/config -p profileadmin --json -I -R -C -P ".$port;
}else
{
	$port = $telegram->customer_service_port;
	$cmd = "/root/tg/bin/telegram-cli -k /root/tg/tg-server.pub -c /root/tg/config -p profilecustomer --json -I -R -C -P ".$port;
}


$descriptorspec = array(
   0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
   2 => array("pipe", "w")    // stderr is a pipe that the child will write to
);
flush();


try{
$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./'), array());
if (is_resource($process)) {

echo "\n------------------------------\n";
echo "Telegram cli background service started\n";

$status = proc_get_status($process);	


    while ($response = fgets($pipes[1])) {
		$response = trim($response);		
		$response = $telegram->sanitize_output($response);
		if($response){
			
			if(!$customer && isset($response->event) && strtolower($response->event) == 'message')
				$telegram->incoming_message($response);

		}
		if($response && $x_verbose){
			echo "\n------------------------------\n";
			print_r($response);
			echo "\n------------------------------\n";
		}

        flush();
        usleep(1000);
    }
}
}catch(Exception $e){
	
		echo 'Error '.$e->getMessage();
	
}
?>
