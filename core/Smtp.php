<?php
namespace SakuraPanel;

class Smtp {
	
    public $smtp_port;
    public $time_out;
    public $host_name;
    public $log_file;
    public $relay_host;
    public $debug;
    public $auth;
    public $user;
    public $pass;
	
    private $sock;
	
    public function __construct($relay_host = "", $smtp_port = 25, $auth = false, $user, $pass)
	{
        $this->debug      = false;
        $this->smtp_port  = $smtp_port;
        $this->relay_host = $relay_host;
        $this->time_out   = 30;
        $this->auth       = $auth;
        $this->user       = $user;
        $this->pass       = $pass;
        $this->host_name  = "localhost";
        $this->log_file   = "";
        $this->sock       = false;
    }
	
    public function sendMail($to, $from, $subject = "", $body = "", $mailtype, $cc = "", $bcc = "", $additional_headers = "")
	{
        $header    = "";
        $mail_from = $this->getAddress($this->stripComment($from));
        $body      = mb_ereg_replace("(^|(\r\n))(\\.)", "\\1.\\3", $body);
        $header   .= "MIME-Version:1.0\r\n";
       
		if ($mailtype == "HTML") {
            $header .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        }
        
		$header .= "To: {$to}\r\n";
        
		if ($cc != "") {
            $header .= "Cc: {$cc}\r\n";
        }
        
		$header .= "From: {$from}\r\n";
        $header .= "Subject: {$subject}\r\n";
        $header .= $additional_headers;
        $header .= "Date: " . date("r") . "\r\n";
        $header .= "X-Mailer: By (SakuraPanel)\r\n";
        
		list($msec, $sec) = explode(" ", microtime());
        $header .= "Message-ID: <" . date("YmdHis", $sec) . "." . ($msec * 1000000) . ".{$mail_from}>\r\n";
        $TO = explode(",", $this->stripComment($to));
        
		if ($cc != "") {
            $TO = array_merge($TO, explode(",", $this->stripComment($cc)));
        }
        if ($bcc != "") {
            $TO = array_merge($TO, explode(",", $this->stripComment($bcc)));
        }
		
        $sent = true;
		
        foreach ($TO as $rcpt_to) {
            $rcpt_to = $this->getAddress($rcpt_to);
            if (!$this->smtpSockopen($rcpt_to)) {
                $this->logWrite("Error: Cannot send email to {$rcpt_to}\n");
                $sent = false;
                continue;
            }
            if ($this->smtpSend($this->host_name, $mail_from, $rcpt_to, $header, $body)) {
                $this->logWrite("E-mail has been sent to <{$rcpt_to}>\n");
            } else {
                $this->logWrite("Error: Cannot send email to <{$rcpt_to}>\n");
                $sent = false;
            }
            fclose($this->sock);
            $this->logWrite("Disconnected from remote host\n");
        }
        return $sent;
    }
	
    private function smtpSend($helo, $from, $to, $header, $body = "")
	{
        if (!$this->smtpPutcmd("HELO", $helo)) {
            return $this->smtpError("sending HELO command");
        }
        if ($this->auth) {
            if (!$this->smtpPutcmd("AUTH LOGIN", base64_encode($this->user))) {
                return $this->smtpError("sending HELO command");
            }
            if (!$this->smtpPutcmd("", base64_encode($this->pass))) {
                return $this->smtpError("sending HELO command");
            }
        }
        if (!$this->smtpPutcmd("MAIL", "FROM:<{$from}>")) {
            return $this->smtpError("sending MAIL FROM command");
        }
        if (!$this->smtpPutcmd("RCPT", "TO:<{$to}>")) {
            return $this->smtpError("sending RCPT TO command");
        }
        if (!$this->smtpPutcmd("DATA")) {
            return $this->smtpError("sending DATA command");
        }
        if (!$this->smtpMessage($header, $body)) {
            return $this->smtpError("sending message");
        }
        if (!$this->smtpEom()) {
            return $this->smtpError("sending <CR><LF>.<CR><LF> [EOM]");
        }
        if (!$this->smtpPutcmd("QUIT")) {
            return $this->smtpError("sending QUIT command");
        }
        return true;
    }
	
    private function smtpSockopen($address)
	{
        if ($this->relay_host == "") {
            return $this->smtpSockopenMx($address);
        } else {
            return $this->smtpSockopenRelay();
        }
    }
	
