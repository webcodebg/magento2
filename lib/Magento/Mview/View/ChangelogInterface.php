<?php
/**
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
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Mview\View;

interface ChangelogInterface
{
    /**
     * Create changelog table
     *
     * @return boolean
     */
    public function create();

    /**
     * Drop changelog table
     *
     * @return boolean
     */
    public function drop();

    /**
     * Clear changelog by version_id
     *
     * @param $versionId
     * @return boolean
     */
    public function clear($versionId);

    /**
     * Retrieve entity ids by range [$fromVersionId..$toVersionId]
     *
     * @param integer $fromVersionId
     * @param integer $toVersionId
     * @return int[]
     */
    public function getList($fromVersionId, $toVersionId);

    /**
     * Get maximum version_id from changelog
     *
     * @return int
     */
    public function getVersion();

    /**
     * Get changlog name
     *
     * @return string
     */
    public function getName();

    /**
     * Get changlog entity column name
     *
     * @return string
     */
    public function getColumnName();

    /**
     * Set view's identifier
     *
     * @param string $viewId
     */
    public function setViewId($viewId);

    /**
     * Get view's identifier
     *
     * @return string
     */
    public function getViewId();
}