<?php
return [
    'settings' => [
        'displayErrorDetails'    => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__.'/../templates/',
        ],
        'db' => [
            'host'     => 'localhost',
            'user'     => 'root', //'user'     => 'futuronerd',
            'password' => '', //'password' => 'futuronerd',
            'dbname'   => 'ga2_futuronerd' //'dbname'   => 'futuronerd',
        ],
        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__.'/../logs/slim-app.log',
            'filename' => __DIR__  . '/../../logs/slim-app.log',
            'level'    => \Monolog\Logger::DEBUG

        ],
        'view' => [
            'cache' => false,
            'debug' => true,
            'path'  => __DIR__ . '/../templates',
        ],
        'mailer' => [
            'host' => 'smtp.dreamhost.com', // SMTP Host
            'port' => '587', // SMTP Port
            'username'  => 'emailteste@jamitdigital.com.br', // SMTP Username
            'password'  => 'Mudar121', // SMTP Password
            'protocol'  => 'TLS', // SSL or TLS
        ],
    ],
];

//mysql:host=localhost;dbname=futuronerd;charset=utf8', 'futuronerd', 'futuronerd'