    private function smtpSockopenRelay()
	{
        $this->logWrite("Trying to {$this->relay_host}:{$this->smtp_port}\n");
        $this->sock = @fsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);
        if (!($this->sock && $this->smtpOk())) {
            $this->logWrite("Error: Cannot connenct to relay host {$this->relay_host}\n");
            $this->logWrite("Error: {$errstr} ({$errno})\n");
            return false;
        }
        $this->logWrite("Connected to relay host {$this->relay_host}\n");
        return true;;
    }
	
    private function smtpSockopenMx($address)
	{
        $domain = ereg_replace("^.+@([^@]+)$", "\\1", $address);
        if (!@getmxrr($domain, $MXHOSTS)) {
            $this->logWrite("Error: Cannot resolve MX \"{$domain}\"\n");
            return false;
        }
        foreach ($MXHOSTS as $host) {
            $this->logWrite("Trying to {$host}:{$this->smtp_port}\n");
            $this->sock = @fsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);
            if (!($this->sock && $this->smtpOk())) {
                $this->logWrite("Warning: Cannot connect to mx host {$host}\n");
                $this->logWrite("Error: {$errstr} ({$errno})\n");
                continue;
            }
            $this->logWrite("Connected to mx host {$host}\n");
            return true;
        }
        $this->logWrite("Error: Cannot connect to any mx hosts (" . implode(", ", $MXHOSTS) . ")\n");
        return false;
    }
	
    private function smtpMessage($header, $body)
	{
        fputs($this->sock, "{$header}\r\n" . $body);
        $this->smtpDebug("> " . str_replace("\r\n", "\n> ", "{$header}\n> {$body}\n> "));
        return true;
    }
	
    private function smtpEom()
	{
        fputs($this->sock, "\r\n.\r\n");
        $this->smtpDebug(". [EOM]\n");
        return $this->smtpOk();
    }
	
    private function smtpOk()
	{
        $response = str_replace("\r\n", "", fgets($this->sock, 512));
        $this->smtpDebug("{$response}\n");
        if (!mb_ereg("^[23]", $response)) {
            fputs($this->sock, "QUIT\r\n");
            fgets($this->sock, 512);
            $this->logWrite("Error: Remote host returned \"{$response}\"\n");
            return false;
        }
        return true;
    }
	
    private function smtpPutcmd($cmd, $arg = "")
	{
        if ($arg != "") {
			$cmd = empty($cmd) ? $arg : "{$cmd} {$arg}";
        }
        fputs($this->sock, "{$cmd}\r\n");
        $this->smtpDebug("> {$cmd}\n");
        return $this->smtpOk();
    }
	
    private function smtpError($string)
	{
        $this->logWrite("Error: Error occurred while {$string}.\n");
        return false;
    }
	
    private function logWrite($message)
	{
        $this->smtpDebug($message);
        if ($this->log_file == "") {
            return true;
        }
        $message = date("M d H:i:s ") . get_current_user() . "[" . getmypid() . "]: {$message}";
        if (!@file_exists($this->log_file) || !($fp = @fopen($this->log_file, "a"))) {
            $this->smtpDebug("Warning: Cannot open log file \"{$this->log_file}\"\n");
            return false;
        }
        flock($fp, LOCK_EX);
        fputs($fp, $message);
        fclose($fp);
        return true;
    }
	
    private function stripComment($address)
	{
        $comment = "\\([^()]*\\)";
        while (mb_ereg($comment, $address)) {
            $address = mb_ereg_replace($comment, "", $address);
        }
        return $address;
    }
	
    private function getAddress($address)
	{
        $address = mb_ereg_replace("([ \t\r\n])+", "", $address);
        $address = mb_ereg_replace("^.*<(.+)>.*$", "\\1", $address);
        return $address;
    }
	
    private function smtpDebug($message)
	{
        if ($this->debug) {
            echo $message;
        }
    }
	
    private function getAttachType($image_tag)
    {
        $filedata     = array();
        $img_file_con = fopen($image_tag, "r");
        unset($image_data);
        
		while ($tem_buffer = addslashes(fread($img_file_con, filesize($image_tag)))) {
			$image_data .= $tem_buffer;
		}
        fclose($img_file_con);
        
		$filedata['context']  = $image_data;
        $filedata['filename'] = basename($image_tag);
        $extension            = substr($image_tag, strrpos($image_tag, "."), strlen($image_tag) - strrpos($image_tag, "."));
		
		$extensions = Array(
			".gif"  => "image/gif",
			".gz"   => "application/x-gzip",
			".htm"  => "text/html",
			".html" => "text/html",
			".jpg"  => "image/jpeg",
			".png"  => "image/png",
			".bmp"  => "image/bmp",
			".gif"  => "image/gif",
			".tar"  => "application/x-tar",
			".txt"  => "text/plain",
			".zip"  => "application/zip"
		);
		
		$filedata['type'] = $extensions[$extension] ?? "application/octet-stream";
        
		return $filedata;
    }
}
