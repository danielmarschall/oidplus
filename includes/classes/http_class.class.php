<?php

/*
 * This file includes:
 *
 * 1. PHP HTTP protocol client: HTTP client to access Web site pages
 *    by Manuel Lemos
 *    http://www.phpclasses.org/httpclient
 *    License: BSD License
 *    with some small fixes by Daniel Marschall / ViaThinkSoft
 *
 *    http.php
 *        @(#) $Header: /opt2/ena/metal/http/http.php,v 1.94 2016/05/03 02:07:04 mlemos Exp $
 *        Modified by Daniel Marschall, ViaThinkSoft, Revision 2020-09-12
 *
 *
 * 2. Simple Authentication and Security Layer client
 *    (Included because of dependency)
 *    by Manuel Lemos
 *    http://www.phpclasses.org/sasl
 *    License: BSD License
 *
 *    sasl.php
 *        @(#) $Id: sasl.php,v 1.11 2005/10/31 18:43:27 mlemos Exp $
 *    basic_sasl_client.php
 *        @(#) $Id: basic_sasl_client.php,v 1.1 2004/11/17 08:01:23 mlemos Exp $
 *    cram_md5_sasl_client.php
 *        @(#) $Id: cram_md5_sasl_client.php,v 1.3 2004/11/17 08:00:37 mlemos Exp $
 *    digest_sasl_client.php
 *        @(#) $Id: digest_sasl_client.php,v 1.1 2005/10/27 05:24:15 mlemos Exp $
 *    login_sasl_client.php
 *        @(#) $Id: login_sasl_client.php,v 1.2 2004/11/17 08:00:37 mlemos Exp $
 *    ntlm_sasl_client.php
 *        @(#) $Id: ntlm_sasl_client.php,v 1.3 2004/11/17 08:00:37 mlemos Exp $
 *    plain_sasl_client.php
 *        @(#) $Id: plain_sasl_client.php,v 1.2 2004/11/17 08:00:37 mlemos Exp $
 */

// =============================================================================

/*
 * basic_sasl_client.php
 *
 * @(#) $Id: basic_sasl_client.php,v 1.1 2004/11/17 08:01:23 mlemos Exp $
 *
 */

define("SASL_BASIC_STATE_START",    0);
define("SASL_BASIC_STATE_DONE",     1);

class basic_sasl_client_class
{
	var $credentials=array();
	var $state=SASL_BASIC_STATE_START;

	Function Initialize(&$client)
	{
		return(1);
	}

	Function Start(&$client, &$message, &$interactions)
	{
		if($this->state!=SASL_BASIC_STATE_START)
		{
			$client->error="Basic authentication state is not at the start";
			return(SASL_FAIL);
		}
		$this->credentials=array(
			"user"=>"",
			"password"=>""
		);
		$defaults=array(
		);
		$status=$client->GetCredentials($this->credentials,$defaults,$interactions);
		if($status==SASL_CONTINUE)
		{
			$message=$this->credentials["user"].":".$this->credentials["password"];
			$this->state=SASL_BASIC_STATE_DONE;
		}
		else
			Unset($message);
		return($status);
	}

	Function Step(&$client, $response, &$message, &$interactions)
	{
		switch($this->state)
		{
			case SASL_BASIC_STATE_DONE:
				$client->error="Basic authentication was finished without success";
				return(SASL_FAIL);
			default:
				$client->error="invalid Basic authentication step state";
				return(SASL_FAIL);
		}
		return(SASL_CONTINUE);
	}
};

// =============================================================================

/*
 * cram_md5_sasl_client.php
 *
 * @(#) $Id: cram_md5_sasl_client.php,v 1.3 2004/11/17 08:00:37 mlemos Exp $
 *
 */

define("SASL_CRAM_MD5_STATE_START",             0);
define("SASL_CRAM_MD5_STATE_RESPOND_CHALLENGE", 1);
define("SASL_CRAM_MD5_STATE_DONE",              2);

class cram_md5_sasl_client_class
{
	var $credentials=array();
	var $state=SASL_CRAM_MD5_STATE_START;

	Function Initialize(&$client)
	{
		return(1);
	}

	Function HMACMD5($key,$text)
	{
		$key=(strlen($key)<64 ? str_pad($key,64,"\0") : substr($key,0,64));
		return(md5((str_repeat("\x5c", 64)^$key).pack("H32", md5((str_repeat("\x36", 64)^$key).$text))));
	}

	Function Start(&$client, &$message, &$interactions)
	{
		if($this->state!=SASL_CRAM_MD5_STATE_START)
		{
			$client->error="CRAM-MD5 authentication state is not at the start";
			return(SASL_FAIL);
		}
		$this->credentials=array(
			"user"=>"",
			"password"=>""
		);
		$defaults=array();
		$status=$client->GetCredentials($this->credentials,$defaults,$interactions);
		if($status==SASL_CONTINUE)
			$this->state=SASL_CRAM_MD5_STATE_RESPOND_CHALLENGE;
		Unset($message);
		return($status);
	}

	Function Step(&$client, $response, &$message, &$interactions)
	{
		switch($this->state)
		{
			case SASL_CRAM_MD5_STATE_RESPOND_CHALLENGE:
				$message=$this->credentials["user"]." ".$this->HMACMD5($this->credentials["password"], $response);
				$this->state=SASL_CRAM_MD5_STATE_DONE;
				break;
			case SASL_CRAM_MD5_STATE_DONE:
				$client->error="CRAM-MD5 authentication was finished without success";
				return(SASL_FAIL);
			default:
				$client->error="invalid CRAM-MD5 authentication step state";
				return(SASL_FAIL);
		}
		return(SASL_CONTINUE);
	}
};

// =============================================================================

/*
 * digest_sasl_client.php
 *
 * @(#) $Id: digest_sasl_client.php,v 1.1 2005/10/27 05:24:15 mlemos Exp $
 *
 */

define('SASL_DIGEST_STATE_START',             0);
define('SASL_DIGEST_STATE_RESPOND_CHALLENGE', 1);
define('SASL_DIGEST_STATE_DONE',              2);

class digest_sasl_client_class
{
	var $credentials=array();
	var $state=SASL_DIGEST_STATE_START;

	Function unq($string)
	{
		return(($string[0]=='"' && $string[strlen($string)-1]=='"') ? substr($string, 1, strlen($string)-2) : $string);
	}

	Function H($data)
	{
		return md5($data);
	}

	Function KD($secret, $data)
	{
		return $this->H($secret.':'.$data);
	}

	Function Initialize(&$client)
	{
		return(1);
	}

	Function Start(&$client, &$message, &$interactions)
	{
		if($this->state!=SASL_DIGEST_STATE_START)
		{
			$client->error='Digest authentication state is not at the start';
			return(SASL_FAIL);
		}
		$this->credentials=array(
			'user'=>'',
			'password'=>'',
			'uri'=>'',
			'method'=>'',
			'session'=>''
		);
		$defaults=array();
		$status=$client->GetCredentials($this->credentials,$defaults,$interactions);
		if($status==SASL_CONTINUE)
			$this->state=SASL_DIGEST_STATE_RESPOND_CHALLENGE;
		Unset($message);
		return($status);
	}

	Function Step(&$client, $response, &$message, &$interactions)
	{
		switch($this->state)
		{
			case SASL_DIGEST_STATE_RESPOND_CHALLENGE:
				$values=explode(',',$response);
				$parameters=array();
				for($v=0; $v<count($values); $v++)
					$parameters[strtok(trim($values[$v]), '=')]=strtok('');

				$message='username="'.$this->credentials['user'].'"';
				if(!IsSet($parameters[$p='realm'])
				&& !IsSet($parameters[$p='nonce']))
				{
					$client->error='Digest authentication parameter '.$p.' is missing from the server response';
					return(SASL_FAIL);
				}
				$message.=', realm='.$parameters['realm'];
				$message.=', nonce='.$parameters['nonce'];
				$message.=', uri="'.$this->credentials['uri'].'"';
				if(IsSet($parameters['algorithm']))
				{
					$algorithm=$this->unq($parameters['algorithm']);
					$message.=', algorithm='.$parameters['algorithm'];
				}
				else
					$algorithm='';

				$realm=$this->unq($parameters['realm']);
				$nonce=$this->unq($parameters['nonce']);
				if(IsSet($parameters['qop']))
				{
					switch($qop=$this->unq($parameters['qop']))
					{
						case "auth":
							$cnonce=$this->credentials['session'];
							break;
						default:
							$client->error='Digest authentication quality of protection '.$qop.' is not yet supported';
							return(SASL_FAIL);
					}
				}
				$nc_value='00000001';
				if(IsSet($parameters['qop'])
				&& !strcmp($algorithm, 'MD5-sess'))
					$A1=$this->H($this->credentials['user'].':'. $realm.':'. $this->credentials['password']).':'.$nonce.':'.$cnonce;
				else
					$A1=$this->credentials['user'].':'. $realm.':'. $this->credentials['password'];
				$A2=$this->credentials['method'].':'.$this->credentials['uri'];
				if(IsSet($parameters['qop']))
					$response=$this->KD($this->H($A1), $nonce.':'. $nc_value.':'. $cnonce.':'. $qop.':'. $this->H($A2));
				else
					$response=$this->KD($this->H($A1), $nonce.':'. $this->H($A2));
				$message.=', response="'.$response.'"';
				if(IsSet($parameters['opaque']))
					$message.=', opaque='.$parameters['opaque'];
				if(IsSet($parameters['qop']))
					$message.=', qop="'.$qop.'"';
				$message.=', nc='.$nc_value;
				if(IsSet($parameters['qop']))
					$message.=', cnonce="'.$cnonce.'"';
				$client->encode_response=0;
				$this->state=SASL_DIGEST_STATE_DONE;
				break;
			case SASL_DIGEST_STATE_DONE:
				$client->error='Digest authentication was finished without success';
				return(SASL_FAIL);
			default:
				$client->error='invalid Digest authentication step state';
				return(SASL_FAIL);
		}
		return(SASL_CONTINUE);
	}
};

// =============================================================================

/*
 * login_sasl_client.php
 *
 * @(#) $Id: login_sasl_client.php,v 1.2 2004/11/17 08:00:37 mlemos Exp $
 *
 */

define("SASL_LOGIN_STATE_START",             0);
define("SASL_LOGIN_STATE_IDENTIFY_USER",     1);
define("SASL_LOGIN_STATE_IDENTIFY_PASSWORD", 2);
define("SASL_LOGIN_STATE_DONE",              3);

class login_sasl_client_class
{
	var $credentials=array();
	var $state=SASL_LOGIN_STATE_START;

	Function Initialize(&$client)
	{
		return(1);
	}

	Function Start(&$client, &$message, &$interactions)
	{
		if($this->state!=SASL_LOGIN_STATE_START)
		{
			$client->error="LOGIN authentication state is not at the start";
			return(SASL_FAIL);
		}
		$this->credentials=array(
			"user"=>"",
			"password"=>"",
			"realm"=>""
		);
		$defaults=array(
			"realm"=>""
		);
		$status=$client->GetCredentials($this->credentials,$defaults,$interactions);
		if($status==SASL_CONTINUE)
			$this->state=SASL_LOGIN_STATE_IDENTIFY_USER;
		Unset($message);
		return($status);
	}

	Function Step(&$client, $response, &$message, &$interactions)
	{
		switch($this->state)
		{
			case SASL_LOGIN_STATE_IDENTIFY_USER:
				$message=$this->credentials["user"].(strlen($this->credentials["realm"]) ? "@".$this->credentials["realm"] : "");
				$this->state=SASL_LOGIN_STATE_IDENTIFY_PASSWORD;
				break;
			case SASL_LOGIN_STATE_IDENTIFY_PASSWORD:
				$message=$this->credentials["password"];
				$this->state=SASL_LOGIN_STATE_DONE;
				break;
			case SASL_LOGIN_STATE_DONE:
				$client->error="LOGIN authentication was finished without success";
				break;
			default:
				$client->error="invalid LOGIN authentication step state";
				return(SASL_FAIL);
		}
		return(SASL_CONTINUE);
	}
};

// =============================================================================

/*
 * ntlm_sasl_client.php
 *
 * @(#) $Id: ntlm_sasl_client.php,v 1.3 2004/11/17 08:00:37 mlemos Exp $
 *
 */

