<?php
function script($script)
{
	echo "<script>\n$script\n</script>";
}

function sorter($key)
{
	return function ($a, $b) use ($key)
	{
		return strnatcmp(strtolower($a[$key]), strtolower($b[$key]));
	};
}

function split_nth($str, $delim, $n)
{
	return array_map(function($p) use ($delim) {
		return implode($delim, $p);
	}, array_chunk(explode($delim, $str), $n));
}

function strip_nl($str)
{
	return str_replace(array("\r", "\n"), '', $str);
}

function br2nl($string)
{
	return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}

function decrypt($string)
{
	global $key_enc_dec;
	$data = base64_decode($string);
	$iv = substr($data, 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC));
	
	$string = rtrim
	(
		mcrypt_decrypt
		(
			MCRYPT_RIJNDAEL_256,
			hash('sha256', $key_enc_dec, true),
			substr($data, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)),
			MCRYPT_MODE_CBC,
			$iv
		),
		"\0"
	);
	return $string;
}

function encrypt($string)
{
	global $key_enc_dec;
	$iv = mcrypt_create_iv
	(
		mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC),
		MCRYPT_DEV_URANDOM
	);

	$string = base64_encode
	(
		$iv.
		mcrypt_encrypt
		(
			MCRYPT_RIJNDAEL_256,
			hash('sha256', $key_enc_dec, true),
			$string,
			MCRYPT_MODE_CBC,
			$iv
		)
	);
	return $string;
}
?>
