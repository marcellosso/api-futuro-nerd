<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \FlyingLuscas\Correios\Client as Client;
use \FlyingLuscas\Correios\Service as Service;

$app->get('/', function (Request $request, Response $response) use ($app) {
    $response->getBody()->write(json_encode('teste de utilização'));
    //return $this->view->render($response, 'teste.php', []);
});
// ##### ROTAS DO MODULO DE FRETE #####
$app->get('/cep/{cep}', function (Request $request, Response $response, array $args) {
    $correios = new Client;
    $ret = $correios->zipcode()->find($args['cep']);
    $response->getBody()->write(json_encode($ret));
});

// $app->get('/frete', function (Request $request, Response $response) {
//     $correios = new Client;
//     $ret = $correios->freight()
//     ->origin('01001-000')
//     ->destination('87047-230')
//     ->services(Service::SEDEX, Service::PAC)
//     ->item(16, 16, 16, .3, 1) // largura, altura, comprimento, peso e quantidade
//     ->item(16, 16, 16, .3, 3) // largura, altura, comprimento, peso e quantidade
//     ->item(16, 16, 16, .3, 2) // largura, altura, comprimento, peso e quantidade
//     ->calculate();
//     $this->logger->addInfo("Route: {GET} /frete " . json_encode($ret));
//     $response->getBody()->write(json_encode($ret));
// });


$app->post('/frete', function (Request $request, Response $response) {
    $body = $request->getParsedBody();
    $correios = new Client;
    $retPAC = $correios->freight()
        ->origin($body['cep_origem'])
        ->destination($body['cep_destino'])
        ->services(Service::PAC) // , Service::PAC
        ->item($body['largura'], $body['altura'], $body['comprimento'], $body['peso'], $body['quantidade'])
        ->calculate();

    $retSEDEX = $correios->freight()
        ->origin($body['cep_origem'])
        ->destination($body['cep_destino'])
        ->services(Service::SEDEX) // , Service::PAC
        ->item($body['largura'], $body['altura'], $body['comprimento'], $body['peso'], $body['quantidade'])
        ->calculate();

    $ret = [$retPAC[0], $retSEDEX[0]];

    $this->logger->addInfo("Route: {POST} /frete " . json_encode($ret));
    $response->getBody()->write(json_encode($ret));
});

// ##### ROTAS DO MODULO DE ENVIO DE EMAIL #####

// $app->get('/sendmail/', function (Request $request, Response $response) {
//     $this->logger->addInfo("Route: {GET} /sendmail/ | Description: sendmail");
//     $obj = new \Controllers\Ajuda($this->view, $this->pdo, $this->logger);
//     return $this->view->render($response, 'default.php', (array) $obj->listAll());
// });

$app->get('/sendmailsample', function (Request $request, Response $response) {
    $user = new stdClass;

    /* -------------- */
    $user = new stdClass;
    //$user->name = 'Ricardo Mangabeira';
    //$user->email = 'rasmangabeira@gmail.com';
    $user->name = 'Marcos Netto';
    $user->email = 'marcoscnettoa@gmail.com';
    /* -------------- */

    // Utilizando classe para devinir o e-mail a ser enviado
    $ret = $this->mailer->setTo($user->email, $user->name)->sendMessage(new \Controllers\AppMail($user));

    // enviando e-mail informando direto o template
    // $ret = $this->mailer->sendMessage('emails/welcome.html.twig', ['user' => $user], function($message) use($user) {
    //     $message->setTo($user->email, $user->name);
    //     $message->setSubject('Welcome to the Team!');
    // });
    $this->logger->addInfo("Route: {GET} /SENDMAIL" . json_encode($ret));
    $response->getBody()->write('Mail sent!');

    return $response;
});

