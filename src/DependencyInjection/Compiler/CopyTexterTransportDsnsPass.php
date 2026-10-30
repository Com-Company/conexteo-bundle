<?php

declare(strict_types=1);

namespace Connected\Conexteo\DependencyInjection\Compiler;

use Connected\Conexteo\Client\ConexteoClientRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Recopie les DSN de `framework.notifier.texter_transports` dans le registre des
 * clients : l'application n'a pas à les redéclarer pour lire les réponses.
 */
final class CopyTexterTransportDsnsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('texter.transports')) {
            return;
        }

        $texterTransportDsns = $container->getDefinition('texter.transports')->getArgument(0);

        if (!\is_array($texterTransportDsns)) {
            return;
        }

        $container->getDefinition(ConexteoClientRegistry::class)->setArgument('$texterTransportDsns', $texterTransportDsns);
    }
}
