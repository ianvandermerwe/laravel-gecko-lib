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

    public static function force_process_mail_list(){
        $emailQueue = new EmailQueue();

        $processAmountLeft = Config::get('mail.email_queue_batch');

        $processedSuccessfulCount = 0;
        $processedFailedCount = 0;

        //PROCESSING OF EMAIL ITEMS
        $emailItems = EmailItem::where('sent_flag','=','0')
            ->get();

        if(count($emailItems) > 0){
            foreach($emailItems as $emailItem){
                if($processAmountLeft > 0){
                    $ret = $emailQueue->_process_mailItem($emailItem->id);
                    if($ret == true){
                        $processAmountLeft--;
                        $processedSuccessfulCount++;
                    }else{
                        $processedFailedCount++;
                    }
                }
            }
        }

        return [
            'successful_emails' => $processedSuccessfulCount,
            'failed_emails' => $processedFailedCount,
        ];
    }

    public function queueSendEmail($job,$data){

        $mailSent = $this->_process_mailItem($data['id']);
        Log::info('Job Processed ' . json_encode($data) . ' ' . json_encode($job));

        if($mailSent == true){
            //DELETE if complete
            $job->delete();
            return true;
        }

        //CHECK's NUMBER OF ATTEMPTS
        if ($job->attempts() > Config::get('mail.email_queue_job_retries'))
        {
            //$job->delete();
        }

        //RELEASE if unsuccessful
        $job->release();
    }

    private function _process_mailItem($id){
        try
        {
            $mailer = new GeckoMailer();

            $mailer->ProcessEmail($id);
        }
        catch(Exception $ex)
        {
            Log::error($ex . ' ' . __CLASS__ . ' ' . __FILE__ . ' ' . __LINE__);
            return false;
        }
        return true;
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