$app->get('/relatorioDiarioFilho', function (Request $request, Response $response) {
    global $container;

    /* -------------- */
    $user = new stdClass;
    //$user->name = 'Ricardo Mangabeira';
    //$user->email = 'rasmangabeira@gmail.com';
    $user->name = 'Marcos Netto';
    $user->email = 'marcoscnettoa@gmail.com';
    /* -------------- */

    $obj = new \Controllers\Relatorio($this->view, $this->pdo, $this->logger);
    $dados = $obj->get_Pai_EstatisticaFilhos_Atual();
    //print_r($dados); exit;
    if (!empty($dados)) {
        foreach ($dados as $D) {

            $ret = $this->mailer->sendMessage('emails/relatorioDiarioFilho.twig', ['dados' => $D, 'URL_ADMIN_FUTURONERD' => URL_ADMIN_FUTURONERD], function ($message) use ($user, $D, $container) {

                $message->setTo($D['pai_email'], $D['pai_nome']);
                $message->setSubject('Relatório Estatísticas');

            });
            $this->logger->addInfo("Route: {GET} /SENDMAIL" . json_encode($ret));

        }
    }

    echo 'Enviado!!!';

    return $response;
});


$app->get('/recuperarsenha/pai/{login}', function (Request $request, Response $response, array $args) {
    global $container;

    /* -------------- */
    $user = new stdClass;
    //$user->name = 'Ricardo Mangabeira';
    //$user->email = 'rasmangabeira@gmail.com';
    $user->name = 'Marcos Netto';
    $user->email = 'marcoscnettoa@gmail.com';
    /* -------------- */

    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    $dados = $obj->getPaiByLogin($args['login']);
    $dados = $dados['data'];
    if (!empty($dados)) {
        $ret = $this->mailer->sendMessage('emails/recuperarsenha.html.twig', ['user' => $dados, 'URL_ADMIN_FUTURONERD' => URL_ADMIN_FUTURONERD], function ($message) use ($user, $dados, $container) {
            $message->setTo($dados['email'], $dados['nome']);
            //$message->setTo('rasmangabeira@gmail.com', 'Ricardo Mangabeira');
            //$message->setTo('marcoscnettoa@gmail.com', 'Marcos Netto');
            $message->setSubject('Recuperar Senha - Pai');
        });
        $this->logger->addInfo("Route: {GET} /SENDMAIL" . json_encode($ret));
        return $this->view->render($response, 'default.php', [true]);
    }
    return $this->view->render($response, 'default.php', [false]);
});


$app->get('/recuperarsenha/filho/{login}', function (Request $request, Response $response, array $args) {
    global $container;

    /* -------------- */
    $user = new stdClass;
    //$user->name = 'Ricardo Mangabeira';
    //$user->email = 'rasmangabeira@gmail.com';
    $user->name = 'Marcos Netto';
    $user->email = 'marcoscnettoa@gmail.com';
    /* -------------- */

    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    $dados = $obj->getFilhoByLogin($args['login']);
    $dados = $dados['data'];
    //die(var_dump($dados));
    if (!empty($dados)) {
        $ret = $this->mailer->sendMessage('emails/recuperarsenha.html.twig', ['user' => $dados, 'URL_ADMIN_FUTURONERD' => URL_ADMIN_FUTURONERD], function ($message) use ($user, $dados, $container) {
            $message->setTo($dados['email'], $dados['nome']);
            //$message->setTo('rasmangabeira@gmail.com', 'Ricardo Mangabeira');
            //$message->setTo('marcoscnettoa@gmail.com', 'Marcos Netto');
            $message->setSubject('Recuperar Senha - Filho');
        });
        $this->logger->addInfo("Route: {GET} /SENDMAIL" . json_encode($ret));
        return $this->view->render($response, 'default.php', [true]);
    }
    return $this->view->render($response, 'default.php', [false]);
});


// ##### ROTAS DO MODULO DE AJUDA #####

$app->get('/ajuda', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /ajuda | Description: List All");
    $obj = new \Controllers\Ajuda($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listAll());
});

$app->get('/ajuda/datatable', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /ajuda/datatable | Description: List All Datatable");
    $obj = new \Controllers\Ajuda($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default-tabela.php', (array)$obj->listAll());
});

