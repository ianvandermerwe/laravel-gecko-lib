#Gecko Setup with Laravel

Add to config.mail file ->
    'use_queue' => true,

Add to config.app aliases ->
    'EmailItem' => 'components/gecko/EmailQueue'