<?php
/**
 * Created by PhpStorm.
 * User: vdmerwe-i
 * Date: 2014/07/28
 * Time: 3:56 PM
 */

class EmailQueue extends Eloquent{
        /**
     * ----------------
     * Will have to run on a CRON Job off the server to check route.
     * ----------------
     */

    public static function proccess_mail_list(){
        $emailQueue = new EmailQueue();

        $processAmountImportantLeft = Config::get('mail.email_queue_important_batch');
        $processAmountLeft = Config::get('mail.email_queue_normal_batch');

        $processedSuccessfulCount = 0;
        $processedFailedCount = 0;


        //PROCESSING OF IMPORTANT PRIORITY EMAIL ITEMS
        $important_emailItems = EmailItem::where('priority','=','1')
            ->where('sent_flag','=','0')
            ->get();
        if(count($important_emailItems) > 0){
            foreach($important_emailItems as $emailItem){
                if($processAmountImportantLeft > 0){
                    $ret = $emailQueue->_proccess_mailItem($emailItem->id);
                    if($ret == true){
                        $processAmountImportantLeft--;
                        $processedSuccessfulCount++;
                    }else{
                        $processedFailedCount++;
                    }
                }
            }
        }

        //PROCESSING OF NORMAL PRIORITY EMAIL ITEMS
        $normal_emailItems = EmailItem::where('priority','>','1')
            ->where('sent_flag','=','0')
            ->get();
        if(count($normal_emailItems) > 0){
            foreach($normal_emailItems as $emailItem){
                if($processAmountLeft > 0){
                    $ret = $emailQueue->_proccess_mailItem($emailItem->id);
                    if($ret == true){
                        $processAmountLeft--;
                        $processedSuccessfulCount++;
                    }else{
                        $processedFailedCount++;
                    }
                }
            }
        }

        echo "Amount of emails has been sent out Successfully - " . $processedSuccessfulCount . "<br />";
        echo "Amount of emails that has failed - " . $processedFailedCount . "<br />";
    }

    private function _proccess_mailItem($id){
        try
        {
            $emailItem = EmailItem::find($id);

            $mailer = new GeckoMailer();

            $ret = $mailer->ProcessEmail($emailItem);

            if($ret == true){
                $emailItem->sent_flag = 1;
                $emailItem->save();
            }
        }
        catch(Exception $ex)
        {
            TextDebugging::LogError($ex,__CLASS__,__FILE__,__LINE__);
            return false;
        }
        return $emailItem;
    }
}

class EmailItem extends Eloquent{
    protected $table = 'email_queue';

    //RULES
    public $rules = array(
        'to' => 'required',
        'subject' => 'required',
        'message' => 'required'
    );
}