define("SASL_NTLM_STATE_START",             0);
define("SASL_NTLM_STATE_IDENTIFY_DOMAIN",   1);
define("SASL_NTLM_STATE_RESPOND_CHALLENGE", 2);
define("SASL_NTLM_STATE_DONE",              3);

class ntlm_sasl_client_class
{
	var $credentials=array();
	var $state=SASL_NTLM_STATE_START;

	Function Initialize(&$client)
	{
		if(!function_exists($function="mcrypt_encrypt")
		|| !function_exists($function="mhash"))
		{
			$extensions=array(
				"mcrypt_encrypt"=>"mcrypt",
				"mhash"=>"mhash"
			);
			$client->error="the extension ".$extensions[$function]." required by the NTLM SASL client class is not available in this PHP configuration";
			return(0);
		}
		return(1);
	}

	Function ASCIIToUnicode($ascii)
	{
		for($unicode="",$a=0;$a<strlen($ascii);$a++)
			$unicode.=substr($ascii,$a,1).chr(0);
		return($unicode);
	}

	Function TypeMsg1($domain,$workstation)
	{
		$domain_length=strlen($domain);
		$workstation_length=strlen($workstation);
		$workstation_offset=32;
		$domain_offset=$workstation_offset+$workstation_length;
		return(
			"NTLMSSP\0".
			"\x01\x00\x00\x00".
			"\x07\x32\x00\x00".
			pack("v",$domain_length).
			pack("v",$domain_length).
			pack("V",$domain_offset).
			pack("v",$workstation_length).
			pack("v",$workstation_length).
			pack("V",$workstation_offset).
			$workstation.
			$domain
		);
	}

	Function NTLMResponse($challenge,$password)
	{
		$unicode=$this->ASCIIToUnicode($password);
		$md4=mhash(MHASH_MD4,$unicode);
		$padded=$md4.str_repeat(chr(0),21-strlen($md4));
		$iv_size=mcrypt_get_iv_size(MCRYPT_DES,MCRYPT_MODE_ECB);
		$iv=mcrypt_create_iv($iv_size,MCRYPT_RAND);
		for($response="",$third=0;$third<21;$third+=7)
		{
			for($packed="",$p=$third;$p<$third+7;$p++)
				$packed.=str_pad(decbin(ord(substr($padded,$p,1))),8,"0",STR_PAD_LEFT);
			for($key="",$p=0;$p<strlen($packed);$p+=7)
			{
				$s=substr($packed,$p,7);
				$b=$s.((substr_count($s,"1") % 2) ? "0" : "1");
				$key.=chr(bindec($b));
			}
			$ciphertext=mcrypt_encrypt(MCRYPT_DES,$key,$challenge,MCRYPT_MODE_ECB,$iv);
			$response.=$ciphertext;
		}
		return $response;
	}

	Function TypeMsg3($ntlm_response,$user,$domain,$workstation)
	{
		$domain_unicode=$this->ASCIIToUnicode($domain);
		$domain_length=strlen($domain_unicode);
		$domain_offset=64;
		$user_unicode=$this->ASCIIToUnicode($user);
		$user_length=strlen($user_unicode);
		$user_offset=$domain_offset+$domain_length;
		$workstation_unicode=$this->ASCIIToUnicode($workstation);
		$workstation_length=strlen($workstation_unicode);
		$workstation_offset=$user_offset+$user_length;
		$lm="";
		$lm_length=strlen($lm);
		$lm_offset=$workstation_offset+$workstation_length;
		$ntlm=$ntlm_response;
		$ntlm_length=strlen($ntlm);
		$ntlm_offset=$lm_offset+$lm_length;
		$session="";
		$session_length=strlen($session);
		$session_offset=$ntlm_offset+$ntlm_length;
		return(
			"NTLMSSP\0".
			"\x03\x00\x00\x00".
			pack("v",$lm_length).
			pack("v",$lm_length).
			pack("V",$lm_offset).
			pack("v",$ntlm_length).
			pack("v",$ntlm_length).
			pack("V",$ntlm_offset).
			pack("v",$domain_length).
			pack("v",$domain_length).
			pack("V",$domain_offset).
			pack("v",$user_length).
			pack("v",$user_length).
			pack("V",$user_offset).
			pack("v",$workstation_length).
			pack("v",$workstation_length).
			pack("V",$workstation_offset).
			pack("v",$session_length).
			pack("v",$session_length).
			pack("V",$session_offset).
			"\x01\x02\x00\x00".
			$domain_unicode.
			$user_unicode.
			$workstation_unicode.
			$lm.
			$ntlm
		);
	}

	Function Start(&$client, &$message, &$interactions)
	{
		if($this->state!=SASL_NTLM_STATE_START)
		{
			$client->error="NTLM authentication state is not at the start";
			return(SASL_FAIL);
		}
		$this->credentials=array(
			"user"=>"",
			"password"=>"",
			"realm"=>"",
			"workstation"=>""
		);
		$defaults=array();
		$status=$client->GetCredentials($this->credentials,$defaults,$interactions);
		if($status==SASL_CONTINUE)
			$this->state=SASL_NTLM_STATE_IDENTIFY_DOMAIN;
		Unset($message);
		return($status);
	}

	Function Step(&$client, $response, &$message, &$interactions)
	{
		switch($this->state)
		{
			case SASL_NTLM_STATE_IDENTIFY_DOMAIN:
				$message=$this->TypeMsg1($this->credentials["realm"],$this->credentials["workstation"]);
				$this->state=SASL_NTLM_STATE_RESPOND_CHALLENGE;
				break;
			case SASL_NTLM_STATE_RESPOND_CHALLENGE:
				$ntlm_response=$this->NTLMResponse(substr($response,24,8),$this->credentials["password"]);
				$message=$this->TypeMsg3($ntlm_response,$this->credentials["user"],$this->credentials["realm"],$this->credentials["workstation"]);
				$this->state=SASL_NTLM_STATE_DONE;
				break;
			case SASL_NTLM_STATE_DONE:
				$client->error="NTLM authentication was finished without success";
				return(SASL_FAIL);
			default:
				$client->error="invalid NTLM authentication step state";
				return(SASL_FAIL);
		}
		return(SASL_CONTINUE);
	}
};

// =============================================================================

/*
 * plain_sasl_client.php
 *
 * @(#) $Id: plain_sasl_client.php,v 1.2 2004/11/17 08:00:37 mlemos Exp $
 *
 */

define("SASL_PLAIN_STATE_START",    0);
define("SASL_PLAIN_STATE_IDENTIFY", 1);
define("SASL_PLAIN_STATE_DONE",     2);

define("SASL_PLAIN_DEFAULT_MODE",            0);
define("SASL_PLAIN_EXIM_MODE",               1);
define("SASL_PLAIN_EXIM_DOCUMENTATION_MODE", 2);

class plain_sasl_client_class
{
	var $credentials=array();
	var $state=SASL_PLAIN_STATE_START;

	Function Initialize(&$client)
	{
		return(1);
	}

	Function Start(&$client, &$message, &$interactions)
	{
		if($this->state!=SASL_PLAIN_STATE_START)
		{
			$client->error="PLAIN authentication state is not at the start";
			return(SASL_FAIL);
		}
		$this->credentials=array(
			"user"=>"",
			"password"=>"",
			"realm"=>"",
			"mode"=>""
		);
		$defaults=array(
			"realm"=>"",
			"mode"=>""
		);
		$status=$client->GetCredentials($this->credentials,$defaults,$interactions);
		if($status==SASL_CONTINUE)
		{
			switch($this->credentials["mode"])
			{
				case SASL_PLAIN_EXIM_MODE:
					$message=$this->credentials["user"]."\0".$this->credentials["password"]."\0";
					break;
				case SASL_PLAIN_EXIM_DOCUMENTATION_MODE:
					$message="\0".$this->credentials["user"]."\0".$this->credentials["password"];
					break;
				default:
					$message=$this->credentials["user"]."\0".$this->credentials["user"].(strlen($this->credentials["realm"]) ? "@".$this->credentials["realm"] : "")."\0".$this->credentials["password"];
					break;
			}
			$this->state=SASL_PLAIN_STATE_DONE;
		}
		else
			Unset($message);
		return($status);
	}

	Function Step(&$client, $response, &$message, &$interactions)
	{
		switch($this->state)
		{
/*
			case SASL_PLAIN_STATE_IDENTIFY:
				switch($this->credentials["mode"])
				{
					case SASL_PLAIN_EXIM_MODE:
						$message=$this->credentials["user"]."\0".$this->credentials["password"]."\0";
						break;
					case SASL_PLAIN_EXIM_DOCUMENTATION_MODE:
						$message="\0".$this->credentials["user"]."\0".$this->credentials["password"];
						break;
					default:
						$message=$this->credentials["user"]."\0".$this->credentials["user"].(strlen($this->credentials["realm"]) ? "@".$this->credentials["realm"] : "")."\0".$this->credentials["password"];
						break;
				}
				var_dump($message);
				$this->state=SASL_PLAIN_STATE_DONE;
				break;
*/
			case SASL_PLAIN_STATE_DONE:
				$client->error="PLAIN authentication was finished without success";
				return(SASL_FAIL);
			default:
				$client->error="invalid PLAIN authentication step state";
				return(SASL_FAIL);
		}
		return(SASL_CONTINUE);
	}
};

// =============================================================================

/*
 * sasl.php
 *
 * @(#) $Id: sasl.php,v 1.11 2005/10/31 18:43:27 mlemos Exp $
 *
 */

define("SASL_INTERACT", 2);
define("SASL_CONTINUE", 1);
define("SASL_OK",       0);
define("SASL_FAIL",    -1);
define("SASL_NOMECH",  -4);

class sasl_interact_class
{
    var $id;
    var $challenge;
    var $prompt;
    var $default_result;
    var $result;
};

class sasl_client_class
{
    /* Public variables */

    var $error='';

    var $mechanism='';

    var $encode_response=1;

    /* Private variables */

    var $driver;
    var $drivers=array(
        "Digest"   => array("digest_sasl_client_class"/*,   "digest_sasl_client.php"   */),
        "CRAM-MD5" => array("cram_md5_sasl_client_class"/*, "cram_md5_sasl_client.php" */),
        "LOGIN"    => array("login_sasl_client_class"/*,    "login_sasl_client.php"    */),
        "NTLM"     => array("ntlm_sasl_client_class"/*,     "ntlm_sasl_client.php"     */),
        "PLAIN"    => array("plain_sasl_client_class"/*,    "plain_sasl_client.php"    */),
        "Basic"    => array("basic_sasl_client_class"/*,    "basic_sasl_client.php"    */)
    );
    var $credentials=array();

    /* Public functions */

    Function SetCredential($key,$value)
    {
        $this->credentials[$key]=$value;
    }

    Function GetCredentials(&$credentials,$defaults,&$interactions)
    {
        Reset($credentials);
        $end=(GetType($key=Key($credentials))!="string");
        for(;!$end;)
        {
            if(!IsSet($this->credentials[$key]))
            {
                if(IsSet($defaults[$key]))
                    $credentials[$key]=$defaults[$key];
                    else
                    {
                        $this->error="the requested credential ".$key." is not defined";
                        return(SASL_NOMECH);
                    }
            }
            else
                $credentials[$key]=$this->credentials[$key];
                Next($credentials);
                $end=(GetType($key=Key($credentials))!="string");
        }
        return(SASL_CONTINUE);
    }

    Function Start($mechanisms, &$message, &$interactions)
    {
        if(strlen($this->error))
            return(SASL_FAIL);
        if(IsSet($this->driver))
            return($this->driver->Start($this,$message,$interactions));
        $no_mechanism_error="";
        for($m=0;$m<count($mechanisms);$m++)
        {
            $mechanism=$mechanisms[$m];
            if(IsSet($this->drivers[$mechanism]))
            {
                /*
                 * if(!class_exists($this->drivers[$mechanism][0]))
		 * 	require(dirname(__FILE__)."/".$this->drivers[$mechanism][1]);
                 */
                $this->driver=new $this->drivers[$mechanism][0];
                if($this->driver->Initialize($this))
                {
                    $this->encode_response=1;
                    $status=$this->driver->Start($this,$message,$interactions);
                    switch($status)
                    {
                        case SASL_NOMECH:
                            Unset($this->driver);
                            if(strlen($no_mechanism_error)==0)
                                $no_mechanism_error=$this->error;
                                $this->error="";
                                break;
                        case SASL_CONTINUE:
                            $this->mechanism=$mechanism;
                            return($status);
                        default:
                            Unset($this->driver);
                            $this->error="";
                            return($status);
                    }
                }
                else
                {
                    Unset($this->driver);
                    if(strlen($no_mechanism_error)==0)
                        $no_mechanism_error=$this->error;
                        $this->error="";
                }
            }
        }
        $this->error=(strlen($no_mechanism_error) ? $no_mechanism_error : "it was not requested any of the authentication mechanisms that are supported");
        return(SASL_NOMECH);
    }

