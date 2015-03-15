<?php
namespace Wrep\Bundle\BugsnagBundle\Bugsnag;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The BugsnagBundle Client Loader.
 *
 * This class assists in the loading of the bugsnag Client class.
 *
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class Client
{
    protected $enabled = false;
    protected $bugsnag;

    /**
     * @param string $apiKey
     * @param Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param string|null $queue
     */
    public function __construct($apiKey, $envName, ContainerInterface $container)
    {
        if (!$apiKey) {
            return;
        }

        $this->enabled = true;
        $releaseStage = ($envName == 'prod') ? 'production' : $envName;

        // Register bugsnag
        $this->bugsnag = new \Bugsnag_Client($apiKey);
        $this->bugsnag->setReleaseStage($releaseStage);
        $this->bugsnag->setNotifyReleaseStages($container->getParameter('bugsnag.notify_stages'));
        $this->bugsnag->setProjectRoot(realpath($container->getParameter('kernel.root_dir').'/..'));
        $this->bugsnag->setBeforeNotifyFunction(function($error) use ($container) {
            // Get request if available
            $request = null;
            try {
              $request = $container->get('request');
            } catch (\Symfony\Component\DependencyInjection\Exception\InactiveScopeException $e) {}

            // Set up result array
            $metaData_symfony = null;

            // Get and add controller information, if available
            if (!is_null($request)) {
              $controller = $request->attributes->get('_controller');
              if ($controller !== null)
              {
                  $metaData_symfony = array('Controller' => $controller);
              }
            }

            // Get custom metadata
            $metaData = $container->getParameter('bugsnag.metadata');
            if (!is_array($metaData)) $metaData = array();

            // Merge metadata together
            if (!is_null($metaData_symfony)) $metaData['Symfony'] = $metaData_symfony;

            // Return our metadata to be included in the error message
            $error->setMetaData($metaData);
            return $metaData;
        });
    }

    public function notifyOnException(\Exception $e)
    {
    	if ($this->enabled) {
    		$this->bugsnag->notifyException($e);
    	}
    }

    public function notifyOnError($message)
    {
    	if ($this->enabled) {
    		$this->bugsnag->notifyError('Error', $message);
    	}
    }
}
