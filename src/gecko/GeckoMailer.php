<?php
/**
 * Created by PhpStorm.
 * User: vdmerwe-i
 * Date: 2014/07/29
 * Time: 3:54 PM
 */

class GeckoMailer{

    public $rules = [
        'to' => 'required',
        'subject' => 'required',
        'message' => 'required'
    ];

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
    $to -> string || implode(',',$array)
    $cc -> string || implode(',',$array)
    $subject -> string
    $message -> string
    $data -> Object
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

    private function _process_EmailSend($mailItem){

        if($mailItem->email_template == '')
            $mailItem->email_template = 'emails/default';

        if($mailItem->message != '')
            $mailItem->data = json_encode(['message'=> $mailItem->message ]);

        Mail::send($mailItem->email_template, ['data' => json_decode($mailItem->data)], function($message) use ($mailItem)
        {
            //SUBJECT
            $message->subject($mailItem->subject);

            //FROM DETAILS
            if($mailItem->from != ''){
                $message->from($mailItem->from);
            }else{
                $message->from(Config::get('mail.from'));
            }

            //TO DETAILS
            if(count($mailItem->to > 1)){
                $message->to($mailItem->to);
            }else{
                if($mailItem->to != '' && !empty($mailItem->to)){
                    $message->to($mailItem->to);
                }else{
                    throw new Exception("Gecko Mailer Error To Address Cannot be empty");
                    die;
                }
            }

            //CC DETAILS
            $cc = explode(',',$mailItem->cc);

            foreach($cc as $emailAddress){
                if($emailAddress != '' && !empty($emailAddress)){
                    $message->cc($emailAddress);
                }
            }

            //DEFAULT CC DETAILS
            $cc = explode(',',Config::get('mail.email_default_cc'));
            foreach($cc as $emailAddress){
                if(!empty($emailAddress)){
                    $message->cc($emailAddress);
                }
            }
            //BCC DETAILS
            $bcc = explode(',',Config::get('mail.email_default_bcc'));
            foreach($bcc as $emailAddress){
                if(!empty($emailAddress)){
                    $message->bcc($emailAddress);
                }
            }

            //$message->attach($pathToFile);
        });

        $mailItem->sent_flag = 1;
        $mailItem->save();

        return true;
    }
}

?>