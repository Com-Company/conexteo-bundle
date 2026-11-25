<?php

namespace Connected\Conexteo;

use Connected\Conexteo\DependencyInjection\Compiler\CopyTexterTransportDsnsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ConexteoBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CopyTexterTransportDsnsPass());
    }
}
