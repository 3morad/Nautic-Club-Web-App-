<?php

namespace App\EmailSystem;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EmailSystemBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
} 