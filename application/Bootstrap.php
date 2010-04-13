<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /**
     * Set access protected for all
     * @access protected
     */
    
    /**
     * Setup include file cache to increase performance
     *
     * @return void
     * @author Shane O’Grady
     * @link http://framework.zend.com/manual/en/zend.loader.pluginloader.html#zend.loader.pluginloader.performance.example
     */
    protected function _initFileIncludeCache()
    {
        $classFileIncCacheOptions = $this->getOption('cache');
        $classFileIncCache = $classFileIncCacheOptions['classFileIncludeCache'];

        if(file_exists($classFileIncCache)) {
            include_once $classFileIncCache;
        }
        Zend_Loader_PluginLoader::setIncludeFileCache($classFileIncCache);
    }
    
    /**
     * Autoload stuff from the default module (which is not in a `modules` subfolder in this project)
     *
     * @return Zend_Application_Module_Autoloader
     * @author Shane O’Grady
     */
    protected function _initAutoload()
    {
        $moduleLoader = new Zend_Application_Module_Autoloader(array(
            'namespace' => '',
            'basePath'  => APPLICATION_PATH));

        return $moduleLoader;
    }
    
    /**
     * Set the Doctype, etc for the views/layouts
     *
     * @return Zend_Application_Module_Autoloader
     * @author Shane O’Grady
     */
    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('HTML5');

		$view->headMeta()->setCharset('UTF-8')
						->setIndent(4);
		$view->headTitle()->setSeparator(' :: ')
						->headTitle('<changeme>')
						->setIndent(4);
    }
    
    /**
     * Initialise Doctrine ORM
     *
     * @return Doctrine_Manager
     * @access public
     * @author Shane O’Grady
     */
    public function _initDoctrine()
    {        
        // Autoload doctrine
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('sfYaml')
                   ->pushAutoloader(array('Doctrine', 'autoload'), 'sfYaml');

        $doctrineConfig = $this->getOption('doctrine');
        $manager = Doctrine_Manager::getInstance();

        // Enable Doctrine DQL callbacks, enables SoftDelete behavior
        $manager->setAttribute(Doctrine_Core::ATTR_USE_DQL_CALLBACKS, true);

        // Set models loading style (agressive/PEAR/conservative)
        $manager->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, Doctrine_Core::MODEL_LOADING_PEAR);
        // Set tables classes to be included in auto-loading
        $manager->setAttribute(Doctrine_Core::ATTR_AUTOLOAD_TABLE_CLASSES, true);

        // Enable attr validation
        $manager->setAttribute(Doctrine_Core::ATTR_VALIDATE, Doctrine_Core::VALIDATE_ALL);
        // Export attributes
        $manager->setAttribute(Doctrine_Core::ATTR_EXPORT, Doctrine_Core::EXPORT_ALL);

        // Disable attr overwrite issue
        // http://trac.doctrine-project.org/ticket/990
        $manager->setAttribute(Doctrine::ATTR_HYDRATE_OVERWRITE, false);
        
        // Allow accessor overriding
        $manager->setAttribute(Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE, true);
        
        // Set default character set and collation
        $manager->setCharset('utf8');
        $manager->setCollate('utf8_unicode_ci');
        
        // Set up APC cache if APC is enabled
        if (function_exists('apc_add')) {
            $cacheDriver = new Doctrine_Cache_Apc;
            $manager->setAttribute(Doctrine_Core::ATTR_QUERY_CACHE, $cacheDriver);
        }

        // Creating named connection
        $doctrineConfig = $this->getOption('doctrine');
        $manager->openConnection($doctrineConfig['connection_string'], 'doctrine');
        $manager->getConnection('doctrine')->setCharset('utf8');

        return $manager;
    }
    
    /**
     * Set the default translation adapter for Views and Forms
     * (This does not seem to be picked up from config file)
     *
     * @return void
     * @author Shane O’Grady
     */
    protected function _initTranslate()
	{
        $translate = $this->getResource('translate');

		Zend_Registry::set('Zend_Translate', $translate);
        Zend_Form::setDefaultTranslator($translate);
	}
    
    /**
     * Initialize the ZFDebug toolbar / Register the ZFDebug plugin with the FrontController
     * 
     * @return void
     * @author Shane O’Grady
     */
    protected function _initZFDebug()
    {
        $zfdebugConfig = $this->getOption('zfdebug');

        if ($zfdebugConfig['enabled'] != 1) {
            return;
        }

        // Ensure Doctrine connection instance is present, and fetch it
        $this->bootstrap('Doctrine');
        $doctrine = $this->getResource('Doctrine');

        // not in the .ini as not always required
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('ZFDebug');

        $options = array(
           'plugins' => array('Variables', 
                              'Danceric_Controller_Plugin_Debug_Plugin_Doctrine',
                              'File' => array('base_path' => APPLICATION_PATH),
                              'Memory', 
                              'Time',
                              'Registry', 
                              'Auth',
                              'Exception')
                        );

       # Setup the cache plugin
       if ($this->hasPluginResource('cache')) {
           $this->bootstrap('cache');
           $cache = $this->getPluginResource('cache')->getDbAdapter();
           $options['plugins']['Cache']['backend'] = $cache->getBackend();
       }

       $debug = new ZFDebug_Controller_Plugin_Debug($options);

       $this->bootstrap('frontController');
       $frontController = $this->getResource('frontController');
       $frontController->registerPlugin($debug);
    }
    
}

