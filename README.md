##Laravel Gecko Lib
=================

<img src="http://geckoweb.co.za/assets/images/Gecko.png"/>

- Custom Email Queue

- Mailer with Email queue hookup

- TextLogging and Email error

- http://www.geckoweb.co.za

#Installation

Require this package in your `composer.json` and update composer. This will download the package.

```php
"geckoweb/gecko": "dev-master"
```

The normal mail config add the following lines on top.

```php
/*
|--------------------------------------------------------------------------
| Gecko Mailer Vars
|--------------------------------------------------------------------------
| Use the Email Queue
*/

'use_queue' => true,

'email_queue_important_batch' => 100,

'email_queue_normal_batch' => 300,

'email_default_cc' => 'admin@yourdomain.com',
```

For the text error logging you will need to add to your view : email/error/logEmail.blade.php

```html
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
</head>
<body>

<h2>{{$heading}}</h2>

<div>
    {{$log}}
</div>
</body>
</html>
```

# License

This package is licensed under LGPL. You are free to use it in personal and commercial projects. The code can be forked and modified, but the original copyright author should always be included!
