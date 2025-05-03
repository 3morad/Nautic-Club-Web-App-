<?php

namespace App\EventCalendar;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EventCalendarBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
} 