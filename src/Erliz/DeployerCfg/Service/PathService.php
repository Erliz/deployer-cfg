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


/**
 * Class PathService
 *
 * @author Stanislav Vetlovskiy <mrerliz@gmail.com>
 */
class PathService
{
    const DEFAULT_ROOT_FOLDER = 'deploy';
    const DEFAULT_PRJ_AND_COMP_FOLDER = '-';
    const DEFAULT_CONF_FOLDER = 'conf';

    public function getComponentsTree($directory, $configExtension = '.config.yml')
    {
        $pattern = join(DIRECTORY_SEPARATOR, [$directory, '*-*', self::DEFAULT_CONF_FOLDER, '*'.$configExtension]);

        $tree = [];
        foreach (glob($pattern) as $item) {
            //            dump($item);
            if (is_readable($item)) {
                preg_match(
                    sprintf(
                        '/%s\%s(.*?\-.*?)\%s/',
                        self::DEFAULT_ROOT_FOLDER,
                        DIRECTORY_SEPARATOR,
                        DIRECTORY_SEPARATOR
                    ),
                    $item,
                    $match
                );
                list($project, $component) = explode('-', $match[1]);
                if (!isset($tree[$project])) {
                    $tree[$project] = [];
                }
                if (!isset($tree[$project][$component])) {
                    $tree[$project][$component] = [];
                }
                $tree[$project][$component][] = ConfigService::getConfigName($item, $configExtension);
            }
        }

        return $tree;
    }

    public function getDirectory($root = null)
    {
        return getcwd().DIRECTORY_SEPARATOR.($root ? $root : self::DEFAULT_ROOT_FOLDER);
    }

    public function getProjects($directory = null)
    {
        if (!$directory) {
            $directory = $this->getDirectory();
        }

        return array_keys($this->getComponentsTree($directory));
    }

    public function getComponents($project, $directory = null)
    {
        if (!$directory) {
            $directory = $this->getDirectory();
        }

        return $this->getComponentsTree($directory)[$project];
    }

    public function getConfigFolder($project, $component, $rootDir = null)
    {
        return implode(DIRECTORY_SEPARATOR, [$this->getDirectory($rootDir), $project.'-'.$component, self::DEFAULT_CONF_FOLDER]);
    }
}
