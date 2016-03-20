<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 3/16/16
 * Time: 2:52 PM
 */

namespace App\Models;


class ACL
{
    private $public;
    private $private;

    public function __construct($public, $private)
    {
        $this->setPrivate($private);
        $this->setPublic($public);
    }

    function setPrivate($private) {
        $this->private = $private;
    }

    function getPrivate() {
        return $this->private;
    }

    function setPublic($public) {
        $this->public = $public;
    }

    function getPublic() {
        return $this->public;
    }
}