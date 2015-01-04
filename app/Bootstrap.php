<?php
namespace App;

use Phalcon\Cache\Frontend\Data,
    Phalcon\Config\Adapter\Ini,
    Phalcon\Crypt,
    Phalcon\Db\Adapter\Pdo\Mysql,
    Phalcon\DI,
    Phalcon\DiInterface,
    Phalcon\Events\Manager as EventsManager,
    Phalcon\Filter,
    Phalcon\Flash\Session,
    Phalcon\Http\Response\Cookies,
    Phalcon\Loader,
    Phalcon\Logger\Adapter\File,
    Phalcon\Mvc\Application,
    Phalcon\Mvc\Dispatcher,
    Phalcon\Mvc\Model\MetaData\Memory,
    Phalcon\Mvc\Router,
    Phalcon\Mvc\Url,
    Phalcon\Mvc\View,
    Phalcon\Security,
    Phalcon\Session\Adapter\Redis,
    Phalcon\Mvc\View\Engine\Volt;

use Slack\Client,
    Slack\Notifier,
    GetSky\Phalcon\Utils\PrettyExceptions;

use App\Library\Mail,
    App\Library\I18n,
    App\Plugins\ExceptionsPlugin;


// Define some useful constants
define('APP_DIR', __DIR__.'/');
define('BASE_DIR', dirname(__DIR__).'/');
define('PUBLIC_PATH', dirname(__DIR__).'/public/');

// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

/**
 * App project bootstrapper
 *
 * @package App
 * @author  Nathan Daly <justlikephp@gmail.com>
 * @version 1.0
 * @created 2014-11-06
 */
class Bootstrap extends Application
{
    /**
     * Dependency Injector reference
     *
     * @var DiInterface
     */
    private $di;

    /**
     * Config data storage
     *
     * @var array
     */
    private $config;

    /**
     * Bootstrap constructor - sets some defaults and stores the di interface
     *
     * @param DiInterface $di
     */
    public function __construct(DiInterface $di)
    {
        // store the dependency injector in the application
        $this->di = $di;

        // traverse the different services
        $loaders = ['config', 'log', 'errors', 'loader', 'dispatcher', 'db', 'timezone',
                    'filter', 'flash', 'crypt', 'session', 'cookie', 'cache', 'modelsMeta',
                    'url', 'router', 'view', 'mail', 'slack'];

        foreach($loaders as $service) {
            $this->$service();
        }

        // Register the app itself as a service
        $this->di->set('app', $this);

        // Set the dependency Injector
        parent::__construct($this->di);
    }

    /**
     * Set up the error and exception handling
     *
     * @return void
     */
    protected function errors()
    {
        // Set error reporting level depending on application environment
        if (isset($this->config->application->debug) && $this->config->application->debug != 0) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }

        set_exception_handler(function($e) {

            $this->di->get('log', [$e->getMessage() ."\n". $e->getTraceAsString(), 'exception.log']);

            $p = new PrettyExceptions();

            if (isset($this->config->application->debug) && $this->config->application->debug != 0) {
                $p->showBacktrace(true);
                $p->showFiles(true);
                $p->showFileFragment(true);
                $p->showApplicationDump(true);
            } else {
                $p->showBacktrace(true);
                $p->showFiles(false);
                $p->showFileFragment(false);
                $p->showApplicationDump(false);
            }

            return $p->handle($e, $this);
        });

