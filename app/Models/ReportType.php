<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/25/16
 * Time: 2:36 PM
 */

namespace App\Models;


class ReportType {

    private $id;
    private $negate;
    private $status;
    private $date;

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getNegate()
    {
        return $this->negate;
    }

    /**
     * @param mixed $negate
     */
    public function setNegate($negate)
    {
        $this->negate = $negate;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public static function createReport($id, $status, $negate, $date = false) {
        $reportType = new ReportType();
        $reportType->setId($id);
        $reportType->setStatus($status);
        $reportType->setNegate($negate);
        $reportType->setDate($date);
        return $reportType;
    }

    public static function toArray(ReportType $reportType) {
        return array('id' => $reportType->getId(),
            'status' => $reportType->getStatus(),
            'negate' => $reportType->getNegate(),
            'date' => $reportType->getDate());
    }
}