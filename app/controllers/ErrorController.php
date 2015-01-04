<?php

namespace App\Controllers;

/**
 * Error controller, used as an endpoint for errors of all kinds
 *
 * @package App\Controllers
 * @authors Nathan Daly, <justlikephp@gmail.com>
 * @version 1.0
 * @created 2015-01-04
 */
class ErrorController extends Base\Controller
{
    /**
     * handler for the 404 errors
     */
    public function show404Action()
    {
        // methods aren't allowed to start with numbers
        // so we have to tell the view engine to use
        // these, because 'show404' looks silly.
        $this->view->pick('error/404');
    }

    /**
     * handler for the 503 errors
     */
    public function show503Action()
    {
        // methods aren't allowed to start with numbers
        // so we have to tell the view engine to use
        // these, because 'show503' looks silly.
        $this->view->render('error', '503');
    }
}