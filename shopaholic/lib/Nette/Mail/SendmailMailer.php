<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette\Mail
 * @version    $Id: SendmailMailer.php 355 2009-06-16 17:31:51Z david@grudl.com $
 */



require_once dirname(__FILE__) . '/../Object.php';



/**
 * Sends e-mails via the PHP internal mail() function.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Mail
 */
class SendmailMailer extends Object implements IMailer
{

	/**
	 * Sends e-mail.
	 * @param  Mail
	 * @return void
	 */
	public function send(Mail $mail)
	{
		$tmp = clone $mail;
		$tmp->setHeader('Subject', NULL);
		$tmp->setHeader('To', NULL);

		$parts = explode(Mail::EOL . Mail::EOL, $tmp->generateMessage(), 2);
		$linux = strncasecmp(PHP_OS, 'win', 3);

		Tools::tryError();
		$res = mail(
			$mail->getEncodedHeader('To'),
			$mail->getEncodedHeader('Subject'),
			$linux ? str_replace(Mail::EOL, "\n", $parts[1]) : $parts[1],
			$linux ? str_replace(Mail::EOL, "\n", $parts[0]) : $parts[0]
		);

		if (Tools::catchError($msg)) {
			throw new InvalidStateException($msg);

		} elseif (!$res) {
			throw new InvalidStateException('Unable to send email.');
		}
	}

}
