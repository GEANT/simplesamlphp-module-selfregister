<?php

class sspmod_selfregister_XHTML_Mailer extends \SimpleSAML\Utils\EMail {

	private $to = NULL;
	private $cc = NULL;
	private $body = NULL;
	private $from = NULL;
	private $replyto = NULL;
	private $subject = NULL;
	private $headers = array();
	private $tp = NULL;
	private $mailer;

	/**
	 * Constructor
	 */
	function __construct($to, $subject, $from = NULL, $cc = NULL, $replyto = NULL) {

		$this->to = $to;
		$this->cc = $cc;
		$this->from = $from;
		$this->replyto = $replyto;
		$this->subject = $subject;
	}


	public function setTemplate($template) {
		$this->tp = $template;
	}


	public function generateBody($tpl = null){
		ob_start();
		$this->tp->show();
		$bodyText = ob_get_contents();
		ob_end_clean();
		$this->body = $bodyText;
	}


	private function getHTML($body) {
		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>simpleSAMLphp Email report</title>
	<style type="text/css">
pre, div.box {
	margin: .4em 2em .4em 1em;
	padding: 4px;

}
pre {
	background: #eee;
	border: 1px solid #aaa;
}
	</style>
</head>
<body>
<div class="container" style="background: #fafafa; border: 1px solid #eee; margin: 2em; padding: .6em;">
' . $body . '
</div>
</body>
</html>';
	}


	public function buildMessage($random_hash = 'asdf1234'){

		if(!$this->body){
			$this->generateBody();
		}

		$message = '
--simplesamlphp-' . $random_hash . '
Content-Type: text/plain; charset="utf-8"
Content-Transfer-Encoding: 8bit

' . strip_tags(html_entity_decode($this->body)) . '

--simplesamlphp-' . $random_hash . '
Content-Type: text/html; charset="utf-8"
Content-Transfer-Encoding: 8bit

' . $this->getHTML($this->body) . '

--simplesamlphp-' . $random_hash . '--
';
		return $message;
	}


	function send($plainTextOnly = false) {
		if ($this->to == NULL)
			throw new Exception('EMail field [to] is required and not set.');
		if ($this->subject == NULL)
			throw new Exception('EMail field [subject] is required and not set.');
		// if ($this->body == NULL) throw new Exception('EMail field [body] is required and not set.');
		$random_hash = SimpleSAML_Utilities::stringToHex(
			SimpleSAML_Utilities::generateRandomBytes(16));
		$this->generateBody();

		if (isset($this->from))
			$this->headers[]= 'From: ' . $this->from;
		if (isset($this->replyto))
			$this->headers[]= 'Reply-To: ' . $this->replyto;

		$this->initMailer();

		$this->mailer->Body = $this->body;
		$this->mailer->AltBody = $this->body;
		$mail_sent = $this->mailer->send();
		//$mail_sent = @mail($this->to, $this->subject, $message, $headers);
		SimpleSAML_Logger::debug('Email: Sending e-mail to [' . $this->to . '] : ' . ($mail_sent ? 'OK' : 'Failed'));
		if (!$mail_sent) throw new Exception('Error when sending e-mail');
	}

	private function initMailer() {
		$config = \SimpleSAML\Configuration::getInstance()->getArrayize('mail.transport.options');
		$this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
		$this->mailer->Host = $config['host'];
		$this->mailer->Port = $config['port'];
		if ($config['auth']) {
			$this->mailer->SMTPAuth = true;
			$this->mailer->Username = $config['username'];
			$this->mailer->Password = $config['password'];
		}
		$this->mailer->SMTPSecure = $config['security'];
		$this->mailer->SMTPOptions = $config['options'];

		$this->mailer->isSMTP();
		$this->mailer->Subject = $this->subject;
		$this->mailer->setFrom($this->from);
		$this->mailer->addAddress($this->to);
		foreach ($this->headers as $header) {
			$header = explode(':', $header, 2);
			$this->mailer->addCustomHeader($header[0], $header[1]);
		}
	}
}// end sspmod_selfregister_XHTML_Mailer
