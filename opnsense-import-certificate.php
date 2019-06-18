<?php

/**
 * Import SSL certificates from a pre-determined place on the filesystem.
 * Once imported, set them for use in the GUI
 */

if (empty($argc)) {
	echo "Only accessible from the CLI.\r\n";
	die(1);
}

if ($argc != 4) {
	echo "Usage: php " . $argv[0] . " /path/to/certificate.crt /path/to/private/key.pem cert_hostname.domain.tld\r\n";
	die(1);
}

require_once "config.inc";
require_once "certs.inc";
require_once "util.inc";
require_once "filter.inc";

$certificate = trim(file_get_contents($argv[1]));
$key = trim(file_get_contents($argv[2]));
$hostname = trim($argv[3]);

// Do some quick verification of the certificate, similar to what the GUI does
if (empty($certificate)) {
	echo "The certificate is empty.\r\n";
	die(1);
}
if (!strstr($certificate, "BEGIN CERTIFICATE") || !strstr($certificate, "END CERTIFICATE")) {
	echo "This certificate does not appear to be valid.\r\nOr: cert and privkey args switched?\r\n";
	die(1);
}

// Verification that the certificate matches
if (empty($key)) {
	echo "The key is empty.\r\n";
	die(1);
}
if (trim(cert_get_subject($certificate, false)) != "CN=".$hostname.",") {
	echo "The certificate subject does not match the hostname $hostname.\r\n".cert_get_subject($certificate, false)."\r\n";
	die(1);
}
if (trim(cert_get_issuer($certificate, false)) != "O=Let's Encrypt, CN=Let's Encrypt Authority X3, C=US,") {
	echo "The certificate issuer does not match the certificate.\r\n".cert_get_issuer($certificate, false)."\r\n";
	die(1);
}

$cert = array();
$cert['refid'] = uniqid();
$cert['descr'] = "Certificate added to opnsense through " . $argv[0] . " on " . date("Y/m/d");

cert_import($cert, $certificate, $key);

// Set up the existing certificate store
// Copied from system_certmanager.php
if (!is_array($config['ca'])) {
	$config['ca'] = array();
}

$a_ca =& $config['ca'];

if (!is_array($config['cert'])) {
	$config['cert'] = array();
}

$a_cert =& $config['cert'];

$internal_ca_count = 0;
foreach ($a_ca as $ca) {
	if ($ca['prv']) {
		$internal_ca_count++;
	}
}

// Check if the certificate we just parsed is already imported (we'll check the certificate portion)
foreach ($a_cert as $existing_cert) {
	if ($existing_cert['crt'] === $cert['crt']) {
		echo "The certificate is already imported.\r\n";
		die(); // exit with a valid error code, as this is intended behaviour
	}
}

// Append the final certificate
$a_cert[] = $cert;

// Write out the updated configuration
write_config();

// Assuming that all worked, we now need to set the new certificate for use in the GUI
$config['system']['webgui']['ssl-certref'] = $cert['refid'];

write_config();

log_error('Web GUI configuration has changed. Restarting now.');
configd_run('webgui restart 2', true);

echo "Completed! New certificate installed.\r\n";
