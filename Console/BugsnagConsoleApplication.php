<?php
namespace Wrep\Bundle\BugsnagBundle\Console;

use Wrep\Bundle\BugsnagBundle\Bugsnag\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BugsnagConsoleApplication extends Application
{
	private $client;

	public function __construct(KernelInterface $kernel)
	{
		parent::__construct($kernel);

		// Boot kernel now
		$kernel->boot();

		// Get container
		$container = $kernel->getContainer();

		// Figure out environment
		$envName = $container->getParameter('kernel.environment');

		// Setup Bugsnag to handle our errors
		$this->client = new Client($container->getParameter('bugsnag.api_key'), $envName, $container);

		// Attach to support reporting PHP errors
		set_error_handler(array($this->client, 'notifyOnError'));
	}

	public function renderException($e, $output)
	{
		// Send exception to Bugsnag
		$this->client->notifyOnException($e);

		// Call parent function
		parent::renderException($e, $output);
	}
}
