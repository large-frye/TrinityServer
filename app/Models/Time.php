<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/5/16
 * Time: 11:10 PM
 */

namespace App\Models;

class Time
{
    private $start;
    private $end;

    public function __construct($start, $end) {
        $this->start = $start;
        $this->end = $end;
    }

    public function getStart() { return $this->start; }
    public function setStart($start) { $this->start = $start; }

    public function getEnd() { return $this->end; }
    public function setEnd($end) { $this->end = $end; }
}