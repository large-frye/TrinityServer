<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/28/16
 * Time: 11:14 AM
 */

namespace App\Models;

use DateTime;

class Count
{
  const ONE_DAY = 86400;

    // All count measures need to be in ms.
    public function __construct()
    {
      $time = time();

      // Other dates for reports will most likely have to be updated.
      $this->tomorrowStart = (strtotime(date('Y-m-d 00:00:00'), $time) + self::ONE_DAY) * 1000;
      $this->tomorrowEnd = (strtotime(date('Y-m-d 23:59:59'), $time) + self::ONE_DAY) * 1000;


        $this->today = strtotime('today') * 1000;
        $this->tomorrow = strtotime('tomorrow') * 1000;
        $this->nextTwoDays = strtotime('+2 day') * 1000;
        $this->yesterday = strtotime('yesterday') * 1000;
        $this->lastTwoDays = strtotime('-2 day') * 1000;
        $this->thisWeek = strtotime('this week') * 1000;
        $this->nextWeek = strtotime('next week') * 1000;
        $this->lastWeek = strtotime('last week') * 1000;
        $this->twoLastWeek = strtotime('-2 week') * 1000;
        $this->twoNextWeek = strtotime('+2 week') * 1000;
        $this->thisMonth = strtotime('first day of this month') * 1000;
        $this->lastMonth = strtotime('first day of last month') * 1000;
        $this->lastDayofLastMonth = strtotime('last day of last month') * 1000;
        $this->lastDayOfThisMonth = strtotime('last day of this month') * 1000;
        $this->nextMonth = strtotime('first day of next month') * 1000;
        $this->lastDayOfNextMonth = strtotime('last day of next month') * 1000;
        $this->year = strtotime('first day of january') * 1000;
        $this->nextYear = $this->getNextYear() * 1000;
        $this->lastYear = $this->getLastYear() * 1000;
    }

    public function getNextYear()
    {
        $nextYear = new DateTime('first day of january');
        $nextYear->modify('+1 year');
        return strtotime($nextYear->format('Y-m-d 00:00:00'));
    }

    public function getLastYear()
    {
        $lastYear = new DateTime('first day of january');
        $lastYear->modify('-1 year');
        return strtotime($lastYear->format('Y-m-d 00:00:00'));
    }
}