$app->get('/ajuda/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /ajuda/:id | Description: Return By ID");
    $obj = new \Controllers\Ajuda($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getById($args['id']));
});

$app->post('/ajuda', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /ajuda | Description: Insert");
    $obj = new \Controllers\Ajuda($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->insert($request->getParsedBody()));
});

$app->put('/ajuda/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {PUT} /ajuda | Description: Update");
    $obj = new \Controllers\Ajuda($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->update($args['id'], $request->getParsedBody()));
});

$app->delete('/ajuda/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {DELETE} /ajuda | Description: Delete");
    $obj = new \Controllers\Ajuda($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->delete($args['id']));
});

// ##### ROTAS DO MODULO DE CATEGORIAPROD #####

$app->get('/categoria-prod', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /categoria-prod | Description: List All");
    $obj = new \Controllers\CategoriaProd($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listAll());
});

$app->get('/categoria-prod/datatable', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /categoria-prod/datatable | Description: List All Datatable");
    $obj = new \Controllers\CategoriaProd($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default-tabela.php', (array)$obj->listAll());
});

$app->get('/categoria-prod/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /categoria-prod/:id | Description: Return By ID");
    $obj = new \Controllers\CategoriaProd($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getById($args['id']));
});

$app->post('/categoria-prod', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /categoria-prod | Description: Insert");
    $obj = new \Controllers\CategoriaProd($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->insert($request->getParsedBody()));
});

$app->put('/categoria-prod/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {PUT} /categoria-prod | Description: Update");
    $obj = new \Controllers\CategoriaProd($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->update($args['id'], $request->getParsedBody()));
});

$app->delete('/categoria-prod/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {DELETE} /categoria-prod | Description: Delete");
    $obj = new \Controllers\CategoriaProd($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->delete($args['id']));
});

// ##### ROTAS DO MODULO DE CLIENTE #####

$app->get('/cliente', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /cliente | Description: List All");
    $obj = new \Controllers\Cliente($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listAll());
});

$app->get('/cliente/datatable', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /cliente/datatable | Description: List All Datatable");
    $obj = new \Controllers\Cliente($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default-tabela.php', (array)$obj->listAll());
});

$app->get('/cliente/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /cliente/:id | Description: Return By ID");
    $obj = new \Controllers\Cliente($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getById($args['id']));
});

$app->post('/cliente', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /cliente | Description: Insert");
    $obj = new \Controllers\Cliente($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->insert($request->getParsedBody()));
});

$app->put('/cliente/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {PUT} /cliente | Description: Update");
    $obj = new \Controllers\Cliente($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->update($args['id'], $request->getParsedBody()));
});

$app->delete('/cliente/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {DELETE} /cliente | Description: Delete");
    $obj = new \Controllers\Cliente($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->delete($args['id']));
});

// ##### ROTAS DO MODULO DE JOGO #####

$app->post('/cadastra/jogada', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /cadastra/jogada | Description: Insert");
    $obj = new \Controllers\Jogo($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->cadastra($request->getParsedBody()));
});

$app->put('/cadastra/pts/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {PUT} /cadastra/pts/:filho | Description: Update");
    $obj = new \Controllers\Jogo($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->cadastraPTS($args['id'], $request->getParsedBody()));
});

// ##### ROTAS DO MODULO DE LOJA #####

$app->get('/loja/produto-verifica/{filho}/{produto}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /loja/produto-verifica/{filho}/{produto} | Description: Return By ID");
    $obj = new \Controllers\Loja($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->verifica($args['filho'], $args['produto']));
});

$app->post('/loja/produto', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /loja/produto | Description: Insert");
    $obj = new \Controllers\Loja($this->view, $this->pdo, $this->logger);
    $body = $request->getParsedBody();
    $id_filho = $body['id_filho'];
    $id_produto = $body['id_produto'];
    $obj->updatePontosAluno($id_filho, $id_produto);
    return $this->view->render($response, 'default.php', (array)$obj->solicitar($request->getParsedBody()));
});

// ##### ROTAS DO MODULO DE MATERIAS #####

