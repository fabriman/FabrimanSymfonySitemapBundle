<?php

namespace Fabriman\SymfonySitemapBundle\EventSubscriber;

use DateTime;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;

class SitemapSubscriber implements EventSubscriberInterface
{
    private $router;
    private $params;
    private $kernel;

    public function __construct(RouterInterface $router, ParameterBagInterface $params, KernelInterface $kernel)
    {
        $this->router = $router;
        $this->params = $params;
        $this->kernel = $kernel;
    }

    public static function getSubscribedEvents(): array
    {
        // return the subscribed events, their methods and priorities
        return [
            ControllerEvent::class => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event)
    {

        // Exclude system routes
        $exclude = [
            "_profiler",
            "_preview",
            "_wdt"
        ];

        $request = $event->getRequest();

        if ($_route  = $request->attributes->get('_route')) {

            // Get the name of the route
            $route = $this->router->getRouteCollection()->get($_route);

            // Verify that this route is not inside the exclusion list
            if (!in_array($_route, $exclude) && $this->kernel->getEnvironment() == 'prod') {
                $filesystem = new Filesystem();

                /*
                 * Get the config file
                 * if not present it will be created under /public/bundles/fabrimansymfonysitemap/Sitemap
                 */
                $sitemap_file = $this->params->get("sitemap_file");
                if (!file_exists($sitemap_file)) {
                    $filesystem->touch($sitemap_file);
                    file_put_contents($sitemap_file, Yaml::dump(['sitemap' => []]));
                }

                // Get the config file as an array
                $yaml_sitemap = Yaml::parseFile($sitemap_file);

                /*
                 * Check if in the route options the sitemap is set to false
                 * If is set to false, the page will be skipped
                 */
                $is_sitemap = (array_key_exists("sitemap", $route->getOptions())) ? ($route->getOptions())["sitemap"] : true;

                if ($is_sitemap && array_key_exists("sitemap", $yaml_sitemap)) {

                    // Get the full path
                    $path = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://" . "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

                    // Search if the path is already set inside the sitemap
                    $search_in_config = false;
                    if ($yaml_sitemap['sitemap'] && count($yaml_sitemap['sitemap']) > 0) {
                        $search_in_config = array_search($path, array_column($yaml_sitemap['sitemap'], "loc"));
                    }

                    if ($search_in_config === false) {

                        // Set the default value for the sitemap
                        $lastmod = (new DateTime())->format("Y-m-d");
                        $changefreq = (array_key_exists("changefreq", $route->getOptions())) ? ($route->getOptions())["changefreq"] : "weekly";
                        $priority = (array_key_exists("priority", $route->getOptions())) ? ($route->getOptions())["priority"] : "0.5";

                        // Create and set the new element
                        $yaml_sitemap['sitemap'][] = [
                            "loc" => $path,
                            "lastmod" => $lastmod,
                            "changefreq" => $changefreq,
                            "priority" => $priority
                        ];

                        $yaml = Yaml::dump($yaml_sitemap);
                        $filesystem->remove($sitemap_file);
                        file_put_contents($sitemap_file, $yaml);
                    }
                }
            }
        }
    }
}
