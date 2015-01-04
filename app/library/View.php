<?php

namespace App;

use Phalcon\Mvc\View as PhalconView;

/**
 * Adds a feature to return a partial as a string.
 *
 * @package App
 * @authors Nathan Daly, <justlikephp@gmail.com>
 * @version 1.0
 * @created 2015-01-04
 */
class View extends PhalconView
{
    /**
     * Return a view partial as a string
     *
     * @param string $path The template path
     * @param array $params The params to feed the template
     *
     * @return string
     */
    public function getPartial($path, $params = array())
    {
        ob_start();
        $this->partial($path, $params);
        return ob_get_clean();
    }
}