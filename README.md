# PHP real time chat using the Telegram API
This is based on Telegram CLI wrapper ( vysheng/tg ), built on top of the Telegram API - http://core.telegram.org/api

## Steps to install

1. Install Telegram CLI wrapper
  https://github.com/vysheng/tg/blob/master/README.md#installation

2. Goto your tg directory and create 2 profiles named profileadmin and profilecustomer respectively.
    * Create two directories -
    	* create folder "profileadmin"
    	* create folder "profilecustomer"

3. Initial profile setup for profileadmin account
> tg/bin/telegram-cli -k tg/tg-server.pub -c tg/config  -p profileadmin --json -I -R -C -P 3000

Initial profile setup for profilecustomer account
> tg/bin/telegram-cli -k tg/tg-server.pub -c tg/config  -p profilecustomer --json -I -R -C -P 3001


You are ready to run the telegram monitoring service

> php telegram_service.php -v

Open another terminal and run

> php telegram_service.php -c -v

Sample tg config
```
default_profile = "profileadmin";
profileadmin = {
  config_directory = "/path/tg/profileadmin";
  msg_num = true;
  binlog_enabled = true;
};

profilecustomer = {
  config_directory = "/path/tg/profilecustomer";
  msg_num = true;
  binlog_enabled = true;
};
```


Sample php code to test the service
```php
<?
try{
$telegram = new Helper();

$data = array();
$chat_topic = 'This is a new group';
$data['chat_topic'] = $chat_topic ;
$telegram->create_group_chat($data);
$chat_id = $telegram->get_chat_id($chat_topic);
$telegram->send_message_to_group($chat_id,'test message',$telegram->admin_service_port);

}catch(Exception $e){
	echo $e->getMessage();
}
?>
```

Sample config
```php
<?
define('ADMIN_ID','YOUR_TELEGRAM_USER_ID');
define('CUSTOMER_ID','YOUR_CUSTOMER_USER_ID');
define('ADMIN_PORT','Admin service port number');/* eg .. 3000 */
define('CUSTOMER_PORT','Client service port number');/* eg .. 3001 */
define('HOST','localhost');
define('LOG_PATH','logs/info.txt');
?>
```


## Acknowledgements

This integration depends on Vygeng's Telegram-CLI (https://github.com/vysheng/tg) and on the core Telegram API
