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
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Catalog navigation
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Block_Navigation extends Mage_Core_Block_Template
{
	protected $_categoryInstance = null;

	/**
	 * Array of level position counters
	 *
	 * @var array
	 */
	protected $_itemLevelPositions = array();

	protected function _construct()
	{
		$this->addData(array(
            'cache_lifetime'    => false,
            'cache_tags'        => array(Mage_Catalog_Model_Category::CACHE_TAG, Mage_Core_Model_Store_Group::CACHE_TAG),
		));
	}

	/**
	 * Retrieve Key for caching block content
	 *
	 * @return string
	 */
	public function getCacheKey()
	{
		return 'CATALOG_NAVIGATION_' . Mage::app()->getStore()->getId()
		. '_' . Mage::getDesign()->getPackageName()
		. '_' . Mage::getDesign()->getTheme('template')
		. '_' . Mage::getSingleton('customer/session')->getCustomerGroupId()
		. '_' . md5($this->getTemplate() . $this->getCurrenCategoryKey());
	}

	public function getCurrenCategoryKey()
	{
		if ($category = Mage::registry('current_category')) {
			return $category->getPath();
		} else {
			return Mage::app()->getStore()->getRootCategoryId();
		}
	}

	/**
	 * Get catagories of current store
	 *
	 * @return Varien_Data_Tree_Node_Collection
	 */
	public function getStoreCategories()
	{
		$helper = Mage::helper('catalog/category');
		return $helper->getStoreCategories();
	}

	/**
	 * Retrieve child categories of current category
	 *
	 * @return Varien_Data_Tree_Node_Collection
	 */
	public function getCurrentChildCategories()
	{
		$layer = Mage::getSingleton('catalog/layer');
		$category   = $layer->getCurrentCategory();
		/* @var $category Mage_Catalog_Model_Category */
		$categories = $category->getChildrenCategories();
		$productCollection = Mage::getResourceModel('catalog/product_collection');
		$layer->prepareProductCollection($productCollection);
		$productCollection->addCountToCategories($categories);
		return $categories;
	}

	/**
	 * Checkin activity of category
	 *
	 * @param   Varien_Object $category
	 * @return  bool
	 */
	public function isCategoryActive($category)
	{
		if ($this->getCurrentCategory()) {
			return in_array($category->getId(), $this->getCurrentCategory()->getPathIds());
		}
		return false;
	}

	protected function _getCategoryInstance()
	{
		if (is_null($this->_categoryInstance)) {
			$this->_categoryInstance = Mage::getModel('catalog/category');
		}
		return $this->_categoryInstance;
	}

	/**
	 * Get url for category data
	 *
	 * @param Mage_Catalog_Model_Category $category
	 * @return string
	 */
	public function getCategoryUrl($category)
	{
		if ($category instanceof Mage_Catalog_Model_Category) {
			$url = $category->getUrl();
		} else {
			$url = $this->_getCategoryInstance()
			->setData($category->getData())
			->getUrl();
		}

		return $url;
	}

	/**
	 * Return item position representation in menu tree
	 *
	 * @param int $level
	 * @return string
	 */
	protected function _getItemPosition($level)
	{
		if ($level == 0) {
			$zeroLevelPosition = isset($this->_itemLevelPositions[$level]) ? $this->_itemLevelPositions[$level] + 1 : 1;
			$this->_itemLevelPositions = array();
			$this->_itemLevelPositions[$level] = $zeroLevelPosition;
		} elseif (isset($this->_itemLevelPositions[$level])) {
			$this->_itemLevelPositions[$level]++;
		} else {
			$this->_itemLevelPositions[$level] = 1;
		}

		$position = array();
		for($i = 0; $i <= $level; $i++) {
			if (isset($this->_itemLevelPositions[$i])) {
				$position[] = $this->_itemLevelPositions[$i];
			}
		}
		return implode('-', $position);
	}

	/**
	 * Enter description here...
	 *
	 * @param Mage_Catalog_Model_Category $category
	 * @param int $level
	 * @param boolean $last
	 * @return string
	 */
	public function drawItem($category, $level=0, $last=false)
	{
		$html = '';
		if (!$category->getIsActive()) {
			return $html;
		}
		if (Mage::helper('catalog/category_flat')->isEnabled()) {
			$children = $category->getChildrenNodes();
			$childrenCount = count($children);
		} else {
			$children = $category->getChildren();
			$childrenCount = $children->count();
		}
		$hasChildren = $children && $childrenCount;
		$html.= '<li';

		if ($hasChildren || ($category->getName() == 'Frames')) {
			$html.= ' onmouseover="toggleMenu(this,1)" onmouseout="toggleMenu(this,0)"';
		}

		$html.= ' class="level'.$level;
		//$html.= ' nav-'.str_replace('/', '-', Mage::helper('catalog/category')->getCategoryUrlPath($category->getRequestPath()));
		$html.= ' nav-' . $this->_getItemPosition($level);
		if ($this->isCategoryActive($category)) {
			$html.= ' active';
		}
		if ($last) {
			$html .= ' last';
		}
		if ($hasChildren) {
			$cnt = 0;
			foreach ($children as $child) {
				if ($child->getIsActive()) {
					$cnt++;
				}
			}
			if ($cnt > 0) {
				$html .= ' parent';
			}
		}
		$html.= '">'."\n";
		$html.= '<a href="'.$this->getCategoryUrl($category).'"><span>'.$this->htmlEscape($category->getName()).'</span></a>'."\n";

		if($category->getName() != 'Frames')
		{
			if ($hasChildren){

				$j = 0;
				$htmlChildren = '';
				foreach ($children as $child) {
					if ($child->getIsActive()) {
						$htmlChildren.= $this->drawItem($child, $level+1, ++$j >= $cnt);
					}
				}

				if (!empty($htmlChildren)) {
					$html.= '<ul class="level' . $level . '">'."\n"
					.$htmlChildren
					.'</ul>';
				}

			}
		}
		else
		{

			$_myStore = Mage::app()->getStore()->getCode();
			if($_myStore == 'wholesaledefault'){
				$html.= '<ul class="level0">
		<li class="level1 nav-1-1">
		<a href="'.$this->getBaseURL().'frames/mixte-72.html"><span>Mixte</span></a>
		</li>
		
		<li class="level1 nav-1-2">
		<a href="'.$this->getBaseURL().'frames/polyvalent-55.html"><span>Polyvalent</span></a>
		</li>
		
		<li class="level1 nav-1-3 last">
		<a href="'.$this->getBaseURL().'frames/rando-74.html"><span>Rando</span></a>
		</li>
		
		
		</ul>';
			}
			else
			{
				$html.= '<ul class="level0">
		<li class="level1 nav-1-1">
		<a href="'.$this->getBaseURL().'frames/mixte-72.html"><span>Mixte</span></a>
		</li>
		
		<li class="level1 nav-1-2">
		<a href="'.$this->getBaseURL().'frames/polyvalent-55.html"><span>Polyvalent</span></a>
		</li>
		
		<li class="level1 nav-1-3">
		<a href="'.$this->getBaseURL().'frames/rando-74.html"><span>Rando</span></a>
		</li>
		
		<li class="level1 nav-1-4 last">
		<a href="'.$this->getBaseURL().'frames/build-kit.html"><span>Build Kits</span></a>
		</li>
		
		</ul>';
			}
		}




		$html.= '</li>'."\n";
		return $html;
	}

	/**
	 * Enter description here...
	 *
	 * @return Mage_Catalog_Model_Category
	 */
	public function getCurrentCategory()
	{
		if (Mage::getSingleton('catalog/layer')) {
			return Mage::getSingleton('catalog/layer')->getCurrentCategory();
		}
		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getCurrentCategoryPath()
	{
		if ($this->getCurrentCategory()) {
			return explode(',', $this->getCurrentCategory()->getPathInStore());
		}
		return array();
	}

	/**
	 * Enter description here...
	 *
	 * @param Mage_Catalog_Model_Category $category
	 * @return string
	 */
	public function drawOpenCategoryItem($category) {
		$html = '';
		if (!$category->getIsActive()) {
			return $html;
		}

		$html.= '<li';

		if ($this->isCategoryActive($category)) {
			$html.= ' class="active"';
		}

		$html.= '>'."\n";
		$html.= '<a href="'.$this->getCategoryUrl($category).'"><span>'.$this->htmlEscape($category->getName()).'</span></a>'."\n";

		if (in_array($category->getId(), $this->getCurrentCategoryPath())){
			$children = $category->getChildren();
			$hasChildren = $children && $children->count();

			if ($hasChildren) {
				$htmlChildren = '';
				foreach ($children as $child) {
					$htmlChildren.= $this->drawOpenCategoryItem($child);
				}

				if (!empty($htmlChildren)) {
					$html.= '<ul>'."\n"
					.$htmlChildren
					.'</ul>';
				}
			}
		}
		$html.= '</li>'."\n";
		return $html;
	}

}
