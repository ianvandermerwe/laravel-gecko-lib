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
            $mailItem->sent_flag = '1';
            $mailItem->save();
        }
        return $ret;
    }

    /**
    $to -> string || implode(',',$array)
    $cc -> string || implode(',',$array)
    */
    public function SendEmail($to, $cc = "", $subject, $message, $priority = 5, $from = '', $fromName = '', $data = null, $template = "", $sendattachment = false, $file1 = "", $file2 = "", $file3 = "") {

        $this->SendFullEmail($to, $cc, $subject, $message, $priority, $from, $fromName, $data, $template, $sendattachment, $file1, $file2, $file3);
    }

    private function SendFullEmail($to, $cc, $subject, $message, $priority, $from, $fromName, $data, $template, $sendattachment = false, $file1, $file2, $file3) {

        $mailItem = new EmailItem();
        $mailItem->to = $to;
        $mailItem->cc = $cc;
        $mailItem->from = $from;
        $mailItem->fromName = $fromName;
        $mailItem->subject = $subject;
        $mailItem->message = $message;
        $mailItem->priority = $priority;
        $mailItem->sent_flag = 0;

        if($template != ''){
            $mailItem->email_template = $template;
        }

        $mailItem->data = json_encode($data);


        $mailItem->send_attachment = $sendattachment;
        if($sendattachment == true){
            $mailItem->file1 = $file1;
            $mailItem->file2 = $file2;
            $mailItem->file3 = $file3;
        }

        $mailItem->save();

        if (Config::get('mail.use_queue')){
            return true;
        } else {
            $ret = $this->ProcessEmail($mailItem);
        }

        return (bool) $ret;
    }

    private function _process_EmailSend($mailItem){
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

        //$mail->Sender = $mailItem->to; ??? possibly from

        $mail->IsHTML(true);

        if($mailItem->from != ''){
            $mail->From = $mailItem->from;
        }else{
            $mail->From = Config::get('mail.from');
        }

        if($mailItem->from != ''){
            $mail->FromName = $mailItem->fromName;
        }else{
            $mail->FromName = Config::get('mail.fromName');
        }

        if(count($mailItem->to > 1)){

            $mailItem->to = explode(',',$mailItem->to);

            foreach($mailItem->to as $emailAddress){
                $mail->AddAddress($emailAddress);
            }

            $mailItem->to = implode(',',$mailItem->to);
        }else{
            $mail->AddAddress($mailItem->to);
        }

        if(count($mailItem->cc) > 1){
            foreach($mailItem->cc as $emailAddress){
                $mail->AddCC($emailAddress);
            }
        }else{
            $mail->AddCC($mailItem->cc);
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
            throw new \Whoops\Example\Exception("Email Template Blade Render Fails - Possibly undefended prop used");
        }

        //$mail->AddReplyTo($fromemail, $fromname); // indicates ReplyTo headers
        return (bool) $mail->Send();
    }
}

?>