    Function Step($response, &$message, &$interactions)
    {
        if(strlen($this->error))
            return(SASL_FAIL);
            return($this->driver->Step($this,$response,$message,$interactions));
    }

};

// =============================================================================


/*
 * http.php
 *
 * @(#) $Header: /opt2/ena/metal/http/http.php,v 1.94 2016/05/03 02:07:04 mlemos Exp $
 *
 */

class http_class {

	/*public*/ const HTTP_CLIENT_ERROR_UNSPECIFIED_ERROR =       -1;
	/*public*/ const HTTP_CLIENT_ERROR_NO_ERROR =                 0;
	/*public*/ const HTTP_CLIENT_ERROR_INVALID_SERVER_ADDRESS =   1;
	/*public*/ const HTTP_CLIENT_ERROR_CANNOT_CONNECT =           2;
	/*public*/ const HTTP_CLIENT_ERROR_COMMUNICATION_FAILURE =    3;
	/*public*/ const HTTP_CLIENT_ERROR_CANNOT_ACCESS_LOCAL_FILE = 4;
	/*public*/ const HTTP_CLIENT_ERROR_PROTOCOL_FAILURE =         5;
	/*public*/ const HTTP_CLIENT_ERROR_INVALID_PARAMETERS =       6;

	public $host_name="";
	public $host_port=0;
	public $proxy_host_name="";
	public $proxy_host_port=80;
	public $socks_host_name = '';
	public $socks_host_port = 1080;
	public $socks_version = '5';

	public $protocol="http";
	public $request_method="GET";
	public $user_agent='httpclient (http://www.phpclasses.org/httpclient $Revision: 1.94 $)';
	public $accept='';
	public $authentication_mechanism="";
	public $user;
	public $password;
	public $realm;
	public $workstation;
	public $proxy_authentication_mechanism="";
	public $proxy_user;
	public $proxy_password;
	public $proxy_realm;
	public $proxy_workstation;
	public $request_uri="";
	public $request="";
	public $request_headers=array();
	public $request_user;
	public $request_password;
	public $request_realm;
	public $request_workstation;
	public $proxy_request_user;
	public $proxy_request_password;
	public $proxy_request_realm;
	public $proxy_request_workstation;
	public $request_body="";
	public $request_arguments=array();
	public $protocol_version="1.1";
	public $timeout=0;
	public $data_timeout=0;
	public $debug=0;
	public $log_debug=0;
	public $log_file_name="";
	public $debug_response_body=1;
	public $html_debug=0;
	public $support_cookies=1;
	public $cookies=array();
	public $error="";
	public $error_code = self::HTTP_CLIENT_ERROR_NO_ERROR;
	public $exclude_address="";
	public $follow_redirect=0;
	public $redirection_limit=5;
	public $response_status="";
	public $response_message="";
	public $file_buffer_length=8000;
	public $force_multipart_form_post=0;
	public $prefer_curl = 0;
	public $keep_alive = 1;
	public $sasl_authenticate = 1;

	/* private variables - DO NOT ACCESS */

	public $state="Disconnected";
	public $use_curl=0;
	public $connection=0;
	public $content_length_set=false;
	public $content_length=0;
	public $response="";
	public $read_response=0;
	public $read_length=0;
	public $request_host="";
	public $next_token="";
	public $redirection_level=0;
	public $chunked=0;
	public $remaining_chunk=0;
	public $last_chunk_read=0;
	public $months=array(
		"Jan"=>"01",
		"Feb"=>"02",
		"Mar"=>"03",
		"Apr"=>"04",
		"May"=>"05",
		"Jun"=>"06",
		"Jul"=>"07",
		"Aug"=>"08",
		"Sep"=>"09",
		"Oct"=>"10",
		"Nov"=>"11",
		"Dec"=>"12");
	public $session='';
	public $connection_close=0;
	public $force_close = 0;
	public $connected_host = '';
	public $connected_port = -1;
	public $connected_ssl = 0;

	/* Private methods - DO NOT CALL */

	Function Tokenize($string,$separator="")
	{
		if(!strcmp($separator,""))
		{
			$separator=$string;
			$string=$this->next_token;
		}
		for($character=0;$character<strlen($separator);$character++)
		{
			$found = null;
			if(GetType($position=strpos($string,$separator[$character]))=="integer")
				$found=(!is_null($found) ? min($found,$position) : $position);
		}
		if(IsSet($found))
		{
			$this->next_token=substr($string,$found+1);
			return(substr($string,0,$found));
		}
		else
		{
			$this->next_token="";
			return($string);
		}
	}

	Function CookieEncode($value, $name)
	{
		return($name ? str_replace("=", "%25", $value) : str_replace(";", "%3B", $value));
	}

	Function SetError($error, $error_code = self::HTTP_CLIENT_ERROR_UNSPECIFIED_ERROR)
	{
		$this->error_code = $error_code;
		return($this->error=$error);
	}

	Function SetPHPError($error, &$php_error_message, $error_code = self::HTTP_CLIENT_ERROR_UNSPECIFIED_ERROR)
	{
		if(IsSet($php_error_message)
		&& strlen($php_error_message))
			$error.=": ".$php_error_message;
		return($this->SetError($error, $error_code));
	}

	Function SetDataAccessError($error,$check_connection=0)
	{
		$this->error=$error;
		$this->error_code = self::HTTP_CLIENT_ERROR_COMMUNICATION_FAILURE;
		if(!$this->use_curl
		&& function_exists("socket_get_status"))
		{
			$status=socket_get_status($this->connection);
			if($status["timed_out"])
				$this->error.=": data access time out";
			elseif($status["eof"])
			{
				if($check_connection)
					$this->error="";
				else
					$this->error.=": the server disconnected";
			}
		}
	}

	Function OutputDebug($message)
	{
		if($this->log_debug)
		{
			if(!empty($this->log_file_name))
				error_log($message."\n", 3, $this->log_file_name);
			else
				error_log($message);
		}
		else
		{
			$message.="\n";
			if($this->html_debug)
				$message=str_replace("\n","<br />\n",HtmlEntities($message));
			echo $message;
			flush();
		}
	}

	Function GetLine()
	{
		for($line="";;)
		{
			if($this->use_curl)
			{
				$eol=strpos($this->response,"\n",$this->read_response);
				$data=($eol ? substr($this->response,$this->read_response,$eol+1-$this->read_response) : "");
				$this->read_response+=strlen($data);
			}
			else
			{
				if(feof($this->connection))
				{
					$this->SetDataAccessError("reached the end of data while reading from the HTTP server connection");
					return(0);
				}
				$data=fgets($this->connection,100);
			}
			if(GetType($data)!="string"
			|| strlen($data)==0)
			{
				$this->SetDataAccessError("it was not possible to read line from the HTTP server");
				return(0);
			}
			$line.=$data;
			$length=strlen($line);
			if($length
			&& !strcmp(substr($line,$length-1,1),"\n"))
			{
				$length-=(($length>=2 && !strcmp(substr($line,$length-2,1),"\r")) ? 2 : 1);
				$line=substr($line,0,$length);
				if($this->debug)
					$this->OutputDebug("S $line");
				return($line);
			}
		}
	}

	Function PutLine($line)
	{
		if($this->debug)
			$this->OutputDebug("C $line");
		if(!fputs($this->connection,$line."\r\n"))
		{
			$this->SetDataAccessError("it was not possible to send a line to the HTTP server");
			return(0);
		}
		return(1);
	}

	Function PutData($data)
	{
		if(strlen($data))
		{
			if($this->debug)
				$this->OutputDebug('C '.$data);
			if(!fputs($this->connection,$data))
			{
				$this->SetDataAccessError("it was not possible to send data to the HTTP server");
				return(0);
			}
		}
		return(1);
	}

	Function FlushData()
	{
		if(!fflush($this->connection))
		{
			$this->SetDataAccessError("it was not possible to send data to the HTTP server");
			return(0);
		}
		return(1);
	}

	Function ReadChunkSize()
	{
		if($this->remaining_chunk==0)
		{
			$debug=$this->debug;
			if(!$this->debug_response_body)
				$this->debug=0;
			$line=$this->GetLine();
			$this->debug=$debug;
			if(GetType($line)!="string")
				return($this->SetError("could not read chunk start: ".$this->error, $this->error_code));
			$this->remaining_chunk=hexdec($line);
			if($this->remaining_chunk == 0)
			{
				if(!$this->debug_response_body)
					$this->debug=0;
				$line=$this->GetLine();
				$this->debug=$debug;
				if(GetType($line)!="string")
					return($this->SetError("could not read chunk end: ".$this->error, $this->error_code));
			}
		}
		return("");
	}

	Function ReadBytes($length)
	{
		if($this->use_curl)
		{
			$bytes=substr($this->response,$this->read_response,min($length,strlen($this->response)-$this->read_response));
			$this->read_response+=strlen($bytes);
			if($this->debug
			&& $this->debug_response_body
			&& strlen($bytes))
				$this->OutputDebug("S ".$bytes);
		}
		else
		{
			if($this->chunked)
			{
				for($bytes="",$remaining=$length;$remaining;)
				{
					if(strlen($this->ReadChunkSize()))
						return("");
					if($this->remaining_chunk==0)
					{
						$this->last_chunk_read=1;
						break;
					}
					$ask=min($this->remaining_chunk,$remaining);
					$chunk=@fread($this->connection,$ask);
					$read=strlen($chunk);
					if($read==0)
					{
						$this->SetDataAccessError("it was not possible to read data chunk from the HTTP server");
						return("");
					}
					if($this->debug
					&& $this->debug_response_body)
						$this->OutputDebug("S ".$chunk);
					$bytes.=$chunk;
					$this->remaining_chunk-=$read;
					$remaining-=$read;
					if($this->remaining_chunk==0)
					{
						if(feof($this->connection))
							return($this->SetError("reached the end of data while reading the end of data chunk mark from the HTTP server", self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE));
						$data=@fread($this->connection,2);
						if(strcmp($data,"\r\n"))
						{
							$this->SetDataAccessError("it was not possible to read end of data chunk from the HTTP server");
							return("");
						}
					}
				}
			}
			else
			{
				$bytes=@fread($this->connection,$length);
				if(strlen($bytes))
				{
					if($this->debug
					&& $this->debug_response_body)
						$this->OutputDebug("S ".$bytes);
				}
				else
					$this->SetDataAccessError("it was not possible to read data from the HTTP server", $this->connection_close);
			}
		}
		return($bytes);
	}

	Function EndOfInput()
	{
		if($this->use_curl)
			return($this->read_response>=strlen($this->response));
		if($this->chunked)
			return($this->last_chunk_read);
		if($this->content_length_set)
			return($this->content_length <= $this->read_length);
		return(feof($this->connection));
	}

