<?php

namespace App\Calendars;

use Illuminate\Support\Facades\Facade;

class CalendarFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'calendar';
    }
}