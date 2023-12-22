<?php

namespace Fabriman\SymfonySitemapBundle\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AsCommand(
    name: "sitemap:update",
    description: "Update Sitemap",
    hidden: false,
    aliases: ['sitemap:update']
)]
class UpdateSitemapCommand extends Command
{
    private $params;
    private $client;

    public function __construct(
        ParameterBagInterface $params,
        HttpClientInterface $client,
    ) {
        parent::__construct();
        $this->params = $params;
        $this->client = $client;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln([
                '',
                '<info>Update is running ...</info>',
                '',
            ]);

            $sitemap_file = $this->params->get("sitemap_file");

            if (!file_exists($sitemap_file)) {
                throw new Exception("No sitemap has been found!");
            }

            $filesystem = new Filesystem();

            $yaml_sitemap = Yaml::parseFile($sitemap_file);

            foreach ($yaml_sitemap['sitemap'] as $key => $map) {
                $response = $this->client->request(
                    'GET',
                    $map['loc']
                );
                $statusCode = $response->getStatusCode();

                if ($statusCode != 200) {
                    unset($yaml_sitemap['sitemap'][$key]);
                    $yaml = Yaml::dump($yaml_sitemap);
                    $filesystem->remove($sitemap_file);
                    file_put_contents($sitemap_file, $yaml);
                    $output->writeln("<comment>Route $map[loc] removed as status $statusCode</comment>");
                }
            }


            $output->writeln("<info>Update has been done</info>");

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $ed = $e->getMessage();
            $output->writeln([
                "",
                "<error>$ed</error>",
                "",
            ]);
            return Command::FAILURE;
        }

    }

}
