<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2022 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// Works with composer.json
// "robrichards/xmlseclibs": "^3.1"
// or
// "selective/xmldsig": "^2.4"

function oidplus_xml_verify($xml_content, $pubkey) {
	require_once __DIR__.'/vendor/autoload.php';

	$sig_ok = false;
	if (class_exists('\RobRichards\XMLSecLibs\XMLSecurityDSig')) {
		// Template: https://github.com/robrichards/xmlseclibs/blob/master/tests/xmlsec-verify.phpt

		$doc = new DOMDocument();
		$doc->loadXML($xml_content);

		$objXMLSecDSig = new \RobRichards\XMLSecLibs\XMLSecurityDSig();

		$objDSig = $objXMLSecDSig->locateSignature($doc);
		if (! $objDSig) {
			throw new Exception("Cannot locate Signature Node");
		}
		$objXMLSecDSig->canonicalizeSignedInfo();
		$objXMLSecDSig->idKeys = array('wsu:Id');
		$objXMLSecDSig->idNS = array('wsu'=>'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd');

		$retVal = $objXMLSecDSig->validateReference();
		if (! $retVal) {
			throw new Exception("Reference Validation Failed");
		}

		$objKey = $objXMLSecDSig->locateKey();
		if (! $objKey ) {
			throw new Exception("We have no idea about the key");
		}
		/*
		$key = NULL;

		$objKeyInfo = \RobRichards\XMLSecLibs\XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig);
		if (! $objKeyInfo->key && empty($key)) {
			$objKey->loadKey($pubkey, false);
		}
		*/
		$objKey->loadKey($pubkey, /*isfile*/false);

		$sig_ok = $objXMLSecDSig->verify($objKey) === 1;
	} else if (class_exists("\Selective\XmlDSig\XmlSignatureValidator")) {
		$signatureValidator = new \Selective\XmlDSig\XmlSignatureValidator();
		$signatureValidator->loadPublicKey($pubkey);
		$sig_ok = $signatureValidator->verifyXml($xml_content);
	} else {
		assert(false);
	}

	if (!$sig_ok) {
		throw new Exception("Signature verification failed!");
	}
}

function oidplus_xml_sign($xml_content, $privkey, $pubkey) {
	require_once __DIR__.'/vendor/autoload.php';

	if (class_exists('\RobRichards\XMLSecLibs\XMLSecurityDSig')) {
		// Template: https://github.com/robrichards/xmlseclibs/blob/master/README.md

		// Load the XML to be signed
		$doc = new DOMDocument();
		$doc->loadXML($xml_content);

		// Create a new Security object
		$objDSig = new \RobRichards\XMLSecLibs\XMLSecurityDSig();
		// Use the c14n exclusive canonicalization
		$objDSig->setCanonicalMethod(\RobRichards\XMLSecLibs\XMLSecurityDSig::EXC_C14N);
		// Sign using SHA-512
		$objDSig->addReference(
		    $doc,
		    \RobRichards\XMLSecLibs\XMLSecurityDSig::SHA512,
		    array('http://www.w3.org/2000/09/xmldsig#enveloped-signature')
		);

		// Create a new (private) Security key
		$objKey = new \RobRichards\XMLSecLibs\XMLSecurityKey(\RobRichards\XMLSecLibs\XMLSecurityKey::RSA_SHA512, array('type'=>'private'));
		/*
		If key has a passphrase, set it using
		$objKey->passphrase = '<passphrase>';
		*/
		// Load the private key
		$objKey->loadKey($privkey, /*isfile*/false);

		// Sign the XML file
		$objDSig->sign($objKey);

		// TODO: Selective\XmlDSig has "KeyInfo" with RSAKeyValue (showing Modulus and Exponent)
		//       while RobRichards\XMLSecLibs has no "KeyInfo".
		//       We can't use $objDSig->add509Cert, because we have no X.509 certificate, just pubkey and privkey...

		// Append the signature to the XML
		$objDSig->appendSignature($doc->documentElement);

		// Save the signed XML
		$xml_signed = $doc->saveXML();
	} else if (class_exists("\Selective\XmlDSig\XmlSigner")) {
		$xmlSigner = new \Selective\XmlDSig\XmlSigner();
		$xmlSigner->loadPrivateKey($privkey, '');
		$xmlSigner->setReferenceUri(''); // Optional: Set reference URI (TODO?)

		/* @phpstan-ignore-next-line */
		$xml_signed = $xmlSigner->signXml($xml_content, \Selective\XmlDSig\DigestAlgorithmType::SHA512);
	} else {
		assert(false);
		return false;
	}

	oidplus_xml_verify($xml_signed, $pubkey);
	return $xml_signed;
}
