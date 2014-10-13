<?php

class SendEmail {
    public function fire($job, $data){

        Log::info($data);

        $job->delete();
    }

} 