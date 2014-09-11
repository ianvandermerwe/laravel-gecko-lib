<?php
/**
 * Created by PhpStorm.
 * User: vdmerwe-i
 * Date: 2014/07/28
 * Time: 4:07 PM
 */

class TextDebugging {
    private function _init_logger(){

        if(file_exists (base_path() . "/logs") == false){
            mkdir(base_path() . "/logs",0777);
        }

        if(file_exists (base_path() . "/logs/ErrorLog.txt") == false){
            $content = '';
            $fp = fopen(base_path() . "/logs/ErrorLog.txt","wb");
            fwrite($fp,$content);
            fclose($fp);
        }

        if(file_exists(base_path() . "/logs/InfoLog.txt") == false){
            $content = '';
            $fp = fopen(base_path() . "/logs/InfoLog.txt","wb");
            fwrite($fp,$content);
            fclose($fp);
        }
    }

    private function _write_file($log,$file){
        $content = "---- LOG : " . date("Y-m-d H:i:s") . " ----" . "\r\n";
        $content.= $log . "\r\n";
        $content.= "-----------------------------------" . "\r\n" . "\r\n";

        file_put_contents(base_path() . "/logs/" . $file, $content, FILE_APPEND);
    }

    public static function LogInfo($message){
        $logger = new TextDebugging();
        $logger->_init_logger();
        $logger->_write_file($message,"InfoLog.txt");
    }

    public static function LogError($ex,$class,$file,$lineNo){
        $log = 'Error has occurred on ' . $file . "=>" . $class . ":" . $lineNo . "\r\n"
            . 'Error Message -> ' . $ex;
        $position = strpos($log,"Stack trace");
        $log = substr_replace($log, "\r\n\r\n", $position, 0);

        $logger = new TextDebugging();
        $logger->_init_logger();
        $logger->_write_file($log,"ErrorLog.txt");

        $data = array(
            'heading' => 'Gecko System Error Log',
            'log' => $log
        );

        /*Mail::send('emails/error/logEmail', $data, function($message)
        {
            $to = Config::get('app.admin_email_address');
            $message->to($to, 'Administrator')
                    ->subject('Error Email Log');
        });*/

        $mailer = new GeckoMailer();

        $mailer->SendEmail(Config::get('app.admin_email_address'),'','Error Email Log','',1,'','',$data,'emails/error/logEmail');
    }
}