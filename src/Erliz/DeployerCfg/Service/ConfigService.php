<?php

/**
 * DeployerCfg package
 *
 * @package   Erliz\DeployerCfg
 * @author    Stanislav Vetlovskiy <mrerliz@gmail.com>
 * @copyright Copyright (c) Stanislav Vetlovskiy
 * @license   MIT
 */

namespace Erliz\DeployerCfg\Service;

use Symfony\Component\Yaml\Yaml;
use Twig_Environment;
use Twig_Lexer;
use Twig_Loader_String;
use Twig_SimpleFilter;


/**
 * Class ConfigService
 *
 * @author Stanislav Vetlovskiy <mrerliz@gmail.com>
 */
class ConfigService
{
    /**
     * @param string $configFolder
     * @param string $configExtension
     * @param Yaml   $configLoader
     *
     * @return array
     */
    public function getExistedConfigs($configFolder, $configExtension, Yaml $configLoader)
    {
        $configs = [];

        foreach ($this->getConfigsList($configFolder, $configExtension) as $configFile) {
            $pathNames = explode('/', $configFile);
            $configName = str_replace($configExtension, '', array_pop($pathNames));
            $configs[$configName] = $configLoader->parse(file_get_contents($configFile));
        }

        return $configs;
    }

    /**
     * @param string $configFolder
     * @param string $configExtension
     *
     * @return array
     */
    public function getConfigsList($configFolder, $configExtension)
    {
        return glob(sprintf('%s/*%s', $configFolder, $configExtension));
    }

    /**
     * @param array $configs
     *
     * @return array
     */
    private function getConfigDependencies($configs)
    {
        $dependencies = [];

        foreach ($configs as $configName => $config) {
            if (!empty($configs[$configName]['config']) && !empty($configs[$configName]['config']['parent'])) {
                $dependencies[$configName] = $configs[$configName]['config']['parent'];
            }
        }

        return $dependencies;
    }

    /**
     * @param string $configPath
     *
     * @return string
     */
    public function getConfigFolder($configPath)
    {
        return dirname($configPath);
    }

    /**
     * @param array  $configs
     * @param string $currentConfigName
     *
     * @return array
     */
    public function getMergedConfig($configs, $currentConfigName)
    {
        // array of config dependence by parent key
        $dependencies = $this->getConfigDependencies($configs);
        $parentConfigName = null;
        $config = [];

        while (1) {
            if (empty($configs[$currentConfigName])) {
                throw new \RuntimeException(sprintf('Not found config file with "%s" name', $currentConfigName));
            }

            if ($parentConfigName = $this->getParentConfigName($dependencies, $currentConfigName, $parentConfigName)) {
                $config = array_merge($config, $configs[$parentConfigName]);
            } else {
                $config = array_merge($config, $configs[$currentConfigName]);
                break;
            }
        }

        return $config;
    }

    /**
     * @param string $configPath
     * @param string $fileExtension
     *
     * @return string
     */
    static public function getConfigName($configPath, $fileExtension)
    {
        return str_replace($fileExtension, '', basename($configPath));
    }

    /**
     * @param array  $dependencies
     * @param string $configName
     * @param null   $maxFarthestParentName
     *
     * @return null
     */
    private function getParentConfigName($dependencies, $configName, $maxFarthestParentName = null)
    {
        $parentConfigName = $configName;
        while (1) {
            if (empty($dependencies[$parentConfigName]) || $dependencies[$parentConfigName] == $maxFarthestParentName) {
                if ($configName == $parentConfigName) {
                    return null;
                }

                return $parentConfigName;
            }

            $parentConfigName = $dependencies[$parentConfigName];
        }

        return null;
    }

    /**
     * @param array            $config
     * @param Twig_Environment $twig
     * @param array|null       $parentConfig
     *
     * @return array
     */
    private function recursiveRender($config, Twig_Environment $twig, $parentConfig = null)
    {
        foreach ($config as $key => $val) {
            if (is_array($val)) {
                $config[$key] = $this->recursiveRender($val, $twig, $parentConfig ?: $config);
            } else {
                if (is_scalar($val)) {
                    $config[$key] = $twig->render($val, $parentConfig ?: $config);
                }
            }
        }

        return $config;
    }

    /**
     * @return Twig_Environment
     */
    public function getTwig()
    {
        $loader = new Twig_Loader_String();
        $twig = new Twig_Environment($loader, ['debug' => true, 'strict_variables' => true, 'autoescape' => false]);

        $twig->addFilter(new Twig_SimpleFilter('emit_yaml', function ($value) {
            return Yaml::dump($value);
        }));

        return $twig;
    }

    /**
     * @param                  $config
     * @param Twig_Environment $twig
     *
     * @return array
     */
    public function generateConfig($config, Twig_Environment $twig)
    {
        $twig->setLexer(
            new Twig_Lexer(
                $twig, [
                    'tag_variable' => ['${', '}'],
                ]
            )
        );

        $i = 0;
        do {
            $configArrayOld = $config;
            $config = $this->recursiveRender($config, $twig);
            $i++;
        } while ($configArrayOld != $config);

        return $config;
    }

    /**
     * @param                  $config
     * @param                  $template
     * @param Twig_Environment $twig
     *
     * @return string
     */
    public function renderTemplate($config, $template, Twig_Environment $twig)
    {
        $twig->setLexer(new Twig_Lexer($twig, []));
        $result = $twig->render(
            file_get_contents($template),
            $config
        );

        return $result;
    }
}
