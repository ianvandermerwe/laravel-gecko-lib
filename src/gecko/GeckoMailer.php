<?php
/**
 * Created by PhpStorm.
 * User: vdmerwe-i
 * Date: 2014/07/29
 * Time: 3:54 PM
 */

class GeckoMailer{

    public function ProcessEmail($mailItem){

        $ret = $this->_process_EmailSend($mailItem);

        if($ret == true){
            $mailItem->sent_flag = 1;
            $mailItem->save();
        }
        return $ret;
    }

    public function SendEmail($to, $cc = "", $subject, $message, $priority = 5, $data = null, $template = "", $sendattachment = false, $file1 = "", $file2 = "", $file3 = "") {

        $this->SendFullEmail($to, $cc, $subject, $message, $priority, $data, $template, $sendattachment, $file1, $file2, $file3);
    }

    private function SendFullEmail($to, $cc, $subject, $message, $priority, $data, $template, $sendattachment = false, $file1, $file2, $file3) {
        try
        {
            try
            {
                $mailItem = new EmailItem();
                $mailItem->to = $to;
                $mailItem->cc = $cc;
                $mailItem->subject = $subject;
                $mailItem->message = $message;

                $mailItem->priority = $priority;

                if($template != ''){
                    $mailItem->email_template = $template;
                }

                $mailItem->data = json_encode($data);
                $mailItem->sent_flag = 0;

                $mailItem->send_attachment = $sendattachment;
                if($sendattachment == true){
                    $mailItem->file1 = $file1;
                    $mailItem->file2 = $file2;
                    $mailItem->file3 = $file3;
                }

                $mailItem->save();
            }
            catch(\Whoops\Example\Exception $ex)
            {
                TextDebugging::LogError($ex,__CLASS__,__FILE__,__LINE__);
                return false;
            }

            if (Config::get('mail.use_queue')){
                return true;
            } else {
                $ret = $this->ProcessEmail($mailItem);
            }

            return (bool) $ret;
        }
        catch (Exception $ex)
        {
            TextDebugging::LogError($ex->getMessage(),__CLASS__,__FILE__,__LINE__);
            return false;
        }
    }

    private function  _process_EmailSend($mailItem){
        $mail = new PHPMailer(true);

        $mail->CharSet = "utf-8";

        if (Config::get('mail.driver') == 'smtp') {
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = Config::get('mail.host');
        $mail->Username = Config::get('mail.username');
        $mail->Password = Config::get('mail.password');
        } else {
            $mail->IsMail();
        }


        $mail->IsHTML(true);
        $mail->From = Config::get('mail.from');
        $mail->FromName = Config::get('mail.fromName');

        if(count($mailItem->to) > 1){
            foreach($mailItem->to as $emailAddress){
                $mail->AddAddress($emailAddress);
            }
        }else{
            $mail->AddAddress($mailItem->to);
        }

        if(count($mailItem->cc) > 1){
            foreach($mailItem->cc as $emailAddress){
                $mail->AddCC($emailAddress);
            }
        }else{
            $mail->AddCC($mailItem->to);
        }

        $mail->Subject = $mailItem->subject;

        if ($mailItem->send_attachment == true) {
            if (!empty($mailItem->file1)) {
                $ret = $mail->AddAttachment(Root() . $mailItem->file1, $mailItem->file1);
            }
            if (!empty($mailItem->file2)) {
                $ret = $mail->AddAttachment(Root() . $mailItem->file2, $mailItem->file2);
            }
            if (!empty($mailItem->file3)) {
                $ret = $mail->AddAttachment(Root() . $mailItem->file3, $mailItem->file3);
            }
            //$filename = "../../../loan_application_form.pdf";
        }

        //MESSAGE ASSIGNMENT
        try{
            if($mailItem->email_template != ''){
                $mail->Body = View::make($mailItem->email_template)
                    ->with('message',$mailItem->message)
                    ->with('data',json_decode($mailItem->data))
                    ->render();
            }else{
                $mail->Body = $mailItem->message;
            }
        }catch (Exception $ex){
            TextDebugging::LogError($ex->getMessage(),__CLASS__,__FILE__,__LINE__);
            return false;
        }

        $mail->Sender = $mailItem->to;

        //$mail->AddReplyTo($fromemail, $fromname); // indicates ReplyTo headers
        return (bool) $mail->Send();
}
}

?>