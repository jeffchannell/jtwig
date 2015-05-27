<?php
/* 
 * Copyright (C) 2015 Jeff Channell <me@jeffchannell.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

defined('_JEXEC') or die;

class PlgSystemJtwig extends JPlugin
{
	/**
	 * CMS Application
	 * @var JApplicationCms
	 */
	protected $app;
	
	/**
	 * CMS Dispatcher
	 * @var JDispatcher
	 */
	protected $dispatcher;
	
	/**
	 * Template data
	 * @var object
	 */
	static protected $data;
	
	/**
	 * Override constructor to initialize Twig
	 * 
	 * @param type $subject
	 * @param type $config
	 */
	public function __construct(&$subject, $config)
	{
		// run parent
		parent::__construct($subject, $config);
		// import Twig plugins
		JPluginHelper::importPlugin('twig');
		// set application
		$this->app = JFactory::getApplication();
		// get dispatcher
		$this->dispatcher = JDispatcher::getInstance();
		// load language
		JFactory::getLanguage()->load('plg_system_jtwig.sys', JPATH_ADMINISTRATOR);
		// set data
		if (!is_object(static::$data))
		{
			$this->resetData();
		}
		// load Twig
		if (!class_exists('Twig_Autoloader'))
		{
			require_once __DIR__ . '/Twig/Autoloader.php';
		}
		Twig_Autoloader::register();
	}
	
	/**
	 * onAfterRender event
	 */
	public function onAfterRender()
	{
		// check if there should be a full-page render
		if (!(($this->app->isSite() && $this->params->get('process_site', true)) || ($this->app->isAdmin() && $this->params->get('process_admin', false))))
		{
			return;
		}
		// get the response first
		$before = JResponse::getBody();
		// try to render the response
		try
		{
			$after = $this->renderStringWithTwig($before, $this->getData());
		}
		catch (Exception $ex)
		{
			$after = "$before";
			// in debug mode, send the error as the response
			if (defined('JDEBUG') && JDEBUG)
			{
				$after = $ex->getMessage();
			}
		}
		// set response body
		JResponse::setBody($after);
	}
	
	/**
	 * Reset the template data to the defaults
	 * 
	 */
	protected function resetData()
	{
		$data = new stdClass();
		$this->dispatcher->trigger('onTwigResetData', array(&$data));
		$data->user = JFactory::getUser();
		$data->joomla = new JConfig;
		static::$data = $data;
	}
	
	/**
	 * Get the template data
	 * 
	 * @return type
	 */
	protected function getData()
	{
		return static::$data;
	}
	
	/**
	 * Render a template with Twig
	 * 
	 * @param string $string
	 * @param mixed<object|array> $data
	 * @return string
	 */
	protected function renderStringWithTwig($string, $data = array())
	{
		$loader = new Twig_Loader_Array(array('index' => "$string"));
		$options = array();
		if (defined('JDEBUG') && JDEBUG)
		{
			$options['debug'] = true;
		}
		$this->dispatcher->trigger('onTwigBeforeCreate', array(&$loader, &$options));
		$twig = new Twig_Environment($loader, $options);
		$this->addTwigExtensions($twig);
		$this->validateTwigData($data);
		try
		{
			$this->dispatcher->trigger('onTwigBeforeRender', array(&$twig, &$data));
			$return = $twig->render('index', $data);
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
		return $return;
	}
	
	/**
	 * Ensure data is a valid type
	 * @param mixed<object|array> $data
	 */
	protected function validateTwigData(&$data)
	{
		if (!is_array($data) && !is_object($data))
		{
			$json = json_decode($data);
			if (!is_object($json))
			{
				$json = array();
			}
			$data = $json;
		}
		$data = (array) $data;
	}
	
	/**
	 * Add extensions to Twig
	 * 
	 * @param Twig_Environment $twig
	 */
	protected function addTwigExtensions(Twig_Environment &$twig)
	{
		$twig->addExtension(new Twig_Extension_Escaper('html'));
		$twig->addExtension(new Twig_Extension_Optimizer(Twig_NodeVisitor_Optimizer::OPTIMIZE_ALL));
		if (defined('JDEBUG') && JDEBUG)
		{
			$twig->addExtension(new Twig_Extension_Debug());
		}
		// easy core wrappers, see Twig docs for further details
		$twig->addFunction(new Twig_SimpleFunction('JRoute', array('JRoute', '_')));
		$twig->addFunction(new Twig_SimpleFunction('JText', array('JText', '_')));
		$twig->addFunction(new Twig_SimpleFunction('Jsprintf', array('JText', 'sprintf')));
	}
}
