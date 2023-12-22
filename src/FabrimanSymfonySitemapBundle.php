<?php

namespace Fabriman\SymfonySitemapBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Fabriman\SymfonySitemapBundle\DependencyInjection\FabrimanSymfonySitemapBundleExtension;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class FabrimanSymfonySitemapBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new FabrimanSymfonySitemapBundleExtension();
    }
}
