<?php

namespace JenkinsLaravel\Facade;

use Illuminate\Support\Facades\Facade;

class Jenkin extends Facade
{

    public static function getFacadeAccessor()
    {
        return "jenkin";
    }
}