	Function Resolve($domain, &$ip, $server_type)
	{
		if(preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',$domain))
			$ip=$domain;
		else
		{
			if($this->debug)
				$this->OutputDebug('Resolving '.$server_type.' server domain "'.$domain.'"...');
			if(!strcmp($ip=@gethostbyname($domain),$domain))
				$ip="";
		}
		if(strlen($ip)==0
		|| (strlen($this->exclude_address)
		&& !strcmp(@gethostbyname($this->exclude_address),$ip)))
			return($this->SetError("could not resolve the host domain \"".$domain."\"", self::HTTP_CLIENT_ERROR_INVALID_SERVER_ADDRESS));
		return('');
	}

	Function Connect($host_name, $host_port, $ssl, $server_type = 'HTTP')
	{
		$domain=$host_name;
		$port = $host_port;
		$ip = '';
		if(strlen($error = $this->Resolve($domain, $ip, $server_type)))
			return($error);
		if(strlen($this->socks_host_name))
		{
			switch($this->socks_version)
			{
				case '4':
					$version = 4;
					break;
				case '5':
					$version = 5;
					break;
				default:
					return('it was not specified a supported SOCKS protocol version');
					break;
			}
			$host_ip = $ip;
			$port = $this->socks_host_port;
			$host_server_type = $server_type;
			$server_type = 'SOCKS';
			if(strlen($error = $this->Resolve($this->socks_host_name, $ip, $server_type)))
				return($error);
		}
		if($this->debug)
			$this->OutputDebug('Connecting to '.$server_type.' server IP '.$ip.' port '.$port.'...');
		if($ssl)
			$ip="ssl://".$host_name;
		$errno = -1;
		if(($this->connection=($this->timeout ? @fsockopen($ip, $port, $errno, $error, $this->timeout) : @fsockopen($ip, $port, $errno)))==0)
		{
			$error_code = self::HTTP_CLIENT_ERROR_CANNOT_CONNECT;
			switch($errno)
			{
				case -3:
					return($this->SetError("socket could not be created", $error_code));
				case -4:
					return($this->SetError("dns lookup on hostname \"".$host_name."\" failed", $error_code));
				case -5:
					return($this->SetError("connection refused or timed out", $error_code));
				case -6:
					return($this->SetError("fdopen() call failed", $error_code));
				case -7:
					return($this->SetError("setvbuf() call failed", $error_code));
				default:
					$php_errormsg = '';
					return($this->SetPHPError($errno." could not connect to the host \"".$host_name."\"",$php_errormsg, $error_code));
			}
		}
		else
		{
			if($this->data_timeout
			&& function_exists("socket_set_timeout"))
				socket_set_timeout($this->connection,$this->data_timeout,0);
			if(strlen($this->socks_host_name))
			{
				if($this->debug)
					$this->OutputDebug('Connected to the SOCKS server '.$this->socks_host_name);
				$send_error = 'it was not possible to send data to the SOCKS server';
				$receive_error = 'it was not possible to receive data from the SOCKS server';
				switch($version)
				{
					case 4:
						$command = 1;
						$user = '';
						if(!fputs($this->connection, chr($version).chr($command).pack('nN', $host_port, ip2long($host_ip)).$user.Chr(0)))
							$error = $this->SetDataAccessError($send_error);
						else
						{
							$response = fgets($this->connection, 9);
							if(strlen($response) != 8)
								$error = $this->SetDataAccessError($receive_error);
							else
							{
								$socks_errors = array(
									"\x5a"=>'',
									"\x5b"=>'request rejected',
									"\x5c"=>'request failed because client is not running identd (or not reachable from the server)',
									"\x5d"=>'request failed because client\'s identd could not confirm the user ID string in the request',
								);
								$error_code = $response[1];
								$error = (IsSet($socks_errors[$error_code]) ? $socks_errors[$error_code] : 'unknown');
								if(strlen($error))
									$error = 'SOCKS error: '.$error;
							}
						}
						break;
					case 5:
						if($this->debug)
							$this->OutputDebug('Negotiating the authentication method ...');
						$methods = 1;
						$method = 0;
						if(!fputs($this->connection, chr($version).chr($methods).chr($method)))
							$error = $this->SetDataAccessError($send_error);
						else
						{
							$response = fgets($this->connection, 3);
							if(strlen($response) != 2)
								$error = $this->SetDataAccessError($receive_error);
							elseif(Ord($response[1]) != $method)
								$error = 'the SOCKS server requires an authentication method that is not yet supported';
							else
							{
								if($this->debug)
									$this->OutputDebug('Connecting to '.$host_server_type.' server IP '.$host_ip.' port '.$host_port.'...');
								$command = 1;
								$address_type = 1;
								if(!fputs($this->connection, chr($version).chr($command)."\x00".chr($address_type).pack('Nn', ip2long($host_ip), $host_port)))
									$error = $this->SetDataAccessError($send_error);
								else
								{
									$response = fgets($this->connection, 11);
									if(strlen($response) != 10)
										$error = $this->SetDataAccessError($receive_error);
									else
									{
										$socks_errors = array(
											"\x00"=>'',
											"\x01"=>'general SOCKS server failure',
											"\x02"=>'connection not allowed by ruleset',
											"\x03"=>'Network unreachable',
											"\x04"=>'Host unreachable',
											"\x05"=>'Connection refused',
											"\x06"=>'TTL expired',
											"\x07"=>'Command not supported',
											"\x08"=>'Address type not supported'
										);
										$error_code = $response[1];
										$error = (IsSet($socks_errors[$error_code]) ? $socks_errors[$error_code] : 'unknown');
										if(strlen($error))
											$error = 'SOCKS error: '.$error;
									}
								}
							}
						}
						break;
					default:
						$error = 'support for SOCKS protocol version '.$this->socks_version.' is not yet implemented';
						break;
				}
				if(strlen($error))
				{
					fclose($this->connection);
					return($error);
				}
			}
			if($this->debug)
				$this->OutputDebug("Connected to $host_name");
			if(strlen($this->proxy_host_name)
			&& !strcmp(strtolower($this->protocol), 'https'))
			{
				if(function_exists('stream_socket_enable_crypto')
				&& in_array('ssl', stream_get_transports()))
					$this->state = "ConnectedToProxy";
				else
				{
					$this->OutputDebug("It is not possible to start SSL after connecting to the proxy server. If the proxy refuses to forward the SSL request, you may need to upgrade to PHP 5.1 or later with OpenSSL support enabled.");
					$this->state="Connected";
				}
			}
			else
				$this->state="Connected";
			return("");
		}
	}

	Function Disconnect()
	{
		if($this->debug)
			$this->OutputDebug("Disconnected from ".$this->connected_host);
		if($this->use_curl)
		{
			curl_close($this->connection);
			$this->response="";
		}
		else
			fclose($this->connection);
		$this->state="Disconnected";
		return("");
	}

	/* Public methods */

	Function GetRequestArguments($url, &$arguments)
	{
		$this->error = '';
		$this->error_code = self::HTTP_CLIENT_ERROR_NO_ERROR;
		$arguments=array();
		$url = str_replace(' ', '%20', $url);
		$parameters=@parse_url($url);
		if(!$parameters)
			return($this->SetError("it was not specified a valid URL", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		if(!IsSet($parameters["scheme"]))
			return($this->SetError("it was not specified the protocol type argument", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		switch(strtolower($parameters["scheme"]))
		{
			case "http":
			case "https":
				$arguments["Protocol"]=$parameters["scheme"];
				break;
			default:
				return($parameters["scheme"]." connection scheme is not yet supported");
		}
		if(!IsSet($parameters["host"]))
			return($this->SetError("it was not specified the connection host argument", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		$arguments["HostName"]=$parameters["host"];
		$arguments["Headers"]=array("Host"=>$parameters["host"].(IsSet($parameters["port"]) ? ":".$parameters["port"] : ""));
		if(IsSet($parameters["user"]))
		{
			$arguments["AuthUser"]=UrlDecode($parameters["user"]);
			if(!IsSet($parameters["pass"]))
				$arguments["AuthPassword"]="";
		}
		if(IsSet($parameters["pass"]))
		{
			if(!IsSet($parameters["user"]))
				$arguments["AuthUser"]="";
			$arguments["AuthPassword"]=UrlDecode($parameters["pass"]);
		}
		if(IsSet($parameters["port"]))
		{
			if(strcmp($parameters["port"],strval(intval($parameters["port"]))))
				return($this->SetError("it was not specified a valid connection host argument", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			$arguments["HostPort"]=intval($parameters["port"]);
		}
		else
			$arguments["HostPort"]=0;
		$arguments["RequestURI"]=(IsSet($parameters["path"]) ? $parameters["path"] : "/").(IsSet($parameters["query"]) ? "?".$parameters["query"] : "");
		if(strlen($this->user_agent))
			$arguments["Headers"]["User-Agent"]=$this->user_agent;
		if(strlen($this->accept))
			$arguments["Headers"]["Accept"]=$this->accept;
		return("");
	}

	Function Open($arguments)
	{
		if(strlen($this->error))
			return($this->error);
		$error_code = self::HTTP_CLIENT_ERROR_UNSPECIFIED_ERROR;
		if(IsSet($arguments["HostName"]))
			$this->host_name=$arguments["HostName"];
		if(IsSet($arguments["HostPort"]))
			$this->host_port=$arguments["HostPort"];
		if(IsSet($arguments["ProxyHostName"]))
			$this->proxy_host_name=$arguments["ProxyHostName"];
		if(IsSet($arguments["ProxyHostPort"]))
			$this->proxy_host_port=$arguments["ProxyHostPort"];
		if(IsSet($arguments["SOCKSHostName"]))
			$this->socks_host_name=$arguments["SOCKSHostName"];
		if(IsSet($arguments["SOCKSHostPort"]))
			$this->socks_host_port=$arguments["SOCKSHostPort"];
		if(IsSet($arguments["SOCKSVersion"]))
			$this->socks_version=$arguments["SOCKSVersion"];
		if(IsSet($arguments["PreferCurl"]))
			$this->prefer_curl=$arguments["PreferCurl"];
		if(IsSet($arguments["Protocol"]))
			$this->protocol=$arguments["Protocol"];
		switch(strtolower($this->protocol))
		{
			case "http":
				$default_port=80;
				break;
			case "https":
				$default_port=443;
				break;
			default:
				return($this->SetError("it was not specified a valid connection protocol", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		}
		if(strlen($this->proxy_host_name)==0)
		{
			if(strlen($this->host_name)==0)
				return($this->SetError("it was not specified a valid hostname", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			$host_name=$this->host_name;
			$host_port=($this->host_port ? $this->host_port : $default_port);
			$server_type = 'HTTP';
		}
		else
		{
			$host_name=$this->proxy_host_name;
			$host_port=$this->proxy_host_port;
			$server_type = 'HTTP proxy';
		}
		$ssl=(strtolower($this->protocol)=="https" && strlen($this->proxy_host_name)==0);
		if($ssl
		&& strlen($this->socks_host_name))
			return($this->SetError('establishing SSL connections via a SOCKS server is not yet supported', self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		$this->use_curl=($ssl && $this->prefer_curl && function_exists("curl_init"));
		switch($this->state)
		{
			case 'Connected':
				if(!strcmp($host_name, $this->connected_host)
				&& intval($host_port) == $this->connected_port
				&& intval($ssl) == $this->connected_ssl)
				{
					if($this->debug)
						$this->OutputDebug("Reusing connection to ".$this->connected_host);
					return('');
				}
				if(strlen($error = $this->Disconnect()))
					return($error);
			case "Disconnected":
				break;
			default:
				return("1 already connected");
		}
		if($this->debug)
			$this->OutputDebug("Connecting to ".$this->host_name);
		if($this->use_curl)
		{
			$error=(($this->connection=curl_init($this->protocol."://".$this->host_name.($host_port==$default_port ? "" : ":".strval($host_port))."/")) ? "" : "Could not initialize a CURL session");

			if ((ini_get('curl.cainfo') == '') && (file_exists(__DIR__.'/../../3p/certs/cacert.pem'))) {
				// Added for ViaThinkSoft OIDplus to handle wrongly configured CURL installations (Windows)
				curl_setopt($this->connection, CURLOPT_CAINFO, __DIR__.'/../../3p/certs/cacert.pem');
			}

			if(strlen($error)==0)
			{
				if(IsSet($arguments["SSLCertificateFile"]))
					curl_setopt($this->connection,CURLOPT_SSLCERT,$arguments["SSLCertificateFile"]);
				if(IsSet($arguments["SSLCertificatePassword"]))
					curl_setopt($this->connection,CURLOPT_SSLCERTPASSWD,$arguments["SSLCertificatePassword"]);
				if(IsSet($arguments["SSLKeyFile"]))
					curl_setopt($this->connection,CURLOPT_SSLKEY,$arguments["SSLKeyFile"]);
				if(IsSet($arguments["SSLKeyPassword"]))
					curl_setopt($this->connection,CURLOPT_SSLKEYPASSWD,$arguments["SSLKeyPassword"]);
			}
			$this->state="Connected";
		}
		else
		{
			$error="";
			if(strlen($this->proxy_host_name)
			&& (IsSet($arguments["SSLCertificateFile"])
			|| IsSet($arguments["SSLCertificateFile"])))
				$error="establishing SSL connections using certificates or private keys via non-SSL proxies is not supported";
			else
			{
				if($ssl)
				{
					if(IsSet($arguments["SSLCertificateFile"]))
						$error="establishing SSL connections using certificates is only supported when the cURL extension is enabled";
					elseif(IsSet($arguments["SSLKeyFile"]))
						$error="establishing SSL connections using a private key is only supported when the cURL extension is enabled";
					else
					{
						$version=explode('.',PHP_VERSION);
						$php_version=intval($version[0])*1000000+intval($version[1])*1000+intval($version[2]);
						if($php_version<4003000)
							$error="establishing SSL connections requires at least PHP version 4.3.0 or having the cURL extension enabled";
						elseif(!function_exists("extension_loaded")
						|| !extension_loaded("openssl"))
							$error="establishing SSL connections requires the OpenSSL extension enabled";
					}
				}
				if(strlen($error)==0)
				{
					$error=$this->Connect($host_name, $host_port, $ssl, $server_type);
					$error_code = $this->error_code;
				}
			}
		}
		if(strlen($error))
			return($this->SetError($error, $error_code));
		$this->session=md5(uniqid(""));
		$this->connected_host = $host_name;
		$this->connected_port = intval($host_port);
		$this->connected_ssl = intval($ssl);
		return("");
	}

	Function Close($force = 0)
	{
		if($this->state=="Disconnected")
			return("1 already disconnected");
		if(!$this->force_close
		&& $this->keep_alive
		&& !$force
		&& $this->state == 'ResponseReceived')
		{
			if($this->debug)
				$this->OutputDebug('Keeping the connection alive to '.$this->connected_host);
			$this->state = 'Connected';
			return('');
		}
		return($this->Disconnect());
	}

	Function PickCookies(&$cookies,$secure)
	{
		if(IsSet($this->cookies[$secure]))
		{
			$now=gmdate("Y-m-d H-i-s");
			for($domain=0,Reset($this->cookies[$secure]);$domain<count($this->cookies[$secure]);Next($this->cookies[$secure]),$domain++)
			{
				$domain_pattern=Key($this->cookies[$secure]);
				$match=strlen($this->request_host)-strlen($domain_pattern);
				if($match>=0
				&& !strcmp($domain_pattern,substr($this->request_host,$match))
				&& ($match==0
				|| $domain_pattern[0]=="."
				|| $this->request_host[$match-1]=="."))
				{
					for(Reset($this->cookies[$secure][$domain_pattern]),$path_part=0;$path_part<count($this->cookies[$secure][$domain_pattern]);Next($this->cookies[$secure][$domain_pattern]),$path_part++)
					{
						$path=Key($this->cookies[$secure][$domain_pattern]);
						if(strlen($this->request_uri)>=strlen($path)
						&& substr($this->request_uri,0,strlen($path))==$path)
						{
							for(Reset($this->cookies[$secure][$domain_pattern][$path]),$cookie=0;$cookie<count($this->cookies[$secure][$domain_pattern][$path]);Next($this->cookies[$secure][$domain_pattern][$path]),$cookie++)
							{
								$cookie_name=Key($this->cookies[$secure][$domain_pattern][$path]);
								$expires=$this->cookies[$secure][$domain_pattern][$path][$cookie_name]["expires"];
								if($expires==""
								|| strcmp($now,$expires)<0)
									$cookies[$cookie_name]=$this->cookies[$secure][$domain_pattern][$path][$cookie_name];
							}
						}
					}
				}
			}
		}
	}

	Function GetFileDefinition($file, &$definition)
	{
		$name="";
		if(IsSet($file["FileName"]))
			$name=basename($file["FileName"]);
		if(IsSet($file["Name"]))
			$name=$file["Name"];
		if(strlen($name)==0)
			return("it was not specified the file part name");
		if(IsSet($file["Content-Type"]))
		{
			$content_type=$file["Content-Type"];
			$type=$this->Tokenize(strtolower($content_type),"/");
			$sub_type=$this->Tokenize("");
			switch($type)
			{
				case "text":
				case "image":
				case "audio":
				case "video":
				case "application":
				case "message":
					break;
				case "automatic":
					switch($sub_type)
					{
						case "name":
							switch(GetType($dot=strrpos($name,"."))=="integer" ? strtolower(substr($name,$dot)) : "")
							{
								case ".xls":
									$content_type="application/excel";
									break;
								case ".hqx":
									$content_type="application/macbinhex40";
									break;
								case ".doc":
								case ".dot":
								case ".wrd":
									$content_type="application/msword";
									break;
								case ".pdf":
									$content_type="application/pdf";
									break;
								case ".pgp":
									$content_type="application/pgp";
									break;
								case ".ps":
								case ".eps":
								case ".ai":
									$content_type="application/postscript";
									break;
								case ".ppt":
									$content_type="application/powerpoint";
									break;
								case ".rtf":
									$content_type="application/rtf";
									break;
								case ".tgz":
								case ".gtar":
									$content_type="application/x-gtar";
									break;
								case ".gz":
									$content_type="application/x-gzip";
									break;
								case ".php":
								case ".php3":
									$content_type="application/x-httpd-php";
									break;
								case ".js":
									$content_type="application/x-javascript";
									break;
								case ".ppd":
								case ".psd":
									$content_type="application/x-photoshop";
									break;
								case ".swf":
								case ".swc":
								case ".rf":
									$content_type="application/x-shockwave-flash";
									break;
								case ".tar":
									$content_type="application/x-tar";
									break;
								case ".zip":
									$content_type="application/zip";
									break;
								case ".mid":
								case ".midi":
								case ".kar":
									$content_type="audio/midi";
									break;
								case ".mp2":
								case ".mp3":
								case ".mpga":
									$content_type="audio/mpeg";
									break;
								case ".ra":
									$content_type="audio/x-realaudio";
									break;
								case ".wav":
									$content_type="audio/wav";
									break;
								case ".bmp":
									$content_type="image/bitmap";
									break;
								case ".gif":
									$content_type="image/gif";
									break;
								case ".iff":
									$content_type="image/iff";
									break;
								case ".jb2":
									$content_type="image/jb2";
									break;
								case ".jpg":
								case ".jpe":
								case ".jpeg":
									$content_type="image/jpeg";
									break;
								case ".jpx":
									$content_type="image/jpx";
									break;
								case ".png":
									$content_type="image/png";
									break;
								case ".tif":
								case ".tiff":
									$content_type="image/tiff";
									break;
								case ".wbmp":
									$content_type="image/vnd.wap.wbmp";
									break;
								case ".xbm":
									$content_type="image/xbm";
									break;
								case ".css":
									$content_type="text/css";
									break;
								case ".txt":
									$content_type="text/plain";
									break;
								case ".htm":
								case ".html":
									$content_type="text/html";
									break;
								case ".xml":
									$content_type="text/xml";
									break;
								case ".mpg":
								case ".mpe":
								case ".mpeg":
									$content_type="video/mpeg";
									break;
								case ".qt":
								case ".mov":
									$content_type="video/quicktime";
									break;
								case ".avi":
									$content_type="video/x-ms-video";
									break;
								case ".eml":
									$content_type="message/rfc822";
									break;
								default:
									$content_type="application/octet-stream";
									break;
							}
							break;
						default:
							return($content_type." is not a supported automatic content type detection method");
					}
					break;
				default:
					return($content_type." is not a supported file content type");
			}
		}
		else
			$content_type="application/octet-stream";
		$definition=array(
			"Content-Type"=>$content_type,
			"NAME"=>$name
		);
		if(IsSet($file["FileName"]))
		{
			if(GetType($length=@filesize($file["FileName"]))!="integer")
			{
				$error="it was not possible to determine the length of the file ".$file["FileName"];
				/*
				if(IsSet($php_errormsg)
				&& strlen($php_errormsg))
					$error.=": ".$php_errormsg;
				*/
				if(!file_exists($file["FileName"]))
					$error="it was not possible to access the file ".$file["FileName"];
				return($error);
			}
			$definition["FILENAME"]=$file["FileName"];
			$definition["Content-Length"]=$length;
		}
		elseif(IsSet($file["Data"]))
			$definition["Content-Length"]=strlen($definition["DATA"]=$file["Data"]);
		else
			return("it was not specified a valid file name");
		return("");
	}

	Function ConnectFromProxy($arguments, &$headers)
	{
		$host = $this->host_name.':'.($this->host_port ? $this->host_port : 443);
		$this->OutputDebug('Connecting from proxy to host '.$host);
		if(!$this->PutLine('CONNECT '.$host.' HTTP/1.0')
		|| (strlen($this->user_agent)
		&& !$this->PutLine('User-Agent: '.$this->user_agent))
		|| (strlen($this->accept)
		&& !$this->PutLine('Accept: '.$this->accept))
		|| (IsSet($arguments['Headers']['Proxy-Authorization'])
		&& !$this->PutLine('Proxy-Authorization: '.$arguments['Headers']['Proxy-Authorization']))
		|| !$this->PutLine(''))
		{
			$this->Disconnect();
			return($this->error);
		}
		$this->state = "ConnectSent";
		if(strlen($error=$this->ReadReplyHeadersResponse($headers)))
			return($error);
		$proxy_authorization="";
		while(!strcmp($this->response_status, "100"))
		{
			$this->state="ConnectSent";
			if(strlen($error=$this->ReadReplyHeadersResponse($headers)))
				return($error);
		}
		switch($this->response_status)
		{
			case "200":
				$this->OutputDebug('Establishing the cryptography layer with host '.$host);
				if(!stream_socket_enable_crypto($this->connection, 1, STREAM_CRYPTO_METHOD_SSLv23_CLIENT))
				{
					$this->OutputDebug('Failed establishing the cryptography layer with host '.$host);
					$php_errormsg = '';
					$this->SetPHPError('it was not possible to start a SSL encrypted connection via this proxy', $php_errormsg, self::HTTP_CLIENT_ERROR_COMMUNICATION_FAILURE);
					$this->Disconnect();
					return($this->error);
				}
				$this->OutputDebug('Succeeded establishing the cryptography layer with host '.$host);
				$this->state = "Connected";
				break;
			case "407":
				if(strlen($error=$this->Authenticate($headers, -1, $proxy_authorization, $this->proxy_request_user, $this->proxy_request_password, $this->proxy_request_realm, $this->proxy_request_workstation)))
					return($error);
				break;
			default:
				return($this->SetError("unable to send request via proxy", self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE));
		}
		return("");
	}

	Function SendRequest($arguments)
	{
		if(strlen($this->error))
			return($this->error);
		if(IsSet($arguments["ProxyUser"]))
			$this->proxy_request_user=$arguments["ProxyUser"];
		elseif(IsSet($this->proxy_user))
			$this->proxy_request_user=$this->proxy_user;
		if(IsSet($arguments["ProxyPassword"]))
			$this->proxy_request_password=$arguments["ProxyPassword"];
		elseif(IsSet($this->proxy_password))
			$this->proxy_request_password=$this->proxy_password;
		if(IsSet($arguments["ProxyRealm"]))
			$this->proxy_request_realm=$arguments["ProxyRealm"];
		elseif(IsSet($this->proxy_realm))
			$this->proxy_request_realm=$this->proxy_realm;
		if(IsSet($arguments["ProxyWorkstation"]))
			$this->proxy_request_workstation=$arguments["ProxyWorkstation"];
		elseif(IsSet($this->proxy_workstation))
			$this->proxy_request_workstation=$this->proxy_workstation;
		switch($this->state)
		{
			case "Disconnected":
				return($this->SetError("connection was not yet established", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			case "Connected":
				$connect = 0;
				break;
			case "ConnectedToProxy":
				$headers = array();
				if(strlen($error = $this->ConnectFromProxy($arguments, $headers)))
					return($error);
				$connect = 1;
				break;
			default:
				return($this->SetError("can not send request in the current connection state", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		}
		if(IsSet($arguments["RequestMethod"]))
			$this->request_method=$arguments["RequestMethod"];
		if(IsSet($arguments["User-Agent"]))
			$this->user_agent=$arguments["User-Agent"];
		if(!IsSet($arguments["Headers"]["User-Agent"])
		&& strlen($this->user_agent))
			$arguments["Headers"]["User-Agent"]=$this->user_agent;
		if(IsSet($arguments["KeepAlive"]))
			$this->keep_alive=intval($arguments["KeepAlive"]);
		if(!IsSet($arguments["Headers"]["Connection"])
		&& $this->keep_alive)
			$arguments["Headers"]["Connection"]='Keep-Alive';
		if(IsSet($arguments["Accept"]))
			$this->user_agent=$arguments["Accept"];
		if(!IsSet($arguments["Headers"]["Accept"])
		&& strlen($this->accept))
			$arguments["Headers"]["Accept"]=$this->accept;
		if(strlen($this->request_method)==0)
			return($this->SetError("it was not specified a valid request method", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		if(IsSet($arguments["RequestURI"]))
			$this->request_uri=$arguments["RequestURI"];
		if(strlen($this->request_uri)==0
		|| substr($this->request_uri,0,1)!="/")
			return($this->SetError("it was not specified a valid request URI", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		$this->request_arguments=$arguments;
		$this->request_headers=(IsSet($arguments["Headers"]) ? $arguments["Headers"] : array());
		$body_length=0;
		$this->request_body="";
		$get_body=1;
		if($this->request_method=="POST"
		|| $this->request_method=="PUT")
		{
			if(IsSet($arguments['StreamRequest']))
			{
				$get_body = 0;
				$this->request_headers["Transfer-Encoding"]="chunked";
			}
			elseif(IsSet($arguments["PostFiles"])
			|| ($this->force_multipart_form_post
			&& IsSet($arguments["PostValues"])))
			{
				$boundary="--".md5(uniqid(time()));
				$this->request_headers["Content-Type"]="multipart/form-data; boundary=".$boundary.(IsSet($arguments["CharSet"]) ? "; charset=".$arguments["CharSet"] : "");
				$post_parts=array();
				if(IsSet($arguments["PostValues"]))
				{
					$values=$arguments["PostValues"];
					if(GetType($values)!="array")
						return($this->SetError("it was not specified a valid POST method values array", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
					for(Reset($values),$value=0;$value<count($values);Next($values),$value++)
					{
						$input=Key($values);
						$headers="--".$boundary."\r\nContent-Disposition: form-data; name=\"".$input."\"\r\n\r\n";
						$data=$values[$input];
						$post_parts[]=array("HEADERS"=>$headers,"DATA"=>$data);
						$body_length+=strlen($headers)+strlen($data)+strlen("\r\n");
					}
				}
				$body_length+=strlen("--".$boundary."--\r\n");
				$files=(IsSet($arguments["PostFiles"]) ? $arguments["PostFiles"] : array());
				Reset($files);
				$end=(GetType($input=Key($files))!="string");
				for(;!$end;)
				{
					$definition = array();
					if(strlen($error=$this->GetFileDefinition($files[$input],$definition)))
						return("3 ".$error);
					$headers="--".$boundary."\r\nContent-Disposition: form-data; name=\"".$input."\"; filename=\"".$definition["NAME"]."\"\r\nContent-Type: ".$definition["Content-Type"]."\r\n\r\n";
					$part=count($post_parts);
					$post_parts[$part]=array("HEADERS"=>$headers);
					if(IsSet($definition["FILENAME"]))
					{
						$post_parts[$part]["FILENAME"]=$definition["FILENAME"];
						$data="";
					}
					else
						$data=$definition["DATA"];
					$post_parts[$part]["DATA"]=$data;
					$body_length+=strlen($headers)+$definition["Content-Length"]+strlen("\r\n");
					Next($files);
					$end=(GetType($input=Key($files))!="string");
				}
				$get_body=0;
			}
			elseif(IsSet($arguments["PostValues"]))
			{
				$values=$arguments["PostValues"];
				if(GetType($values)!="array")
					return($this->SetError("it was not specified a valid POST method values array", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
				for(Reset($values),$value=0;$value<count($values);Next($values),$value++)
				{
					$k=Key($values);
					if(GetType($values[$k])=="array")
					{
						for($v = 0; $v < count($values[$k]); $v++)
						{
							if($value+$v>0)
								$this->request_body.="&";
							$this->request_body.=UrlEncode($k)."=".UrlEncode($values[$k][$v]);
						}
					}
					else
					{
						if($value>0)
							$this->request_body.="&";
						$this->request_body.=UrlEncode($k)."=".UrlEncode($values[$k]);
					}
				}
				$this->request_headers["Content-Type"]="application/x-www-form-urlencoded".(IsSet($arguments["CharSet"]) ? "; charset=".$arguments["CharSet"] : "");
				$get_body=0;
			}
		}
		if($get_body
		&& (IsSet($arguments["Body"])
		|| IsSet($arguments["BodyStream"])))
		{
			if(IsSet($arguments["Body"]))
				$this->request_body=$arguments["Body"];
			else
			{
				$stream=$arguments["BodyStream"];
				$this->request_body="";
				for($part=0; $part<count($stream); $part++)
				{
					if(IsSet($stream[$part]["Data"]))
						$this->request_body.=$stream[$part]["Data"];
					elseif(IsSet($stream[$part]["File"]))
					{
						if(!($file=@fopen($stream[$part]["File"],"rb"))) {
							$php_errormsg = '';
							return($this->SetPHPError("could not open upload file ".$stream[$part]["File"], $php_errormsg, self::HTTP_CLIENT_ERROR_CANNOT_ACCESS_LOCAL_FILE));
						}
						while(!feof($file))
						{
							if(GetType($block=@fread($file,$this->file_buffer_length))!="string")
							{
								$error=$this->SetPHPError("could not read body stream file ".$stream[$part]["File"], $php_errormsg, self::HTTP_CLIENT_ERROR_CANNOT_ACCESS_LOCAL_FILE);
								fclose($file);
								return($error);
							}
							$this->request_body.=$block;
						}
						fclose($file);
					}
					else
						return("5 it was not specified a valid file or data body stream element at position ".$part);
				}
			}
			if(!IsSet($this->request_headers["Content-Type"]))
				$this->request_headers["Content-Type"]="application/octet-stream".(IsSet($arguments["CharSet"]) ? "; charset=".$arguments["CharSet"] : "");
		}
		if(IsSet($arguments["AuthUser"]))
			$this->request_user=$arguments["AuthUser"];
		elseif(IsSet($this->user))
			$this->request_user=$this->user;
		if(IsSet($arguments["AuthPassword"]))
			$this->request_password=$arguments["AuthPassword"];
		elseif(IsSet($this->password))
			$this->request_password=$this->password;
		if(IsSet($arguments["AuthRealm"]))
			$this->request_realm=$arguments["AuthRealm"];
		elseif(IsSet($this->realm))
			$this->request_realm=$this->realm;
		if(IsSet($arguments["AuthWorkstation"]))
			$this->request_workstation=$arguments["AuthWorkstation"];
		elseif(IsSet($this->workstation))
			$this->request_workstation=$this->workstation;
		if(strlen($this->proxy_host_name)==0
		|| $connect)
			$request_uri=$this->request_uri;
		else
		{
			switch(strtolower($this->protocol))
			{
				case "http":
					$default_port=80;
					break;
				case "https":
					$default_port=443;
					break;
			}
			$request_uri=strtolower($this->protocol)."://".$this->host_name.(($this->host_port==0 || $this->host_port==$default_port) ? "" : ":".$this->host_port).$this->request_uri;
		}
		if($this->use_curl)
		{
			$m = array();
			$version=(GetType($v=curl_version())=="array" ? (IsSet($v["version"]) ? $v["version"] : "0.0.0") : (preg_match("/^libcurl\\/([0-9]+\\.[0-9]+\\.[0-9]+)/",$v,$m) ? $m[1] : "0.0.0"));
			$curl_version=100000*intval($this->Tokenize($version,"."))+1000*intval($this->Tokenize("."))+intval($this->Tokenize(""));
			$protocol_version=($curl_version<713002 ? "1.0" : $this->protocol_version);
		}
		else
			$protocol_version=$this->protocol_version;
		$this->request=$this->request_method." ".$request_uri." HTTP/".$protocol_version;
		if($body_length
		|| ($body_length=strlen($this->request_body))
		|| !strcmp($this->request_method, 'POST'))
			$this->request_headers["Content-Length"]=$body_length;
		for($headers=array(),$host_set=0,Reset($this->request_headers),$header=0;$header<count($this->request_headers);Next($this->request_headers),$header++)
		{
			$header_name=Key($this->request_headers);
			$header_value=$this->request_headers[$header_name];
			if(GetType($header_value)=="array")
			{
				for(Reset($header_value),$value=0;$value<count($header_value);Next($header_value),$value++)
					$headers[]=$header_name.": ".$header_value[Key($header_value)];
			}
			else
				$headers[]=$header_name.": ".$header_value;
			if(strtolower(Key($this->request_headers))=="host")
			{
				$this->request_host=strtolower($header_value);
				$host_set=1;
			}
		}
		if(!$host_set)
		{
			$headers[]="Host: ".$this->host_name;
			$this->request_host=strtolower($this->host_name);
		}
		if(count($this->cookies))
		{
			$cookies=array();
			$this->PickCookies($cookies,0);
			if(strtolower($this->protocol)=="https")
				$this->PickCookies($cookies,1);
			if(count($cookies))
			{
				$h=count($headers);
				$headers[$h]="Cookie:";
				for(Reset($cookies),$cookie=0;$cookie<count($cookies);Next($cookies),$cookie++)
				{
					$cookie_name=Key($cookies);
					$headers[$h].=" ".$cookie_name."=".$cookies[$cookie_name]["value"].";";
				}
			}
		}
		$next_state = "RequestSent";
		if($this->use_curl)
		{
			if(IsSet($arguments['StreamRequest']))
				return($this->SetError("Streaming request data is not supported when using Curl", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			if($body_length
			&& strlen($this->request_body)==0)
			{
				for($request_body="",$success=1,$part=0;$part<count($post_parts);$part++)
				{
					$request_body.=$post_parts[$part]["HEADERS"].$post_parts[$part]["DATA"];
					if(IsSet($post_parts[$part]["FILENAME"]))
					{
						if(!($file=@fopen($post_parts[$part]["FILENAME"],"rb")))
						{
							$php_errormsg = '';
							$this->SetPHPError("could not open upload file ".$post_parts[$part]["FILENAME"], $php_errormsg, self::HTTP_CLIENT_ERROR_CANNOT_ACCESS_LOCAL_FILE);
							$success=0;
							break;
						}
						while(!feof($file))
						{
							if(GetType($block=@fread($file,$this->file_buffer_length))!="string")
							{
								$this->SetPHPError("could not read upload file", $php_errormsg, self::HTTP_CLIENT_ERROR_CANNOT_ACCESS_LOCAL_FILE);
								$success=0;
								break;
							}
							$request_body.=$block;
						}
						fclose($file);
						if(!$success)
							break;
					}
					$request_body.="\r\n";
				}
				$request_body.="--".$boundary."--\r\n";
			}
			else
				$request_body=$this->request_body;
			curl_setopt($this->connection,CURLOPT_HEADER,1);
			curl_setopt($this->connection,CURLOPT_RETURNTRANSFER,1);
			if($this->timeout)
				curl_setopt($this->connection,CURLOPT_TIMEOUT,$this->timeout);
			curl_setopt($this->connection,CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($this->connection,CURLOPT_SSL_VERIFYHOST,0);
			$request=$this->request."\r\n".implode("\r\n",$headers)."\r\n\r\n".$request_body;
			curl_setopt($this->connection,CURLOPT_CUSTOMREQUEST,$request);
			if($this->debug)
				$this->OutputDebug("C ".$request);
			if(!($success=(strlen($this->response=@curl_exec($this->connection))!=0)))
			{
				$error=curl_error($this->connection);
				$this->SetError("Could not execute the request".(strlen($error) ? ": ".$error : ""), self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE);
			}
		}
		else
		{
			if(($success=$this->PutLine($this->request)))
			{
				for($header=0;$header<count($headers);$header++)
				{
					if(!$success=$this->PutLine($headers[$header]))
						break;
				}
				if($success
				&& ($success=$this->PutLine("")))
				{
					if(IsSet($arguments['StreamRequest']))
						$next_state = "SendingRequestBody";
					elseif($body_length)
					{
						if(strlen($this->request_body))
							$success=$this->PutData($this->request_body);
						else
						{
							for($part=0;$part<count($post_parts);$part++)
							{
								if(!($success=$this->PutData($post_parts[$part]["HEADERS"]))
								|| !($success=$this->PutData($post_parts[$part]["DATA"])))
									break;
								if(IsSet($post_parts[$part]["FILENAME"]))
								{
									if(!($file=@fopen($post_parts[$part]["FILENAME"],"rb")))
									{
										$this->SetPHPError("could not open upload file ".$post_parts[$part]["FILENAME"], $php_errormsg, self::HTTP_CLIENT_ERROR_CANNOT_ACCESS_LOCAL_FILE);
										$success=0;
										break;
									}
									while(!feof($file))
									{
										if(GetType($block=@fread($file,$this->file_buffer_length))!="string")
										{
											$this->SetPHPError("could not read upload file", $php_errormsg, self::HTTP_CLIENT_ERROR_CANNOT_ACCESS_LOCAL_FILE);
											$success=0;
											break;
										}
										if(!($success=$this->PutData($block)))
											break;
									}
									fclose($file);
									if(!$success)
										break;
								}
								if(!($success=$this->PutLine("")))
									break;
							}
							if($success)
								$success=$this->PutLine("--".$boundary."--");
						}
						if($success)
							$sucess=$this->FlushData();
					}
				}
			}
		}
		if(!$success)
			return($this->SetError("could not send the HTTP request: ".$this->error, $this->error_code));
		$this->state=$next_state;
		return("");
	}

	Function SetCookie($name, $value, $expires="" , $path="/" , $domain="" , $secure=0, $verbatim=0)
	{
		if(strlen($this->error))
			return($this->error);
		if(strlen($name)==0)
			return($this->SetError("it was not specified a valid cookie name", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		if(strlen($path)==0
		|| strcmp($path[0],"/"))
			return($this->SetError($path." is not a valid path for setting cookie ".$name, self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		if($domain==""
		|| !strpos($domain,".",$domain[0]=="." ? 1 : 0))
			return($this->SetError($domain." is not a valid domain for setting cookie ".$name, self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		$domain=strtolower($domain);
		if(!strcmp($domain[0],"."))
			$domain=substr($domain,1);
		if(!$verbatim)
		{
			$name=$this->CookieEncode($name,1);
			$value=$this->CookieEncode($value,0);
		}
		$secure=intval($secure);
		$this->cookies[$secure][$domain][$path][$name]=array(
			"name"=>$name,
			"value"=>$value,
			"domain"=>$domain,
			"path"=>$path,
			"expires"=>$expires,
			"secure"=>$secure
		);
		return("");
	}

	Function SendRequestBody($data, $end_of_data)
	{
		if(strlen($this->error))
			return($this->error);
		switch($this->state)
		{
			case "Disconnected":
				return($this->SetError("connection was not yet established", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			case "Connected":
			case "ConnectedToProxy":
				return($this->SetError("request was not sent", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			case "SendingRequestBody":
				break;
			case "RequestSent":
				return($this->SetError("request body was already sent", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			default:
				return($this->SetError("can not send the request body in the current connection state", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		}
		$length = strlen($data);
		if($length)
		{
			$size = dechex($length)."\r\n";
			if(!$this->PutData($size)
			|| !$this->PutData($data))
				return($this->error);
		}
		if($end_of_data)
		{
			$size = "0\r\n";
			if(!$this->PutData($size))
				return($this->error);
			$this->state = "RequestSent";
		}
		return("");
	}

	Function ReadReplyHeadersResponse(&$headers)
	{
		$headers=array();
		if(strlen($this->error))
			return($this->error);
		switch($this->state)
		{
			case "Disconnected":
				return($this->SetError("connection was not yet established", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			case "Connected":
				return($this->SetError("request was not sent", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			case "ConnectedToProxy":
				return($this->SetError("connection from the remote server from the proxy was not yet established", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			case "SendingRequestBody":
				return($this->SetError("request body data was not completely sent", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			case "ConnectSent":
				$connect = 1;
				break;
			case "RequestSent":
				$connect = 0;
				break;
			default:
				return($this->SetError("can not get request headers in the current connection state", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		}
		$this->content_length=$this->read_length=$this->read_response=$this->remaining_chunk=0;
		$this->content_length_set=$this->chunked=$this->last_chunk_read=$chunked=0;
		$this->force_close = $this->connection_close=0;
		for($this->response_status="";;)
		{
			$line=$this->GetLine();
			if(GetType($line)!="string")
				return($this->SetError("could not read request reply: ".$this->error, $this->error_code));
			if(strlen($this->response_status)==0)
			{
				$matches = array();
				if(!preg_match($match="/^http\\/[0-9]+\\.[0-9]+[ \t]+([0-9]+)[ \t]*(.*)\$/i",$line,$matches))
					return($this->SetError("it was received an unexpected HTTP response status", self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE));
				$this->response_status=$matches[1];
				$this->response_message=$matches[2];
				if($this->response_status == 204)
				{
					$this->content_length = 0;
					$this->content_length_set = 1;
				}
			}
			if($line=="")
			{
				if(strlen($this->response_status)==0)
					return($this->SetError("it was not received HTTP response status", self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE));
				$this->state=($connect ? "GotConnectHeaders" : "GotReplyHeaders");
				break;
			}
			$header_name=strtolower($this->Tokenize($line,":"));
			$header_value=Trim(Chop($this->Tokenize("\r\n")));
			if(IsSet($headers[$header_name]))
			{
				if(GetType($headers[$header_name])=="string")
					$headers[$header_name]=array($headers[$header_name]);
				$headers[$header_name][]=$header_value;
			}
			else
				$headers[$header_name]=$header_value;
			if(!$connect)
			{
				switch($header_name)
				{
					case "content-length":
						$this->content_length=intval($headers[$header_name]);
						$this->content_length_set=1;
						break;
					case "transfer-encoding":
						$encoding=$this->Tokenize($header_value,"; \t");
						if(!$this->use_curl
						&& !strcmp($encoding,"chunked"))
							$chunked=1;
						break;
					case "set-cookie":
						if($this->support_cookies)
						{
							if(GetType($headers[$header_name])=="array")
								$cookie_headers=$headers[$header_name];
							else
								$cookie_headers=array($headers[$header_name]);
							for($cookie=0;$cookie<count($cookie_headers);$cookie++)
							{
								$cookie_name=trim($this->Tokenize($cookie_headers[$cookie],"="));
								$cookie_value=$this->Tokenize(";");
								$domain=$this->request_host;
								$path="/";
								$expires="";
								$secure=0;
								while(($name = strtolower(trim(UrlDecode($this->Tokenize("=")))))!="")
								{
									$value=UrlDecode($this->Tokenize(";"));
									switch($name)
									{
										case "domain":
											$domain=$value;
											break;
										case "path":
											$path=$value;
											break;
										case "expires":
											if(preg_match("/^((Mon|Monday|Tue|Tuesday|Wed|Wednesday|Thu|Thursday|Fri|Friday|Sat|Saturday|Sun|Sunday), )?([0-9]{2})\\-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\\-([0-9]{2,4}) ([0-9]{2})\\:([0-9]{2})\\:([0-9]{2}) GMT\$/",$value,$matches))
											{
												$year=intval($matches[5]);
												if($year<1900)
													$year+=($year<70 ? 2000 : 1900);
												$expires="$year-".$this->months[$matches[4]]."-".$matches[3]." ".$matches[6].":".$matches[7].":".$matches[8];
											}
											break;
										case "secure":
											$secure=1;
											break;
									}
								}
								if(strlen($this->SetCookie($cookie_name, $cookie_value, $expires, $path , $domain, $secure, 1)))
									$this->error="";
							}
						}
						break;
					case "connection":
						$this->force_close = $this->connection_close=!strcmp(strtolower($header_value),"close");
						break;
				}
			}
		}
		$this->chunked=$chunked;
		if($this->content_length_set)
			$this->connection_close=0;
		return("");
	}

	Function Redirect(&$headers)
	{
		if($this->follow_redirect)
		{
			if(!IsSet($headers["location"])
			|| (GetType($headers["location"])!="array"
			&& strlen($location=$headers["location"])==0)
			|| (GetType($headers["location"])=="array"
			&& strlen($location=$headers["location"][0])==0))
				return($this->SetError("it was received a redirect without location URL", self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE));
			if(strcmp($location[0],"/"))
			{
				if(!($location_arguments=@parse_url($location)))
					return($this->SetError("the server did not return a valid redirection location URL", self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE));
				if(!IsSet($location_arguments["scheme"]))
					$location=((GetType($end=strrpos($this->request_uri,"/"))=="integer" && $end>1) ? substr($this->request_uri,0,$end) : "")."/".$location;
			}
			if(!strcmp($location[0],"/"))
				$location=$this->protocol."://".$this->host_name.($this->host_port ? ":".$this->host_port : "").$location;
			$arguments = array();
			$error=$this->GetRequestArguments($location,$arguments);
			if(strlen($error))
				return($this->SetError("could not process redirect url: ".$error, self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE));
			$arguments["RequestMethod"]="GET";
			if(strlen($error=$this->Close())==0
			&& strlen($error=$this->Open($arguments))==0
			&& strlen($error=$this->SendRequest($arguments))==0)
			{
				$this->redirection_level++;
				if($this->redirection_level>$this->redirection_limit)
				{
					$error="it was exceeded the limit of request redirections";
					$this->error_code = self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE;
				}
				else
					$error=$this->ReadReplyHeaders($headers);
				$this->redirection_level--;
			}
			if(strlen($error))
				return($this->SetError($error, $this->error_code));
		}
		return("");
	}

	Function Authenticate(&$headers, $proxy, &$proxy_authorization, &$user, &$password, &$realm, &$workstation)
	{
		if($proxy)
		{
			$authenticate_header="proxy-authenticate";
			$authorization_header="Proxy-Authorization";
			$authenticate_status="407";
			$authentication_mechanism=$this->proxy_authentication_mechanism;
		}
		else
		{
			$authenticate_header="www-authenticate";
			$authorization_header="Authorization";
			$authenticate_status="401";
			$authentication_mechanism=$this->authentication_mechanism;
		}
		if(IsSet($headers[$authenticate_header])
		&& $this->sasl_authenticate)
		{
			if(function_exists("class_exists")
			&& !class_exists("sasl_client_class"))
				return($this->SetError("the SASL client class needs to be loaded to be able to authenticate".($proxy ? " with the proxy server" : "")." and access this site", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			if(GetType($headers[$authenticate_header])=="array")
				$authenticate=$headers[$authenticate_header];
			else
				$authenticate=array($headers[$authenticate_header]);
			for($response="", $mechanisms=array(),$m=0;$m<count($authenticate);$m++)
			{
				$mechanism=$this->Tokenize($authenticate[$m]," ");
				$response=$this->Tokenize("");
				if(strlen($authentication_mechanism))
				{
					if(!strcmp($authentication_mechanism,$mechanism))
					{
						$mechanisms[]=$mechanism;
						break;
					}
				}
				else
					$mechanisms[]=$mechanism;
			}
			$sasl=new sasl_client_class;
			if(IsSet($user))
				$sasl->SetCredential("user",$user);
			if(IsSet($password))
				$sasl->SetCredential("password",$password);
			if(IsSet($realm))
				$sasl->SetCredential("realm",$realm);
			if(IsSet($workstation))
				$sasl->SetCredential("workstation",$workstation);
			$sasl->SetCredential("uri",$this->request_uri);
			$sasl->SetCredential("method",$this->request_method);
			$sasl->SetCredential("session",$this->session);
			do
			{
				$message = null;
				$interactions = null;
				$status=$sasl->Start($mechanisms,$message,$interactions);
			}
			while($status==SASL_INTERACT);
			switch($status)
			{
				case SASL_CONTINUE:
					break;
				case SASL_NOMECH:
					return($this->SetError(($proxy ? "proxy " : "")."authentication error: ".(strlen($authentication_mechanism) ? "authentication mechanism ".$authentication_mechanism." may not be used: " : "").$sasl->error, self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
				default:
					return($this->SetError("Could not start the SASL ".($proxy ? "proxy " : "")."authentication client: ".$sasl->error, self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			}
			if($proxy >= 0)
			{
				for(;;)
				{
					$body = '';
					if(strlen($error=$this->ReadReplyBody($body,$this->file_buffer_length)))
						return($error);
					if(strlen($body)==0)
						break;
				}
			}
			$authorization_value=$sasl->mechanism.(IsSet($message) ? " ".($sasl->encode_response ? base64_encode($message) : $message) : "");
			$request_arguments=$this->request_arguments;
			$arguments=$request_arguments;
			$arguments["Headers"][$authorization_header]=$authorization_value;
			if(!$proxy
			&& strlen($proxy_authorization))
				$arguments["Headers"]["Proxy-Authorization"]=$proxy_authorization;
			if(strlen($error=$this->Close())
			|| strlen($error=$this->Open($arguments)))
				return($this->SetError($error, $this->error_code));
			$authenticated=0;
			if(IsSet($message))
			{
				if($proxy < 0)
				{
					if(strlen($error=$this->ConnectFromProxy($arguments, $headers)))
						return($this->SetError($error, $this->error_code));
				}
				else
				{
					if(strlen($error=$this->SendRequest($arguments))
					|| strlen($error=$this->ReadReplyHeadersResponse($headers)))
						return($this->SetError($error, $this->error_code));
				}
				if(!IsSet($headers[$authenticate_header]))
					$authenticate=array();
				elseif(GetType($headers[$authenticate_header])=="array")
					$authenticate=$headers[$authenticate_header];
				else
					$authenticate=array($headers[$authenticate_header]);
				for($mechanism=0;$mechanism<count($authenticate);$mechanism++)
				{
					if(!strcmp($this->Tokenize($authenticate[$mechanism]," "),$sasl->mechanism))
					{
						$response=$this->Tokenize("");
						break;
					}
				}
				switch($this->response_status)
				{
					case $authenticate_status:
						break;
					case "301":
					case "302":
					case "303":
					case "307":
						if($proxy >= 0)
							return($this->Redirect($headers));
					default:
						if(intval($this->response_status/100)==2)
						{
							if($proxy)
								$proxy_authorization=$authorization_value;
							$authenticated=1;
							break;
						}
						if($proxy
						&& !strcmp($this->response_status,"401"))
						{
							$proxy_authorization=$authorization_value;
							$authenticated=1;
							break;
						}
						return($this->SetError(($proxy ? "proxy " : "")."authentication error: ".$this->response_status." ".$this->response_message, self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE));
				}
			}
			for(;!$authenticated;)
			{
				do
				{
					$status=$sasl->Step($response,$message,$interactions);
				}
				while($status==SASL_INTERACT);
				switch($status)
				{
					case SASL_CONTINUE:
						$authorization_value=$sasl->mechanism.(IsSet($message) ? " ".($sasl->encode_response ? base64_encode($message) : $message) : "");
						$arguments=$request_arguments;
						$arguments["Headers"][$authorization_header]=$authorization_value;
						if(!$proxy
						&& strlen($proxy_authorization))
							$arguments["Headers"]["Proxy-Authorization"]=$proxy_authorization;
						if($proxy < 0)
						{
							if(strlen($error=$this->ConnectFromProxy($arguments, $headers)))
								return($this->SetError($error, $this->error_code));
						}
						else
						{
							if(strlen($error=$this->SendRequest($arguments))
							|| strlen($error=$this->ReadReplyHeadersResponse($headers)))
								return($this->SetError($error, $this->error_code));
						}
						switch($this->response_status)
						{
							case $authenticate_status:
								if(GetType($headers[$authenticate_header])=="array")
									$authenticate=$headers[$authenticate_header];
								else
									$authenticate=array($headers[$authenticate_header]);
								for($response="",$mechanism=0;$mechanism<count($authenticate);$mechanism++)
								{
									if(!strcmp($this->Tokenize($authenticate[$mechanism]," "),$sasl->mechanism))
									{
										$response=$this->Tokenize("");
										break;
									}
								}
								if($proxy >= 0)
								{
									for(;;)
									{
										if(strlen($error=$this->ReadReplyBody($body,$this->file_buffer_length)))
											return($error);
										if(strlen($body)==0)
											break;
									}
								}
								$this->state="Connected";
								break;
							case "301":
							case "302":
							case "303":
							case "307":
								if($proxy >= 0)
									return($this->Redirect($headers));
							default:
								if(intval($this->response_status/100)==2)
								{
									if($proxy)
										$proxy_authorization=$authorization_value;
									$authenticated=1;
									break;
								}
								if($proxy
								&& !strcmp($this->response_status,"401"))
								{
									$proxy_authorization=$authorization_value;
									$authenticated=1;
									break;
								}
								return($this->SetError(($proxy ? "proxy " : "")."authentication error: ".$this->response_status." ".$this->response_message));
						}
						break;
					default:
						return($this->SetError("Could not process the SASL ".($proxy ? "proxy " : "")."authentication step: ".$sasl->error, self::HTTP_CLIENT_ERROR_PROTOCOL_FAILURE));
				}
			}
		}
		return("");
	}

	Function ReadReplyHeaders(&$headers)
	{
		if(strlen($error=$this->ReadReplyHeadersResponse($headers)))
			return($error);
		$proxy_authorization="";
		while(!strcmp($this->response_status, "100"))
		{
			$this->state="RequestSent";
			if(strlen($error=$this->ReadReplyHeadersResponse($headers)))
				return($error);
		}
		switch($this->response_status)
		{
			case "301":
			case "302":
			case "303":
			case "307":
				if(strlen($error=$this->Redirect($headers)))
					return($error);
				break;
			case "407":
				if(strlen($error=$this->Authenticate($headers, 1, $proxy_authorization, $this->proxy_request_user, $this->proxy_request_password, $this->proxy_request_realm, $this->proxy_request_workstation)))
					return($error);
				if(strcmp($this->response_status,"401"))
					return("");
			case "401":
				return($this->Authenticate($headers, 0, $proxy_authorization, $this->request_user, $this->request_password, $this->request_realm, $this->request_workstation));
		}
		return("");
	}

	Function ReadReplyBody(&$body,$length)
	{
		$body="";
		if(strlen($this->error))
			return($this->error);
		switch($this->state)
		{
			case "Disconnected":
				return($this->SetError("connection was not yet established", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			case "Connected":
			case "ConnectedToProxy":
				return($this->SetError("request was not sent", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			case "RequestSent":
				$headers = array();
				if(($error=$this->ReadReplyHeaders($headers))!="")
					return($error);
				break;
			case "GotReplyHeaders":
				break;
			case 'ResponseReceived':
				$body = '';
				return('');
			default:
				return($this->SetError("can not get request headers in the current connection state", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
		}
		if($this->content_length_set)
			$length=min($this->content_length-$this->read_length,$length);
		$body = '';
		if($length>0)
		{
			if(!$this->EndOfInput()
			&& ($body=$this->ReadBytes($length))=="")
			{
				if(strlen($this->error))
					return($this->SetError("could not get the request reply body: ".$this->error, $this->error_code));
			}
			$this->read_length+=strlen($body);
			if($this->EndOfInput())
				$this->state = 'ResponseReceived';
		}
		return("");
	}

	Function ReadWholeReplyBody(&$body)
	{
		$body = '';
		for(;;)
		{
			$body = '';
			$block = '';
			if(strlen($error = $this->ReadReplyBody($block, $this->file_buffer_length)))
				return($error);
			if(strlen($block) == 0)
				return('');
			$body .= $block;
		}
	}

	Function ReadWholeReplyIntoTemporaryFile(&$file)
	{
		if(!($file = tmpfile())) {
			$php_errormsg = '';
			return $this->SetPHPError('could not create the temporary file to save the response', $php_errormsg, self::HTTP_CLIENT_ERROR_CANNOT_ACCESS_LOCAL_FILE);
		}
		for(;;)
		{
			$block = '';
			if(strlen($error = $this->ReadReplyBody($block, $this->file_buffer_length)))
			{
				fclose($file);
				return($error);
			}
			if(strlen($block) == 0)
			{
				if(@fseek($file, 0) != 0)
				{
					$error = $this->SetPHPError('could not seek to the beginning of temporary file with the response', $php_errormsg, self::HTTP_CLIENT_ERROR_CANNOT_ACCESS_LOCAL_FILE);
					fclose($file);
					return $error;
				}
				return('');
			}
			if(!@fwrite($file, $block))
			{
				$php_errormsg = '';
				$error = $this->SetPHPError('could not write to the temporary file to save the response', $php_errormsg, self::HTTP_CLIENT_ERROR_CANNOT_ACCESS_LOCAL_FILE);
				fclose($file);
				return $error;
			}
		}
	}

	Function SaveCookies(&$cookies, $domain='', $secure_only=0, $persistent_only=0)
	{
		$now=gmdate("Y-m-d H-i-s");
		$cookies=array();
		for($secure_cookies=0,Reset($this->cookies);$secure_cookies<count($this->cookies);Next($this->cookies),$secure_cookies++)
		{
			$secure=Key($this->cookies);
			if(!$secure_only
			|| $secure)
			{
				for($cookie_domain=0,Reset($this->cookies[$secure]);$cookie_domain<count($this->cookies[$secure]);Next($this->cookies[$secure]),$cookie_domain++)
				{
					$domain_pattern=Key($this->cookies[$secure]);
					$match=strlen($domain)-strlen($domain_pattern);
					if(strlen($domain)==0
					|| ($match>=0
					&& !strcmp($domain_pattern,substr($domain,$match))
					&& ($match==0
					|| $domain_pattern[0]=="."
					|| $domain[$match-1]==".")))
					{
						for(Reset($this->cookies[$secure][$domain_pattern]),$path_part=0;$path_part<count($this->cookies[$secure][$domain_pattern]);Next($this->cookies[$secure][$domain_pattern]),$path_part++)
						{
							$path=Key($this->cookies[$secure][$domain_pattern]);
							for(Reset($this->cookies[$secure][$domain_pattern][$path]),$cookie=0;$cookie<count($this->cookies[$secure][$domain_pattern][$path]);Next($this->cookies[$secure][$domain_pattern][$path]),$cookie++)
							{
								$cookie_name=Key($this->cookies[$secure][$domain_pattern][$path]);
								$expires=$this->cookies[$secure][$domain_pattern][$path][$cookie_name]["expires"];
								if((!$persistent_only
								&& strlen($expires)==0)
								|| (strlen($expires)
								&& strcmp($now,$expires)<0))
									$cookies[$secure][$domain_pattern][$path][$cookie_name]=$this->cookies[$secure][$domain_pattern][$path][$cookie_name];
							}
						}
					}
				}
			}
		}
	}

	Function SavePersistentCookies(&$cookies, $domain='', $secure_only=0)
	{
		$this->SaveCookies($cookies, $domain, $secure_only, 1);
	}

	Function GetPersistentCookies(&$cookies, $domain='', $secure_only=0)
	{
		$this->SavePersistentCookies($cookies, $domain, $secure_only);
	}

	Function RestoreCookies($cookies, $clear=1)
	{
		$new_cookies=($clear ? array() : $this->cookies);
		for($secure_cookies=0, Reset($cookies); $secure_cookies<count($cookies); Next($cookies), $secure_cookies++)
		{
			$secure=Key($cookies);
			if(GetType($secure)!="integer")
				return($this->SetError("invalid cookie secure value type (".serialize($secure).")", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
			for($cookie_domain=0,Reset($cookies[$secure]);$cookie_domain<count($cookies[$secure]);Next($cookies[$secure]),$cookie_domain++)
			{
				$domain_pattern=Key($cookies[$secure]);
				if(GetType($domain_pattern)!="string")
					return($this->SetError("invalid cookie domain value type (".serialize($domain_pattern).")", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
				for(Reset($cookies[$secure][$domain_pattern]),$path_part=0;$path_part<count($cookies[$secure][$domain_pattern]);Next($cookies[$secure][$domain_pattern]),$path_part++)
				{
					$path=Key($cookies[$secure][$domain_pattern]);
					if(GetType($path)!="string"
					|| strcmp(substr($path, 0, 1), "/"))
						return($this->SetError("invalid cookie path value type (".serialize($path).")", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
					for(Reset($cookies[$secure][$domain_pattern][$path]),$cookie=0;$cookie<count($cookies[$secure][$domain_pattern][$path]);Next($cookies[$secure][$domain_pattern][$path]),$cookie++)
					{
						$cookie_name=Key($cookies[$secure][$domain_pattern][$path]);
						$expires=$cookies[$secure][$domain_pattern][$path][$cookie_name]["expires"];
						$value=$cookies[$secure][$domain_pattern][$path][$cookie_name]["value"];
						if(GetType($expires)!="string"
						|| (strlen($expires)
						&& !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\$/", $expires)))
							return($this->SetError("invalid cookie expiry value type (".serialize($expires).")", self::HTTP_CLIENT_ERROR_INVALID_PARAMETERS));
						$new_cookies[$secure][$domain_pattern][$path][$cookie_name]=array(
							"name"=>$cookie_name,
							"value"=>$value,
							"domain"=>$domain_pattern,
							"path"=>$path,
							"expires"=>$expires,
							"secure"=>$secure
						);
					}
				}
			}
		}
		$this->cookies=$new_cookies;
		return("");
	}
};