$app->get('/materia', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /materia | Description: List All");
    $obj = new \Controllers\Materia($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listAll());
});

$app->get('/materia_by_serie/{id_serie}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /materia_by_serie ". $args['id_serie'] ."| Description: List listBySerieId");
    $obj = new \Controllers\Materia($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listBySerieId($args['id_serie']));
});

$app->get('/materia/datatable', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /materia/datatable | Description: List All Datatable");
    $obj = new \Controllers\Materia($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default-tabela.php', (array)$obj->listAll());
});

$app->get('/materia/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /materia/:id | Description: Return By ID");
    $obj = new \Controllers\Materia($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getById($args['id']));
});

$app->post('/materia', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /materia | Description: Insert");
    $obj = new \Controllers\Materia($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->insert($request->getParsedBody()));
});

$app->put('/materia/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {PUT} /materia | Description: Update");
    $obj = new \Controllers\Materia($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->update($args['id'], $request->getParsedBody()));
});

$app->delete('/materia/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {DELETE} /materia | Description: Delete");
    $obj = new \Controllers\Materia($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->delete($args['id']));
});

// ##### ROTAS DO MODULO DE PAIS #####

$app->get('/pais/todos', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /pais/todos | Description: List All");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listAll());
});

$app->get('/pais/todos/datatable', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /pais/todos/datatable | Description: List All Datatable");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default-tabela.php', (array)$obj->listAll());
});

$app->get('/pais/filhos/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /pais/filhos/{id} | Description: Return By ID");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listaFilhos($args['id']));
});

$app->get('/filhos/getPts/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /pais/filhos/{id} | Description: Return By ID");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getPtsFilho($args['id']));
});

$app->get('/filho/questoes/{materia}/{serie}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /filho/questoes/{materia}/{serie} | Description: Return By ID");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->totalQuestoes($args['materia'], $args['serie']));
});

$app->get('/filho/questoes/respondidas/{filho}/{materia}/{serie}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /filho/questoes/respondidas/{filho}/{materia}/{serie} | Description: Return By ID");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->questoesRespondidas($args['filho'], $args['materia'], $args['serie']));
});

$app->get('/filho/questoes/erradas/{filho}/{materia}/{serie}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /filho/questoes/erradas/{filho}/{materia}/{serie} | Description: Return By ID");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->questoesErradas($args['filho'], $args['materia'], $args['serie']));
});

$app->get('/filho/questoes/erradas/pergrespsel/{filho}/{materia}/{serie}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /filho/questoes/erradas/pergrespsel/{filho}/{materia}/{serie} | Description: Return By ID");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->questoesErradasPergRespSel($args['filho'], $args['materia'], $args['serie']));
});

$app->get('/filho/questoes/relatorio/completadas/{id_serie}/{id_filho}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /filho/questoes/relatorio/completadas/{" . $args['id_serie'] . "}/{" . $args['id_filho'] . "} | Description: Return By ID");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->relatorioQuestoesCompletadas($args['id_serie'], $args['id_filho']));
});

$app->post('/pais', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /pais | Description: Insert");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->cadastra($request->getParsedBody()));
});

$app->post('/pais/consulta', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /pais/consulta | Description: Insert");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listaPai($request->getParsedBody()));
});

$app->post('/pais/login', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /pais/login | Description: Insert");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->login($request->getParsedBody()));
});

$app->post('/filho', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /filho | Description: Modifica o plano");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listaFilho($request->getParsedBody()));
});

$app->post('/filho/login', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /filho/login | Description: Login Filho");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->loginFilho($request->getParsedBody()));
});

$app->post('/pais/modifica/plano', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /pais/modifica/plano | Description: Modifica o plano");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->modificaPlano($request->getParsedBody()));
});

$app->post('/pais/cadastra/filho', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /pais/cadastra/filho | Description: Modifica o plano");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->cadastraFilho($request->getParsedBody()));
});

$app->put('/filho/modifica/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {PUT} /filho/modifica/{id} | Description: Update");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->modificaFilho($args['id'], $request->getParsedBody()));
});

