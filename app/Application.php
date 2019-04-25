<?php
/**
 * Created by PhpStorm.
 * User: igorbulakh
 * Date: 05.12.2017
 * Time: 16:41
 */

namespace NTSchool\Phpblog;

use NTSchool\Phpblog\Controller\BaseController;
use NTSchool\Phpblog\Controller\PageController;
use NTSchool\Phpblog\Core\ErrorHandler;
use NTSchool\Phpblog\Core\Exceptions\Error404;
use NTSchool\Phpblog\Core\Exceptions\Fatal;
use NTSchool\Phpblog\Core\Logger;
use NTSchool\Phpblog\Core\providers\ModelProvider;
use NTSchool\Phpblog\Core\providers\SessionProvider;
use NTSchool\Phpblog\Core\providers\UserProvider;
use NTSchool\Phpblog\Core\ServiceContainer;
use NTSchool\Phpblog\Core\Http\Request;
use NTSchool\Phpblog\Core\Http\Response;
use Symfony\Component\Dotenv\Dotenv;


class Application
{
    /**
     * @var string
     */
    public $currentController;

    /**
     * @var string
     */
    public $currentAction;

    /**
     * @var ServiceContainer
     */
    protected $container;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * Application constructor.
     *
     * @param \NTSchool\Phpblog\Core\ServiceContainer|null $container
     */
    public function __construct(ServiceContainer $container = null)
    {
        $this->enableErrorsHandling();
        $this->loadDotEnv();

        if($container === null) {
            $this->container = new ServiceContainer();
        }else {
            $this->container = $container;
        }

        $this->initRequest();

        (new ModelProvider())->register($this->container);
        (new UserProvider())->register($this->container);
        (new SessionProvider())->register($this->container);

        $this->response = new Response();
        $this->parseUrl();
    }

    //TODO: Доделать обработчики ошибок


    /**
     *
     */
    public function run()
    {
        try {
            $session = $this->container->get('http.session');
            $session->start()->initialize();

            $controller = new $this->currentController($this->request, $this->response, $this->container);
            $action = $this->currentAction;

            $controller->$action();

            $session->save();

            $this->response->setContent($controller->getFullTemplate());
            $this->response->send();
        }catch(\PDOException $e) {
            (new ErrorHandler(new BaseController($this->request, $this->response, $this->container), new Logger('critical', 'critical'), $this->response, DEV_MODE))->handle($e, 'Oooops... Something went wrong!');
        }catch(Error404 $e) {
            (new ErrorHandler(new BaseController($this->request, $this->response, $this->container), new Logger('Error404', 'err404'), $this->response, DEV_MODE))->handle($e, 'Page not found!');
        }catch(Fatal $e) {
            (new ErrorHandler(new BaseController($this->request, $this->response, $this->container), new Logger('Fatal Error', LOG_DIR), $this->response, DEV_MODE))->handle($e, 'Fatal Error');
        }
    }

    /**
     *
     */
    private function parseUrl()
    {
        $arr = $this->getUriAsArr();
        $this->currentController = $this->getController($arr);
        $this->currentAction = $this->getAction($arr);
    }

    /**
     * @return array|mixed|null
     */
    private function getUriAsArr()
    {
        $uri = $this->request->server()->get('REQUEST_URI');

        $uri = explode('/', $uri);

        $end = count($uri) - 1;

        unset($uri[0]);

        if($uri[$end] === '') {
            unset($uri[$end]);
        }

        $uri = array_values($uri);

        return $uri;
    }

    /**
     * @param array $uri
     *
     * @return string
     * @throws \NTSchool\Phpblog\Core\Exceptions\Error404
     */
    private function getController(array $uri)
    {
        if(isset($uri[0]) && $uri[0] !== '') {
            $controller = trim('NTSchool\Phpblog\Controller\\' . ucfirst($uri[0]) . 'Controller');
            if(!file_exists('app/Controller/' . ucfirst($uri[0]) . 'Controller.php')) {
                throw new Error404('Страница не найдейна');
            }
        }

        return $controller ?? 'NTSchool\Phpblog\Controller\PostController';
    }


    /**
     * @param array $uri
     *
     * @return string
     */
    private function getAction(array $uri)
    {
        if(isset($uri[1]) && $uri[1] !== '') {
            $parts = explode('-', $uri[1]);
            if(!is_numeric($uri[1])) {
                for($i = 1; $i < count($parts); $i++) {
                    if(!isset($parts[$i])) {
                        $parts = $parts[0];
                    }
                    $parts[$i] = ucfirst($parts[$i]);
                }
                $parts = implode('', $parts);

                $action = trim($parts) . 'Action';

                $id = $uri[2] ?? null;
            }else {
                $action = 'oneAction';
                $id = $parts[0];
            }

            $this->setIdFromUri($id);
        }

        return $action ?? 'indexAction';
    }

    /**
     * @param $id
     */
    private function setIdFromUri($id)
    {
        if($id) {
            $this->request->get()->set('id', $id);
        }
    }

    /**
     *
     */
    private function initRequest()
    {
        $this->request = new Request($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES);
    }

    /**
     *
     */
    protected function loadDotEnv()
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . '/../.env');
    }

    /**
     *
     */
    public function enableErrorsHandling()
    {
        set_exception_handler(function($e) {
            $controller = new PageController($this->request, $this->response, $this->container);
            $controller->error($e);
            $this->response->setContent($controller->getFullTemplate());
            $this->response->send();
        });
    }
}