## Fabriman Symfony Sitemap Bundle

---

This Sitemap Bundle will automatically append inside the sitemap.xml file all routes that are visited.

## Installation

    composer require fabriman/symfony-sitemap

#### Create the config folder that the system will use to set the sitemap

    mkdir 0777 public/Sitemap

### Update the Sitemap to delete deprecated routes

    php bin/console sitemap:update

## Routes options

    // To avoid having this route inside the sitemap (admin, cronjobs and so on)
    #[Route('/syour_route', name: 'route', options: ["sitemap" => false])]
---
    // To set custom change frequency (default is weekly)
    #[Route('/syour_route', name: 'route', options: ["changefreq" => "daily"])]
 ---
    // To set custom change priority (default is 0.5)
    #[Route('/syour_route', name: 'route', options: ["priority" => "0.5"])]


## Sitemap.xml generator controller

    <?php

    namespace App\Controller;
    
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Component\Yaml\Yaml;
    
    class SitemapController extends AbstractController
    {
    private $params;
    public function __construct(ParameterBagInterface $params)
    {
    $this->params = $params;
    }
    
        #[Route('/sitemap.xml', name: 'sitemap', options: ["sitemap" => false])]
        public function index()
        {
            // find published blog posts from db
            $response = new Response();
            $sitemap_file = $this->params->get("sitemap_file");
            $yaml_sitemap = Yaml::parseFile($sitemap_file);
            if (array_key_exists("sitemap", $yaml_sitemap) && count($yaml_sitemap['sitemap']) > 0) {
                $urls = [];
                foreach ($yaml_sitemap['sitemap'] as $url) {
                    $urls[] = [
                        'loc' => $url['loc'],
                        'lastmod' => $url['lastmod'],
                        'changefreq' => $url['changefreq'],
                        'priority' => $url['priority'],
                    ];
                }
    
                $response = new Response(
                    $this->renderView('./sitemap/sitemap.html.twig', ['urls' => $urls]),
                    200
                );
            }
    
            $response->headers->set('Content-Type', 'text/xml');
            return $response;
        }
    }