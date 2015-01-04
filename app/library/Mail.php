<?php

namespace App\Library;

use Phalcon\Exception;
use Phalcon\Mvc\User\Component;
use Phalcon\Mvc\View;

/**
 * Mail wrapper that brings together Phalcon and Swiftmailer
 *
 * @package App
 * @author Rian Orie <rian.orie@gmail.com>
 * @created 2014-11-02
 * @version 1.0
 */
class Mail Extends Component
{
	/**
	 * Storage for the transport adapter
	 *
	 * @var \Swift_SmtpTransport
	 */
	protected $transport;

	/**
	 * Storage for the recipient
	 * @var string|array
	 */
	protected $to;

	/**
	 * Storage for the subject
	 * @var string
	 */
	protected $subject;

	/**
	 * Storage for the template name
	 * @var string
	 */
	protected $template;

	/**
	 * Storage for the parameters to use in the template
	 * @var array
	 */
	protected $params = [];

	/**
	 * Set the recipient
	 *
	 * @param string|array $to Either the email address, or an associative array with [email => name]
	 *
	 * @throws Exception
	 */
	public function setTo($to)
	{
		// validate the given email address
		if ( ! is_array($to)) {
			if ( ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
				throw new Exception('Invalid email address given to mailer.');
			}

			// validate it even if its in an array
		} else {
			$email = array_keys($to);
			if ( ! filter_var($email[0], FILTER_VALIDATE_EMAIL)) {
				throw new Exception('Invalid email address given to mailer.');
			}
		}

		$this->to = $to;
	}

	/**
	 * Set the email subject
	 *
	 * @param string $subject
	 *
	 * @throws Exception
	 */
	public function setSubject($subject)
	{
		if ( ! is_string($subject) || empty($subject)) {
			throw new Exception('Mail subject should not be empty.');
		}

		$this->subject = $subject;
	}

	/**
	 * Set the email template to use
	 *
	 * @param string $template
	 *
	 * @throws Exception
	 */
	public function setTemplate($template)
	{
		if ( ! is_string($template) || empty($template)) {
			throw new Exception('Mail template name should not be empty.');
		}

		$emailDir = APP_PATH.$this->config->application->viewsDir.'/email';
		if ( ! file_exists($emailDir.$template.'.phtml')) {
			throw new Exception('Email template '.$template.' not found!');
		}

		$this->template = $template;
	}

	/**
	 * Set a single parameter
	 *
	 * @param mixed $key
	 * @param mixed $value
	 *
	 * @throws Exception
	 */
	public function setParam($key, $value)
	{
		if ( ! is_scalar($key)) {
			throw new Exception('Key for setParam should be scalar.');
		}

		$this->params[$key] = $value;
	}

	/**
	 * Add params to the message
	 *
	 * @param array $params
	 */
	public function setParams(array $params)
	{
		$this->params = array_merge($this->params, $params);
	}

	/**
	 * Send an email
	 *
	 * @param bool $flush Flush the email variables after sending
	 *
	 * @return bool
	 */
	public function send($flush = true)
	{
		// config settings
		$mailSettings = $this->config->mail;

		// Load the template contents
		$template = $this->getTemplate($this->template, $this->params);

		// Create the message
		$message = \Swift_Message::newInstance()
		                         ->setSubject($this->subject)
		                         ->setTo($this->to)
		                         ->setFrom([$mailSettings->fromEmail => $mailSettings->fromName])
		                         ->setBody($template, 'text/html');

		// set up the transport for delivery
		if ( ! $this->transport) {
			$this->transport = \Swift_SmtpTransport::newInstance('localhost', 1025);
		}

		// Create the Mailer using your created Transport
		$mailer = \Swift_Mailer::newInstance($this->transport);

		// store the state, we need it later. (state is actually the number of successfully sent emails)
		$state = $mailer->send($message);

		if ($state > 0 && $flush) {
			$this->to = null;
			$this->subject = null;
			$this->params = [];
			$this->template = null;
		}

		// turn the state into a boolean
		return ($state > 0);
	}

	/**
	 * Load a template from the system
	 *
	 * @param string $name The email template to use
	 * @param array $params Parameters to replace in the message
	 *
	 * @return mixed
	 */
	private function getTemplate($name, $params)
	{
		// Merge some bases into the parameters, these will
		// always be available.
		$params = array_merge(['baseUri' => $this->config->application->baseUri], $params);

		// Load up a view handler and feed it the things we need
		$view = new View\Simple();
		$view->setViewsDir(APP_PATH.$this->config->application->viewsDir);
		$view->setVars($params);

		// Load up the layout
		$layout = new View\Simple();
		$layout->setViewsDir(APP_PATH.$this->config->application->viewsDir);
		$layout->setVar('content', $view->render('email/'.$name));

		// and finally return the contents
		return $layout->render('layouts/email');
	}
}