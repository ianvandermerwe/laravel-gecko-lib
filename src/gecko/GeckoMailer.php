<?php
/**
 * Created by PhpStorm.
 * User: vdmerwe-i
 * Date: 2014/07/29
 * Time: 3:54 PM
 */

class GeckoMailer{

    /**
     * @var array
     */
    public $rules = [
        'to' => 'required',
        'subject' => 'required',
        'message' => 'required'
    ];

    /**
     * @param $mailId
     * @return bool
     */
    public function ProcessEmail($mailId){

        $mailItem = EmailItem::find($mailId);

        $ret = $this->_process_EmailSend($mailItem);

        if($ret == true){
            $mailItem->sent_flag = 1;
            $mailItem->save();
        }

        return $ret;
    }


    /**
     * @param $to
     * @param $cc
     * @param $subject
     * @param $message
     * @param string $from
     * @param string $fromName
     * @param null $data
     * @param string $template
     * @return bool
     */
    public function SendEmail($to, $cc, $subject, $message, $from = '', $fromName = '', $data = NULL, $template = '') {

        $mailItem = new EmailItem();
        $mailItem->to = $to;
        $mailItem->cc = $cc;
        $mailItem->from = $from;
        $mailItem->from_name = $fromName;
        $mailItem->subject = $subject;
        $mailItem->message = $message;
        $mailItem->sent_flag = 0;

        if($template != ''){
            $mailItem->email_template = $template;
        }

        $mailItem->data = json_encode($data);

        $mailItem->save();

        if (Config::get('mail.use_queue')){

            Queue::push('EmailQueue@queueSendEmail', array('id' => $mailItem->id));

            return true;
        } else {
            $ret = $this->ProcessEmail($mailItem->id);
            return $ret;
        }
    }

    /**
     * @param $mailItem
     * @return bool
     * @throws Exception
     */
    private function _process_EmailSend($mailItem){

        //----------EMAIL SETUP--------------
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
        //------------------------------------

        //EMAIL TEMPLATE CHECK
        if($mailItem->email_template == '')
            $mailItem->email_template = 'emails/default';

        //EMAIL MESSAGE CHECK
        if($mailItem->message != '')
            $mailItem->data = json_encode(['message'=> $mailItem->message ]);

        //SUBJECT
        $mail->Subject = $mailItem->subject;

        //FROM
        if(!empty($mailItem->from) && !empty($mailItem->fromName)){
            $mail->From = $mailItem->from;
            $mail->FromName = $mailItem->fromName;
        }else{
            $mail->From = Config::get('mail.from')[0];
            $mailItem->from = $mail->From;
            $mail->FromName = Config::get('mail.fromName')[1];
            $mailItem->fromName = $mail->FromName;
        }

        //ADDING REPLY TO HEADERS
        //$mail->AddReplyTo($mailItem->from, $mailItem->fromName);

        //ADDING TO
        $to = explode(',',$mailItem->to);

        foreach($to as $emailAddress){
            if(!empty($emailAddress)){
                $mail->AddAddress($emailAddress);
            }
        }

        //ADDING CC
        $cc = explode(',',$mailItem->cc);

        foreach($cc as $emailAddress){
            if(!empty($emailAddress)){
                $mail->AddCC($emailAddress);
            }
        }

        //ADDING CONFIG CC
        if(Config::get('mail.email_default_cc') != '')
            $mail->AddBCC(Config::get('mail.email_default_cc'));

        //EMAIL TEMPLATE RENDER
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
            //throw new Exception("Email Template Blade Render Fails - Possibly undefined prop used");
            throw new Exception($ex);
        }

        /*
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
        }*/

        $mailItem->sent_flag = 1;
        $mailItem->save();

        return (bool) $mail->Send();
    }
}

?>