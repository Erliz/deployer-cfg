<?php

/**
 * DeployerCfg package
 *
 * @package   Erliz\DeployerCfg
 * @author    Stanislav Vetlovskiy <mrerliz@gmail.com>
 * @copyright Copyright (c) Stanislav Vetlovskiy
 * @license   MIT
 */

namespace Erliz\DeployerCfg\Command;

use Erliz\DeployerCfg\Service\ConfigService;
use Erliz\DeployerCfg\Service\PathService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ListConfigsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('list-configs')
            ->setDescription('Display list of configs files')
            ->setHelp(
                "This command allows you to display all available config files."
            )
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to config files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //        $cwd = getcwd();
//        $defaultConfigExtension = '.config.yml';
        //        $configService = new ConfigService();
        //
        //        $configPath = $input->getArgument('path');
        //        if (!is_readable($configPath)) {
        //            throw new \InvalidArgumentException(sprintf('Could not find configs folder in "%s"', $configPath));
        //        }
        //        if (!is_dir($configPath)) {
        //            $configsFolder = $configService->getConfigFolder($cwd.DIRECTORY_SEPARATOR.$configPath);
        //        } else {
        //            $configsFolder = $configPath;
        //        }
        //        // list of config files content
        //        $configs = $configService->getConfigsList(
        //            $configsFolder,
        //            $defaultConfigExtension
        //        );
        //
        $pathService = new PathService();
        //
        //        if (empty($configs)) {
        //            throw new \InvalidArgumentException(sprintf('Not found configs in "%s"', $configsFolder));
        //        }

        $configs = $pathService->getComponentsTree($pathService->getDirectory($input->getArgument('path')));
        if (empty($configs)) {
            throw new \InvalidArgumentException(
                sprintf('Not found configs in "%s"', $input->getArgument('path') ?: 'current dir')
            );
        }

        $output->writeln("List of available configs:\n");
        foreach ($configs as $project => $components) {
            $output->writeln(sprintf("  - %s", $project));
            foreach ($components as $component => $configs) {
                $output->writeln(sprintf("    - %s", $component));
                foreach ($configs as $config) {
                    $output->writeln(sprintf("      - %s", $config));
                }
            }
        }
    }
}