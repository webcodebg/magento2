<?php
/**
 * Static class that represents profiling tool
 *
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2012 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magento_Profiler
{
    /**
     * Separator literal to assemble timer identifier from timer names
     */
    const NESTING_SEPARATOR = '->';

    /**
     * Whether profiling is active or not
     *
     * @var bool
     */
    static private $_enabled = false;

    /**
     * Nesting path that represents namespace to resolve timer names
     *
     * @var array
     */
    static private $_currentPath = array();

    /**
     * Count of elements in $_currentPath
     *
     * @var int
     */
    static private $_pathCount = 0;

    /**
     * Index for counting of $_pathCount for timer names
     *
     * @var array
     */
    static private $_pathIndex = array();

    /**
     * Collection for profiler drivers.
     *
     * @var array
     */
    static private $_drivers = array();

    /**
     * List of default tags.
     *
     * @var array
     */
    static private $_defaultTags = array();

    /**
     * Collection of tag filters.
     *
     * @var array
     */
    static private $_tagFilters = array();

    /**
     * Has tag filters flag to faster checks of filters availability.
     *
     * @var bool
     */
    static private $_hasTagFilters = false;

    /**
     * Set default tags
     *
     * @param array $tags
     */
    public static function setDefaultTags(array $tags)
    {
        self::$_defaultTags = $tags;
    }

    /**
     * Add tag filter.
     *
     * @param string $tagName
     * @param string $tagValue
     */
    public static function addTagFilter($tagName, $tagValue)
    {
        if (!isset(self::$_tagFilters[$tagName])) {
            self::$_tagFilters[$tagName] = array();
        }
        self::$_tagFilters[$tagName][] = $tagValue;
        self::$_hasTagFilters = true;
    }

    /**
     * Check tags with tag filters.
     *
     * @param array|null $tags
     * @return bool
     */
    private static function _checkTags(array $tags = null)
    {
        if (self::$_hasTagFilters) {
            if (is_array($tags)) {
                $keysToCheck = array_intersect(array_keys(self::$_tagFilters), array_keys($tags));
                if ($keysToCheck) {
                    foreach ($keysToCheck as $keyToCheck) {
                        if (in_array($tags[$keyToCheck], self::$_tagFilters[$keyToCheck])) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Add profiler driver.
     *
     * @param Magento_Profiler_DriverInterface $driver
     */
    public static function add(Magento_Profiler_DriverInterface $driver)
    {
        self::$_drivers[get_class($driver)] = $driver;
        self::enable();
    }

    /**
     * Retrieve unique identifier among all timers
     *
     * @param string|null $timerName Timer name
     * @return string
     */
    private static function _getTimerId($timerName = null)
    {
        if (!self::$_currentPath) {
            return (string)$timerName;
        } elseif ($timerName) {
            return implode(self::NESTING_SEPARATOR, self::$_currentPath) . self::NESTING_SEPARATOR . $timerName;
        } else {
            return implode(self::NESTING_SEPARATOR, self::$_currentPath);
        }
    }

    /**
     * Get tags list.
     *
     * @param array|null $tags
     * @return array|null
     */
    private static function _getTags(array $tags = null)
    {
        if (self::$_defaultTags) {
            return (array)$tags + self::$_defaultTags;
        } else {
            return $tags;
        }
    }

    /**
     * Enable profiling.
     *
     * Any call to profiler does nothing until profiler is enabled.
     */
    public static function enable()
    {
        self::$_enabled = true;
    }

    /**
     * Disable profiling.
     *
     * Any call to profiler is silently ignored while profiler is disabled.
     */
    public static function disable()
    {
        self::$_enabled = false;
    }

    /**
     * Get profiler enable status.
     *
     * @return bool
     */
    public static function isEnabled()
    {
        return self::$_enabled;
    }

    /**
     * Clear collected statistics for specified timer or for whole profiler if timer id is omitted
     *
     * @param string|null $timerName
     * @throws InvalidArgumentException
     */
    public static function clear($timerName = null)
    {
        if (strpos($timerName, self::NESTING_SEPARATOR) !== false) {
            throw new InvalidArgumentException('Timer name must not contain a nesting separator.');
        }
        $timerId = self::_getTimerId($timerName);
        /** @var Magento_Profiler_DriverInterface $driver */
        foreach (self::$_drivers as $driver) {
            $driver->clear($timerId);
        }
    }

    /**
     * Reset profiler to initial state
     */
    public static function reset()
    {
        self::clear();
        self::$_enabled = false;
        self::$_currentPath = array();
        self::$_tagFilters = array();
        self::$_defaultTags = array();
        self::$_hasTagFilters = false;
        self::$_drivers = array();
        self::$_pathCount = 0;
        self::$_pathIndex = array();
    }

    /**
     * Start collecting statistics for specified timer
     *
     * @param string $timerName
     * @param array|null $tags
     * @throws InvalidArgumentException
     */
    public static function start($timerName, array $tags = null)
    {
        if (!self::$_enabled) {
            return;
        }

        $tags = self::_getTags($tags);
        if (!self::_checkTags($tags)) {
            return;
        }

        if (strpos($timerName, self::NESTING_SEPARATOR) !== false) {
            throw new InvalidArgumentException('Timer name must not contain a nesting separator.');
        }

        $timerId = self::_getTimerId($timerName);
        /** @var Magento_Profiler_DriverInterface $driver */
        foreach (self::$_drivers as $driver) {
            $driver->start($timerId, $tags);
        }
        /* Continue collecting timers statistics under the latest started one */
        self::$_currentPath[] = $timerName;
        self::$_pathCount++;
        self::$_pathIndex[$timerName][] = self::$_pathCount;
    }

    /**
     * Stop recording statistics for specified timer.
     *
     * Call with no arguments to stop the recently started timer.
     * Only the latest started timer can be stopped.
     *
     * @param string|null $timerName
     * @throws InvalidArgumentException
     */
    public static function stop($timerName = null)
    {
        if (!self::$_enabled || !self::_checkTags(self::_getTags())) {
            return;
        }

        if ($timerName === null) {
            $timersToStop = 1;
        } else {
            $timerPosition = false;
            if (!empty(self::$_pathIndex[$timerName])) {
                $timerPosition = array_pop(self::$_pathIndex[$timerName]);
            }
            if ($timerPosition === false) {
                throw new InvalidArgumentException(sprintf('Timer "%s" has not been started.', $timerName));
            } elseif ($timerPosition === 1) {
                $timersToStop = 1;
            } else {
                $timersToStop = self::$_pathCount + 1 - $timerPosition;
            }
        }

        for ($i = 0; $i < $timersToStop; $i++) {
            $timerId = self::_getTimerId();
            /** @var Magento_Profiler_DriverInterface $driver */
            foreach (self::$_drivers as $driver) {
                $driver->stop($timerId);
            }
            /* Move one level up in timers nesting tree */
            array_pop(self::$_currentPath);
            self::$_pathCount--;
        }
    }
}