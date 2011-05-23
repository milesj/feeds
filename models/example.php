<?php
/**
 * Example Model
 * 
 * An example model of how to use a model to relate to a single feed.
 *
 * @author      Miles Johnson - http://milesj.me
 * @copyright   Copyright 2006-2011, Miles Johnson, Inc.
 * @license     http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/code/cakephp/feeds
 */

class Example extends Model {

	/**
	 * No database table needed.
	 *
	 * @access public
	 * @var boolean
	 */
	public $useTable = false;

	/**
	 * Use the feeds datasource.
	 *
	 * @access public
	 * @var boolean
	 */
	public $useDbConfig = 'feeds';

	/**
	 * Hard code the RSS feed into the model.
	 *
	 * @access public
	 * @var string
	 */
	public $feedUrls = 'http://feeds.feedburner.com/milesj';

}