        set_error_handler(function($errorCode, $errorMessage, $errorFile, $errorLine) {

            $p = new PrettyExceptions();
            if (isset($this->config->application->debug) && $this->config->application->debug != 0) {
                $p->showBacktrace(true);
                $p->showFiles(true);
                $p->showFileFragment(true);
                $p->showApplicationDump(true);
            } else {
                $p->showBacktrace(true);
                $p->showFiles(false);
                $p->showFileFragment(false);
                $p->showApplicationDump(false);

                $fileLogger = new \App\Logger(APP_DIR . '/../logs', array());
                $fileLogger->error(" Message: ". $errorMessage.".\r\n\nTrace: \r\n".$errorFile.'->'.$errorLine."\r\n");
            }
            return $p->handleError($errorCode, $errorMessage, $errorFile, $errorLine, $this);
        });
    }

    /**
     * Set the config service
     *
     * @return void
     */
    protected function config()
    {
        $this->config = new Ini(__DIR__ . '/config/config_'.APPLICATION_ENV.'.ini');
        $this->di->set('config', $this->config);
    }

    /**
     * Register the project autoloaders
     *
     * @return void
     */
    protected function loader()
    {
        $loader = new Loader();
        $loader->registerNamespaces(array(
            'App\Controllers' => APP_DIR . $this->config->application->controllersDir,
            'App\Models'      => APP_DIR . $this->config->application->modelsDir,
            'App\Forms'       => APP_DIR . $this->config->application->formsDir,
            'App\Plugins'     => APP_DIR . $this->config->application->pluginsDir,
            'App\Library'     => APP_DIR . $this->config->application->libraryDir,
        ));

        $loader->register();
    }

    /**
     * Adjust the regular dispatcher to add a namespace
     *
     * @return void
     */
    protected function dispatcher()
    {
        $this->di->set('dispatcher', function() {

            $dispatcher = new Dispatcher();
            $dispatcher->setDefaultNamespace('App\Controllers');

            $eventsManager = new EventsManager();
            $eventsManager->attach('dispatch:beforeException', new ExceptionsPlugin());

            $dispatcher->setEventsManager($eventsManager);

            return $dispatcher;
        });
    }

    /**
     * Set the timezone
     *
     * @return void
     */
    protected function timezone()
    {
        date_default_timezone_set($this->config->application->timezone);
    }

    /**
     * Set the language service
     *
     * @return void
     */
    protected function i18n()
    {
        $this->di->setShared('i18n', function() {
            return I18n::instance();
        });
    }

    /**
     * Set the security service
     *
     * @return void
     */
    protected function security()
    {
        $config = $this->config;
        $this->di->set('security', function() use ($config) {
            $security = new Security();
            $security->setWorkFactor(12);
            $security->setDefaultHash($config->security->key);
            return $security;
        });
    }

    /**
     * Set the crypt service
     *
     * @return void
     */
    protected function crypt()
    {
        $config = $this->config;
        $this->di->set('crypt', function() use ($config) {
            $crypt = new Crypt();
            $crypt->setKey($config->crypt->key);
            $crypt->setPadding(Crypt::PADDING_ZERO);
            return $crypt;
        });
    }

    /**
     * Set the filter service
     *
     * @return void
     */
    protected function filter()
    {
        $this->di->set('filter', function() {
            $filter = new Filter();
            return $filter;
        });
    }

    /**
     * Set the cookie service
     *
     * @return void
     */
    protected function cookie()
    {
        $this->di->set('cookies', function() {
            return new Cookies();
        });
    }

    /**
     * Set the database service
     *
     * @return void
     */
    protected function db()
    {
        $config = $this->config;
        $this->di->set('db', function() use ($config) {
            return new Mysql([
                'host'     => $config->database->host,
                'username' => $config->database->username,
                'password' => $config->database->password,
                'dbname'   => $config->database->dbname,
                'options'  => [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                ]
            ]);
        });
    }

    /**
     * Set the metadata adapter
     *
     * @return void
     */
    protected function modelsMeta()
    {
        $config = $this->config;
        $this->di->set('modelsMetadata', function () use ($config) {
            return new Memory(['metaDataDir' => $config->application->cacheDir . 'metaData/']);
        });
    }

    /**
     * Set the flash service
     *
     * @return void
     */
    protected function flash()
    {
        $this->di->set('flash', function() {
            return new Session([
                'warning'     => 'alert alert-warning',
                'notice'      => 'alert alert-info',
                'success'     => 'alert alert-success',
                'error'       => 'alert alert-error',
                'dismissable' => 'alert alert-dismissable'
            ]);
        });
    }

    /**
     * Set the session service
     *
     * @return void
     */
    protected function session()
    {
        $config = $this->getDI()->get('config');
        $this->di->set('session', function() use ($config) {

            // less typing this way
            $s = $config->session;
            $s->weight = (isset($s->weight) ? $s->weight : 1);
            $s->database = (isset($s->database) ? $s->database: 1);

            // init the session using redis
            $session = new Redis(['path' => 'tcp://' . $s->host . ':' .$s->port . '?weight=' . $s->weight . '&database=' .$s->database]);
            $session->start();

            return $session;
        });
    }

    /**
     * Set the cache service
     *
     * @return void
     */
    protected function cache()
    {
        $config = $this->config;
        $this->di->set('cache', function() use ($config) {

            //Connect to redis
            $redis = new \Redis();
            $redis->connect($config->cache->host, $config->cache->port);
            $redis->select($config->cache->database);

            $frontCache = new Data(["lifetime" => $config->cache->lifetime]);

            $cache = new \Phalcon\Cache\Backend\Redis($frontCache, ['redis' => $redis]);

            return $cache;
        });
    }

    /**
     * Set the url service
     *
     * @return void
     */
    protected function url()
    {
        $config = $this->config;
        $this->di->set('url', function() use ($config) {
            $url = new Url();
            $url->setBaseUri($config->application->baseUri);
            $url->setStaticBaseUri($config->application->staticUri);
            return $url;
        });
    }

    /**
     * Set the static router service
     *
     * @return void
     */
    protected function router()
    {
        $this->di->set('router', function() {
            return require APP_DIR . '/config/routes.php';
        });
    }

    /**
     * Set the view service
     *
     * @return void
     */
    protected function view()
    {
        $config = $this->config;
        $this->di->set('view', function () use ($config) {

            $view = new View();
            $view->setViewsDir(APP_DIR . $config->application->viewsDir);
            $view->registerEngines(array(
                '.volt' => function ($view, $di) use ($config) {
                    $volt = new Volt($view, $di);

                    $volt->setOptions(array(
                        'compiledPath' => APP_DIR . $config->application->cacheDir . '/volt/',
                        'compiledSeparator' => '_'
                    ));

                    return $volt;
                },

                '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
            ));

            return $view;
        }, true);
    }

    /**
     * Add the swiftmailer service to the system
     */
    protected function mail()
    {
        $config = $this->config;
        $this->di->set('mail', function() use ($config) {
            require_once(BASE_DIR.'/vendor/swiftmailer/swiftmailer/lib/swift_required.php');
            return new Mail();
        });
    }

    /**
     * Log message into file
     *
     * @return void
     */
    public function log()
    {
        $config = $this->config;
        $this->di->set('log', function($messages, $file = null) use ($config) {

            $file = (is_null($file) ? 'logs-'.date('Y-m').'.log' : $file);

            $logger = new File(APP_DIR . $config->application->logDir . $file, ['mode' => 'a+']);

            if (is_array($messages) || $messages instanceof \Countable) {

                foreach ($messages as $key => $message) {

                    if (in_array($key, ['alert', 'debug', 'error', 'info', 'notice', 'warning'])) {
                        $logger->$key($message);

                    } else {
                        $logger->log('info', $message);
                    }
                }
            } else {
                $logger->log('info', $messages);
            }
        });
    }

    /**
     * set the Slack service
     * Source: https://github.com/polem/slack-notifier
     */
    public function slack()
    {
        $config = $this->config;
        $this->di->set('slack', function () use ($config) {

            $client = new Client($config->slack->username, $config->slack->token);
            $slack = new Notifier($client);

            return $slack;
        });
    }
}