$app->put('/filho/tempoativo/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {PUT} /filho/tempoativo/{id} | Description: Update");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->tempoAtivoFilho($args['id'])); //, $request->getParsedBody()
});

// # MXTera --
$app->get('/filho/tempoativototal/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /filho/tempoativototal/{id} | Description: Tempo Total de Uso");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->tempoAtivoFilhoTotal($args['id']));
});
// -- #


$app->put('/pais/modifica/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {PUT} /pais/modifica/{id} | Description: Update");
    $obj = new \Controllers\Pais($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->modifica($args['id'], $request->getParsedBody()));
});

// ##### ROTAS DO MODULO DE PLANOS #####

$app->get('/plano', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /plano | Description: List All");
    $obj = new \Controllers\Plano($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listAll());
});

$app->get('/plano/datatable', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /plano/datatable | Description: List All Datatable");
    $obj = new \Controllers\Plano($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default-tabela.php', (array)$obj->listAll());
});

$app->get('/plano/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /plano/:id | Description: Return By ID");
    $obj = new \Controllers\Plano($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getById($args['id']));
});

$app->post('/plano', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /plano | Description: Insert");
    $obj = new \Controllers\Plano($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->insert($request->getParsedBody()));
});

$app->put('/plano/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {PUT} /plano | Description: Update");
    $obj = new \Controllers\Plano($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->update($args['id'], $request->getParsedBody()));
});

$app->delete('/plano/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {DELETE} /plano | Description: Delete");
    $obj = new \Controllers\Plano($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->delete($args['id']));
});

// ##### ROTAS DO MODULO DE PRODUTOS #####

$app->get('/produtos', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /produtos | Description: List All");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listAll());
});

$app->get('/produtos/solicitados/{id_filho}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /produtos/solicitados/{$args['id_filho']} | Description: List All");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getProdutosSolicitados($args['id_filho']));
});

$app->get('/produtos/datatable', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /produtos | Description: List All");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default-tabela.php', (array)$obj->listAll());
});

$app->get('/produtos/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /produtos | Description: List All");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getById($args['id']));
});

$app->post('/produtos', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /produtos | Description: List All");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->insert($request->getParsedBody()));
});

$app->put('/produtos/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /produtos | Description: List All");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->update($args['id'], $request->getParsedBody()));
});

$app->delete('/produtos/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /produtos | Description: List All");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->delete($args['id']));
});

// ##### ROTAS DO MODULO DE PEDIDOS #####

$app->get('/pedido/datatable', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /plano/datatable | Description: List All Datatable");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default-tabela.php', (array)$obj->getProdutosSolicitadosPorPai());
});

$app->get('/pedido/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /produtos | Description: List All");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getPedidoById($args['id']));
});

$app->put('/pedido/status/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /produtos | Description: List All");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->updateStatus($args['id'], $request->getParsedBody()));
});

$app->delete('/pedido/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /produtos | Description: List All");
    $obj = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->deletePedido($args['id']));
});

// ##### ROTAS DO MODULO DE QUESTOES #####

$app->get('/questoes', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /questoes | Description: List All");
    $obj = new \Controllers\Questoes($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listAll());
});

$app->get('/questoes/datatable', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /questoes/datatable | Description: List All Datatable");
    $obj = new \Controllers\Questoes($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default-tabela.php', (array)$obj->listAll());
});

$app->get('/questoes/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /questoes/:id | Description: Return By ID");
    $obj = new \Controllers\Questoes($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getById($args['id']));
});

$app->delete('/questoes/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {DELETE} /questoes/{id} | Description: Delete");
    $obj = new \Controllers\Questoes($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->delete($args['id']));
});

$app->get('/questao/{filho}/{materia}/{serie}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /questao/{filho}/{materia}/{serie} | Description: Return By ID");
    $obj = new \Controllers\Questoes($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->qr($args['filho'], $args['materia'], $args['serie']));
});

