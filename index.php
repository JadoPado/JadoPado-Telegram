<?
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/vendor/autoload.php';

try{
$telegram = new Helper();

$data = array();
$chat_topic = 'This is a new group'.'_'.date("d_m_y_H_i_s");
$data['chat_topic'] = $chat_topic ;
$telegram->create_group_chat($data);
$chat_id = $telegram->get_chat_id($chat_topic);
$result = $telegram->send_message_to_group($chat_id,'test message',$telegram->admin_service_port);
var_dump($result);

}catch(Exception $e){
	echo $e->getMessage();
}
?>