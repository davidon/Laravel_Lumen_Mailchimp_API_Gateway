<?php
declare(strict_types=1);

/**
 * It's found PUT/PATCH method can only accept raw JSON request data (content-type=application/json
 * @var \Laravel\Lumen\Routing\Router $router
 */

// MailChimp group
$router->group(['prefix' => 'mailchimp', 'namespace' => 'MailChimp'], function () use ($router) {
    // Lists group
    $router->group(['prefix' => 'lists'], function () use ($router) {
        //members end points
        $router->post('/{list_id}/members', 'MembersController@create');
        $router->get('/{list_id}/members', 'MembersController@showListMembers');
        //member_id is subscriber hash in manual
        $router->get('/{list_id}/members/{member_id}', 'MembersController@showMember');
        //ideally put should update a whole record
        $router->put('/{list_id}/members/{member_id}', 'MembersController@update');
        $router->delete('/{list_id}/members/{member_id}', 'MembersController@remove');

        $router->post('/', 'ListsController@create');
        $router->get('/', 'ListsController@showall');
        $router->get('/{listId}', 'ListsController@show');
        $router->put('/{listId}', 'ListsController@update');
        $router->patch('/{listId}', 'ListsController@update');
        $router->delete('/{listId}', 'ListsController@remove');
    });
});
