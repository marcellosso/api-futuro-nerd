<?php
// DIC configuration
$container = $app->getContainer();

// view renderer
// $container['renderer'] = function ($c) {
//     $settings = $c->get('settings')['renderer'];
//     return new Slim\Views\PhpRenderer($settings['template_path']);
// };

$container['view'] = function ($c) {
    $view_config = $c->get('settings')['view'];
    $view = new \Slim\Views\PhpRenderer($view_config['path']);
    return $view;
};

$container['view_twig'] = function ($container) {
    $view_config = $container->get('settings')['view'];
    $view = new \Slim\Views\Twig($view_config['path']);
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new \Slim\Views\TwigExtension($container['router'], $basePath));
    
    return $view;
};

// PDO Mysql 
$container['pdo'] = function ($c) {
    $pdo_config = $c->get('settings')['db'];
    $dsn = "mysql:dbname=" . $pdo_config['dbname'] . ";host=" . $pdo_config['host'];
    $pdo = new PDO($dsn, $pdo_config['user'], $pdo_config['password'], [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
    ]);
    return $pdo;
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], Monolog\Logger::DEBUG));
    return $logger;
};

//andrewdyer/slim3-mailer
$container['mailer'] = function($container) {
    $twig = $container['view_twig'];
    $view_config = $container->get('settings')['mailer'];
    $mailer = new \Anddye\Mailer\Mailer($twig, [
        'host'      => $view_config['host'],  // SMTP Host
        'port'      => $view_config['port'],  // SMTP Port
        'username'  => $view_config['username'],  // SMTP Username
        'password'  => $view_config['password'],  // SMTP Password
        'protocol'  => $view_config['protocol']   // SSL or TLS
    ]);
        
    // Set the details of the default sender
    $mailer->setDefaultFrom('emailteste@jamitdigital.com.br', 'Futuro Nerd');
    
    return $mailer;
};