<?
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Helper
{
    
    public $host = '';
    public $admin_telegram_user_id = '';
    public $customer_telegram_user_id = '';
    public $admin_service_port = '';
    public $customer_service_port = '';
    public $log_file = '';
    
    public function __construct()
    {
        $this->admin_telegram_user_id    = ADMIN_ID;
        $this->customer_telegram_user_id = CUSTOMER_ID;
        $this->admin_service_port        = ADMIN_PORT;
        $this->customer_service_port     = CUSTOMER_PORT;
        $this->host                      = HOST;
        $this->log_file                  = LOG_PATH;
    }
    
    public function traceLog($log_text, $error = false)
    {
        // create a log channel
        $log_text = print_r($log_text, true);
        $log      = new Logger('name');
        $log->pushHandler(new StreamHandler($this->log_file, Logger::WARNING));
        if (!$error)
            $log->addWarning($log_text);
        else
            $log->addError($log_text);
        
    }
    
    public function sanitize_chat_topic($chat_topic)
    {
        $chat_topic = str_replace(' ', '_', $chat_topic);
        return str_replace('#', '@', $chat_topic);
    }
    
    public function create_group_chat($data)
    {
        
        try {
            
            if (!isset($data['chat_topic']))
                return false;
            
            $users             = '';
            $chat_topic        = $data['chat_topic'];
            $chat_topic        = $this->safe_string($chat_topic);
            $result['success'] = false;
            
            if (isset($data['telegram_user_ids']))
                foreach ($data['telegram_user_ids'] as $user_id)
                    $users .= ' user#' . $user_id;
            
            /* Addal users to the group*/
            $users .= ' user#' . $this->admin_telegram_user_id;
            $users .= ' user#' . $this->customer_telegram_user_id;
            
            $command = "create_group_chat \"$chat_topic\"" . $users;
            
            $output = $this->run_command($command, $this->admin_service_port);
            
            if ($output && isset($output->result) && strtolower($output->result) == 'success')
                return $output;
            
            return false;
            
        }
        catch (Exception $e) {
            $this->traceLog($e->getMessage());
        }
        
    }
    
    public function safe_string($str)
    {
        return str_replace(array(
            '"',
            "\n"
        ), array(
            '\"',
            "\\n"
        ), $str);
    }
    
    public function send_message_to_user($user_id, $message, $port)
    {
        $message = $this->safe_string($message);
        return $this->run_command("msg user#" . $user_id . " \"" . $message . "\"", $port);
    }
    
    public function chat_info($chat_id, $port)
    {
        $message = $this->safe_string($message);
        return $this->run_command("msg user#" . $user_id . " \"" . $message . "\"", $port);
    }
    
    public function chat_add_user($chat_id, $user_id, $port)
    {
        return $this->run_command("chat_add_user chat#" . $chat_id . " user#" . $user_id, $port);
    }
    
    public function send_message_to_group($chat_id, $message, $port)
    {
        $message = $this->safe_string($message);
        return $this->run_command("msg chat#" . $chat_id . " \"" . $message . "\"", $port);
    }
    
    public function load_photo($msg_id, $port)
    {
        
        return $this->run_command("load_photo $msg_id", $port);
    }
    
    public function download_file($data_obj, $port)
    {
        
        $download_info = $this->load_photo($data_obj->id, $port);
        
        if ($download_info && isset($download_info->event) && $download_info->event == 'download') {
            $file_path = $download_info->result;
            if (file_exists($file_path)) {
                return $file_path;
            }
        }
        return false;
        
    }
    
    public function send_welcome_message($user_info, $verify = true, $verification_code = null)
    {
        
        $welcome_message = "Hi " . $user_info->last_name . ", You've successfully integrated your Telegram Account ...";
        
        if ($verify) {
            $welcome_message .= " Verification Code  " . $verification_code;
        }
        
        return $this->send_message_to_user($user_info->id, $welcome_message, $this->admin_service_port);
        
    }
    
    public function incoming_message($data_obj)
    {
        
        if (isset($data_obj->to) && isset($data_obj->from)) {
            
            $to   = $data_obj->to;
            $from = $data_obj->from;
            $file = false;
            
            $message = '';
            if (isset($data_obj->text))
                $message = $data_obj->text;
            
            $chat_id = $to->id;
            
            $this->traceLog($data_obj);
            /*
            Here you can write the functions to handle messages from Telegram and 
            send back to client browser using socket.io/ajax
            */
            
        }
        
    }
    
    public function get_chat_id($chat_subject)
    {
        try {
            
            $chat_subject = str_replace(' ', '_', $chat_subject);
            $chat_subject = str_replace('#', '@', $chat_subject);
            
            $output = $this->run_command('chat_info ' . $chat_subject, $this->admin_service_port);
            
            if ($output && isset($output->type) && $output->type == 'chat') {
                return $output->id;
            }
            
        }
        catch (Exception $e) {
            $this->traceLog('get_chat_id error ' . $e->getMessage());
        }
    }
    
    public function safe_feof($fp, &$start = NULL)
    {
        $start = microtime(true);
        return feof($fp);
    }
    
    public function run_command($cmd, $port)
    {
        try {
            
            $fp     = fsockopen($this->host, $port, $errno, $errstr, 10);
            $output = '';
            
            $timeout = 30;
            $start   = NULL;
            
            if ($fp) {
                fwrite($fp, $cmd . "\r\n");
                while (!$this->safe_feof($fp, $start) && (microtime(true) - $start) < $timeout) {
                    $output = fread($fp, 2048);
                    break;
                }
                
                fclose($fp);
                return $this->sanitize_output($output);
                
            } else {
                $this->traceLog('Telegram fsockopen error');
                
            }
        }
        catch (Exception $e) {
            $this->traceLog('run_command ' . $e->getMessage());
        }
    }
    
    public function add_contact($phone, $first_name, $last_name = '')
    {
        
        try {
            
            $output = $this->run_command("add_contact \"$phone\" \"$first_name\" \"$last_name\"", $this->admin_service_port);
            return $output;
            
        }
        catch (Exception $e) {
            $this->traceLog('add_contact ' . $e->getMessage());
        }
    }
    
    public function send_photo_to_group($chat_id, $file_path, $port)
    {
        
        try {
            $file_path = PATH_APP . $file_path;
            if (file_exists($file_path)) {
                $output = $this->run_command("send_photo chat#" . $chat_id . " \"$file_path\"", $port);
                return $output;
            }
            
        }
        catch (Exception $e) {
            $this->traceLog('send_photo_to_group ' . $e->getMessage());
        }
    }
    
    public function send_photo($user_id, $file_path, $port)
    {
        
        try {
            $file_path = PATH_APP . $file_path;
            if (file_exists($file_path)) {
                
                $output = $this->run_command("send_photo user#" . $user_id . " \"$file_path\"", $port);
                return $output;
            }
            
        }
        catch (Exception $e) {
            $this->traceLog('send_photo ' . $e->getMessage());
        }
    }
    
    public function sanitize_output($output)
    {
        $output = explode("\n", $output);
        if (stripos($output[0], "ANSWER") !== false)
            unset($output[0]);
        $output = implode("\n", $output);
        $output = trim($output);
        $output = json_decode($output);
        return $output;
    }
    
}
?>	    