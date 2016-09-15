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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class RenderCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('render')
            ->setDescription('Create configs with params from file.')
            ->setHelp(
                "This command allows you to create configuration files for nginx, php-fpm or even application itself from list of configs"
            )
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'The config file with list of params.')
            ->addOption(
                'project',
                'p',
                InputOption::VALUE_OPTIONAL,
                'The project from deploy folder with config file with list of params.'
            )
            ->addOption(
                'module',
                'm',
                InputOption::VALUE_OPTIONAL,
                'The component/module of project from deploy folder with config file with list of params.'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_OPTIONAL,
                'The config file of component/module of project from deploy folder with list of params.'
            )
            ->addOption(
                'template',
                't',
                InputOption::VALUE_REQUIRED,
                'The template which should be fill with params from config'
            )
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'The output file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cwd = getcwd();
        $defaultConfigExtension = '.config.yml';
        $configLoader = new Yaml();
        $configService = new ConfigService();
        $pathService = new PathService();

        $templatePath = $input->getOption('template');
        if (!is_readable($templatePath)) {
            throw new \InvalidArgumentException(sprintf('Could not read template file "%s"', $templatePath));
        }

        if ($configPath = $input->getOption('file')) {
            if (!is_readable($configPath)) {
                throw new \InvalidArgumentException(sprintf('Could not read template file "%s"', $configPath));
            }
            $currentConfigName = $configService->getConfigName($configPath, $defaultConfigExtension);
            $configsFolder = $configService->getConfigFolder($cwd.DIRECTORY_SEPARATOR.$configPath);
        } elseif ($projectName = $input->getOption('project')) {
            $dir = $pathService->getDirectory();
            $tree = $pathService->getComponentsTree($dir);
            if (empty($tree[$projectName])) {
                throw new \InvalidArgumentException(
                    sprintf('Not found project "%s" from path "%s"', $projectName, $dir)
                );
            }
            if ($componentName = $input->getOption('module')) {
                if (empty($tree[$projectName][$componentName])) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Not found component/module "%s" in project "%s" from path "%s"',
                            $componentName,
                            $projectName,
                            $dir
                        )
                    );
                }
                $configsFolder = $pathService->getConfigFolder($projectName, $componentName);
                if ($configName = $input->getOption('config')) {
                    if (empty($tree[$projectName][$componentName])) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Not found config "%s" in component "%s" in project "%s" in path "%s"',
                                $configName,
                                $componentName,
                                $projectName,
                                $dir
                            )
                        );
                    }
                    $currentConfigName = $configName;
                } else {
                    throw new \InvalidArgumentException('Config name should be set');
                }
            } else {
                throw new \InvalidArgumentException('Component/module name should be set');
            }
        } else {
            throw new \InvalidArgumentException('Project name or config file path should be set');
        }

        if (empty($currentConfigName)) {
            throw new \InvalidArgumentException('$currentConfigName should be set');
        }
        if (empty($configsFolder)) {
            throw new \InvalidArgumentException('$configsFolder should be set');
        }
        // list of config files content
        $configs = $configService->getExistedConfigs(
            $configsFolder,
            $defaultConfigExtension,
            $configLoader
        );

        if (empty($configs)) {
            throw new \InvalidArgumentException(sprintf('Not found configs in "%s"', $configsFolder));
        }

        // result config
        $config = $configService->getMergedConfig($configs, $currentConfigName);

        // base values
        $config['_']['options']['branch'] = '';
        $config['_']['options']['releaseName'] = '';

        $twig = $configService->getTwig();
        $config = $configService->generateConfig($config, $twig);

        $result = $configService->renderTemplate($config, $templatePath, $twig);

        if ($outputPath = $input->getOption('output')) {
            $fs = new Filesystem();
            $fs->dumpFile($outputPath, $result);
        } else {
            $output->write($result);
        }
    }
}