$app->get('/questao-errada/{filho}/{materia}/{serie}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /questao/{filho}/{materia}/{serie} | Description: Return By ID");
    $obj = new \Controllers\Questoes($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->qre($args['filho'], $args['materia'], $args['serie']));
});

// ##### ROTAS DO MODULO DE SERIES #####

$app->get('/serie', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /serie | Description: List All");
    $obj = new \Controllers\Serie($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->listAll());
});

$app->get('/serie/datatable', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /serie/datatable | Description: List All Datatable");
    $obj = new \Controllers\Serie($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default-tabela.php', (array)$obj->listAll());
});

$app->get('/serie/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {GET} /serie/:id | Description: Return By ID");
    $obj = new \Controllers\Serie($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getById($args['id']));
});

$app->post('/serie', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /serie | Description: Insert");
    $obj = new \Controllers\Serie($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->insert($request->getParsedBody()));
});

$app->put('/serie/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {PUT} /serie | Description: Update");
    $obj = new \Controllers\Serie($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->update($args['id'], $request->getParsedBody()));
});

$app->delete('/serie/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("Route: {DELETE} /serie | Description: Delete");
    $obj = new \Controllers\Serie($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->delete($args['id']));
});

// ##### ROTAS DO MODULO DE PAGSEGURO #####

//Route Session ID PagSeguro
// $app->get('/payment/session/', 'PagSeguroController:SessionId');

$app->get('/payment/session', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /serie | Description: List All");
    $obj = new \Controllers\PagSeguroController($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getSessionID());
});

$app->get('/payment/validatecreditcard', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {GET} /serie | Description: List All");
    $obj = new \Controllers\PagSeguroController($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->getSessionID());
});


$app->post('/finalizarcompra', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /finalizarcompra | Description: Insert");
    $obj = new \Controllers\PagamentoProdutoPaiController($this->view, $this->pdo, $this->logger);
    return $this->view->render($response, 'default.php', (array)$obj->finalizarPagamentoPedidoPai($request->getParsedBody()));
});

$app->post('/pagseguro_notification', function (Request $request, Response $response) {
    $this->logger->addInfo("Route: {POST} /pagseguro_notification | Description: Insert");
    $obj = new \Controllers\PagSeguroNotificationController($this->view, $this->pdo, $this->logger);
    $transaction = $obj->notification();

    $body = $request->getParsedBody();
    $id_pagseguro_notification = $obj->insert($body);

    $arr_pagseguro_notification_transaction = [
        "id_pagseguro_notification" => $id_pagseguro_notification,
        "code" => $transaction->getCode(),
        "reference" => $transaction->getReference(),
        "type" => $transaction->getType()->getValue(),
        "status" => $transaction->getStatus()->getValue(),
        "grossAmount" => $transaction->getGrossAmount(),
        "feeAmount" => $transaction->getFeeAmount(),
        "netAmount" => $transaction->getNetAmount()
    ];

    // Grava a notificação no banco do dados
    $obj_not_tran = new \Controllers\PagSeguroNotificationTransactionController($this->view, $this->pdo, $this->logger);
    $id_not_tran = $obj_not_tran->insert($arr_pagseguro_notification_transaction);

    // Atualizar os status de pagamento e do pedido
    $objProduto = new \Controllers\Produtos($this->view, $this->pdo, $this->logger);
    $id_pedido = $transaction->getReference();
    $status_pagamento_pedido = array(0 => 0, 1 => 1, 2 => 1, 3 => 3, 4 => 3, 5 => 6, 6 => 6, 7 => 6, 8 => 6, 9 => 6);
    $status_pagamento = $transaction->getStatus()->getValue();
    $status_pedido = $status_pagamento_pedido[$status_pagamento];
    $data_status = array("status_pedido" => $status_pedido, "status_pagamento" => $status_pagamento);
    $update_status = $objProduto->updateStatus($id_pedido, $data_status);

    return $this->view->render($response, 'default.php', array($id_not_tran, $arr_pagseguro_notification_transaction, $update_status)); //
});







