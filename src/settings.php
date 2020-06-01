<?php
//require_once('../../admin/config.php');

define('URL_API_FUTURONERD',    'https://api-futuronerd.herokuapp.com/');
define('URL_ADMIN_FUTURONERD',  'http://localhost:8081/');
define('URL_CONSULTA_CEP',      'https://viacep.com.br/ws/');

return [
    'settings' => [
        'displayErrorDetails'    => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__.'/../templates/',
        ],
//        'db' => [
//            'host'     => 'localhost',
//            'user'     => 'futuronerd', //'user'     => 'futuronerd',
//            'password' => 'futuronerd', //'password' => 'futuronerd',
//            'dbname'   => 'futuronerd' //'dbname'   => 'futuronerd',
//        ],
//        'db' => [
//            'host'     => 'localhost',
//            'user'     => 'root', //'user'     => 'futuronerd',
//            'password' => 'root123', //'password' => 'futuronerd',
//            'dbname'   => 'futuro_nerd_local' //'dbname'   => 'futuronerd',
//        ],

        'db' => [
            'host'     => 'ffn96u87j5ogvehy.cbetxkdyhwsb.us-east-1.rds.amazonaws.com',
            'user'     => 'uvmrvwcmw19kt2su',
            'password' => 'zawuvgqtjcs5t9ff',
            'dbname'   => 'p040hst38t6dnx4w'
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
        ] //,
        // "determineRouteBeforeAppMiddleware" => true
    ],
];

//mysql:host=localhost;dbname=futuronerd;charset=utf8', 'futuronerd', 'futuronerd'
