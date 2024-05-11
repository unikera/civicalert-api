<?php

declare(strict_types=1);
use App\Application\Actions\Citoyen\RegisterAction;
use App\Application\Actions\Agent\LoginAgentAction;
use App\Application\Actions\Agent\RegisterAgentAction;
use App\Application\Actions\Agent\UpdateProfileAgentAction;
use App\Application\Actions\LoginAction;
use App\Application\Actions\Incident\ListIncidentAction;
use App\Application\Actions\Incident\DeclareIncidentAction;
use App\Application\Actions\Incident\GetIncidentAction;
use App\Application\Actions\Incident\SupIncidentAction;
use App\Application\Actions\Incident\ConfirmationIncidentAction;
use App\Application\Actions\Incident\TraiterIncidentAction;
use App\Application\Actions\Incident\CloturerIncidentAction; 
use App\Application\Actions\Admin\LoginAdminAction; 
use App\Application\Actions\Admin\ListIncidentActionAdmin;
use App\Application\Actions\Admin\ListAgentActionAdmin;
use App\Application\Actions\Citoyens\EditCitoyensAction;
use App\Application\Actions\Citoyens\EditPassAction;
use App\Application\Actions\Citoyens\InfosCitoyensAction;
use App\Application\Actions\UpdateProfileAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Middleware\JwtAuthenticationMiddleware;

use Slim\App;

return function (App $app) {

    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Bienvenue!');
        return $response;
    });

    $app->post('/inscription', RegisterAction::class);
    $app->post('/login', LoginAction::class);
    // $app->post('/modification',UpdateProfileAction::class)->add(new JwtAuthenticationMiddleware());
    $app->get('/infosCitoyen/{id}', InfosCitoyensAction::class);
    $app->post('/editPass', EditPassAction::class);
    $app->post('/editCitoyen', EditCitoyensAction::class);
    $app->post('/agent/inscription', RegisterAgentAction::class)->add(new JwtAuthenticationMiddleware());
    $app->post('/agent/login', LoginAgentAction::class);
    $app->post('/agent/modification',UpdateProfileAgentAction::class)->add(new JwtAuthenticationMiddleware());
    $app->get('/listeIncidents', ListIncidentAction::class);
    $app->get('/infoIncident/{id}', GetIncidentAction::class);
    $app->get('/traiterIncident/{id}', TraiterIncidentAction::class)->add(new JwtAuthenticationMiddleware());
    $app->get('/supprimerIncident/{id}', SupIncidentAction::class)->add(new JwtAuthenticationMiddleware());
    $app->get('/cloturerIncident/{id}', CloturerIncidentAction::class)->add(new JwtAuthenticationMiddleware());
    $app->post('/declarerIncident', DeclareIncidentAction::class)->add(new JwtAuthenticationMiddleware());
    $app->get('/confirmer/{id}', ConfirmationIncidentAction::class)->add(new JwtAuthenticationMiddleware());
    $app->post('/admin/login', LoginAdminAction::class);
    $app->get('/admin/incident', ListIncidentActionAdmin::class);
    $app->get('/admin/agent', ListAgentActionAdmin::class);

};
