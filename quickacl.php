<?php
/**
* @package    quickACL
* @copyright  Copyright (C) 2012 Julien Vonthron. All rights reserved. Based on original inlineACL plugin from keep http://joomla.blog.hu
* @license    GNU/GPL, see LICENSE.php
* quickACL is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Import library dependencies
jimport('joomla.plugin.plugin');

class plgContentQuickacl extends JPlugin {

	public function __construct( $subject, $params )
	{
		parent::__construct( $subject, $params );
		$this->loadLanguage();
	}

	/**
	 * Plugin that loads events lists within content
	 *
	 * @param	string	The context of the content being passed to the plugin.
	 * @param	object	The article object.  Note $article->text is also available
	 * @param	object	The article params
	 * @param	int		The 'page' number
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		if ( JString::strpos( $article->text, 'qACL' ) === false 
			&& JString::strpos( $article->text, 'iACL' ) === false) {
			return true;
		}
		$regex = "#{(?:q|i)ACL.*type=([^\s]+)\s(.*)}(.*){/(?:q|i)ACL}#sU";
		$article->text = preg_replace_callback( $regex, array($this, '_replacer'), $article->text );
		return true;
	}

	protected function _replacer( &$matches )
	{
		$user = JFactory::getUser();
		$db   = JFactory::getDBO();

		$text = "";

		$cbfield = $this->params->get('cbfield', '');

		$ids = explode(",", $matches[2]);
		$ids = array_map('trim', $ids);

		switch($matches[1]) {
			case "userid":
				if (in_array($user->id, $ids)) {
					$text = $matches[3];
				}
				break;
					
			case "!userid":
				if (!in_array($user->id, $ids)) {
					$text = $matches[3];
				}
				break;
					
			case "username":
				if (in_array($user->username, $ids)) {
					$text = $matches[3];
				}
				break;
					
			case "!username":
				if (!in_array($user->username, $ids)) {
					$text = $matches[3];
				}
				break;

			case "group":
				if (self::isInGroup($user, $ids)) {
					$text = $matches[3];
				}
				break;
					
			case "!group":
				if (!self::isInGroup($user, $ids)) {
					$text = $matches[3];
				}
				break;
					
			case "cbfield":
				if(empty($cbfield)) return;

				$query = "SELECT $cbfield as f
				          FROM #__comprofiler
				          WHERE id = " . $user->id;
				$db->setQuery($query);
				$list = $db->loadObjectList();
				$fieldValue = $list[0]->f;
				if(in_array($fieldValue, $ids)) {
					$text = $matches[3];
				}
				break;
					
			case "!cbfield":
				if(empty($cbfield)) return;
				$query = "SELECT $cbfield as f
				          FROM #__comprofiler
				          WHERE id = " . $user->id;
				$db->setQuery($query);
				$list = $db->loadObjectList();
				$fieldValue = $list[0]->f;
				if(!in_array($fieldValue, $ids)) {
					$text = $matches[3];
				}
				break;
		}

		return $text;
	}

	/**
	 * returns true if the user belongs to one of the groups
	 *
	 * @param JUser $user
	 * @param Array group names
	 * @return array
	 */
	protected static function isInGroup(JUser $user, array $names)
	{
		// check if 'guest'
		if (in_array("guest", $names))
		{
			return $user->id > 0 ? false : true;
		}
		$groups = JAccess::getGroupsByUser($user->id, true);
		if (!$groups) {
			return array();
		}
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('LOWER(grp.title)')
		      ->from('`#__usergroups` AS grp');
		$query->where('grp.id IN ('.implode(',', $groups).')');
		$db->setQuery($query);
		$res = $db->loadResultArray();

		$names = array_map('strtolower', $names);
		$inter = array_intersect($res, $names);

		return count($inter) > 0;
	}
}