<?php
/**
 * PayZQ payment method model
 *
 * @category    PayZQ
 * @package     Payment
 * @author      PayZQ
 * @copyright   PayZQ (http://payzq.net)
 */
 
namespace PayZQ\Payment\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class PayZQAPI extends AbstractHelper{

	private static $api_base_url = 'http://test-zms.zertifica.org:7743/api/v1/transactions/';
  private static $key_jwt = 'secret';
	private static $iv = '4242424242424242';

	public static $merchant_key = '';
	public static $secret_key = '';
	public static $test_secret_key = '';
	public static $test_mode = '';

	public function get_key_jwt() {
		return self::$key_jwt;
	}

	public function get_iv() {
		return self::$iv;
	}

	public function get_api_base_url() {
		return self::$api_base_url;
	}

	public function set_test_private_key($test_private_key) {
		self::$test_secret_key = $test_private_key;
	}

	public function set_private_key($private_key) {
		self::$secret_key = $private_key;
	}

	public function set_test_mode($test_mode) {
		self::$test_mode = $test_mode;
	}

	public function set_merchant_key($merchant_key) {
		self::$merchant_key = $merchant_key;
	}

	/**
	 * Format card number.
	 * @param string $number
	 */
	public function clear_card_number($number) {
		return str_replace(' ', '', $number);
	}

	/**
	 * Format card date.
	 * @param string $date
	 */
	public function clear_card_date($date) {
		$str_date = str_replace(array(' ', '/', '\\'), '', $date);
		$str_date = (strlen($str_date) == 6) ? substr($str_date, 0, 2). substr($str_date, -2) : $str_date;
		return $str_date;
	}

	/**
	 * Get card type from number.
	 * @param string $card_number
	 */
	public function get_card_type($card_number) {
		if (preg_match('/^4/', $card_number)) return 'Visa';
		if (preg_match('/^(34|37)/', $card_number)) return 'Amex';
		if (preg_match('/^5[1-5]/', $card_number)) return 'MasterCard';
		if (preg_match('/^6011/', $card_number)) return 'Discover';
		if (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{4,}/', $card_number)) return 'Diners';
		if (preg_match('/^(?:2131|1800|35[0-9]{3})[0-9]{3,}/', $card_number)) return 'Jcb';
	}

	/**
	 * Generate the payzq transaction ID.
	 */
	public function get_payzq_transaction_code() {

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resource->getConnection();
		$tableName = $resource->getTableName('employee'); //gives table name with prefix

		$sql = "SELECT UTC_TIMESTAMP() as time FROM DUAL";
		$result = $connection->fetchAll($sql);

    $host= gethostname();
    $ip = gethostbyname($host);

    $chars = array(' ', '-', '.', ':');
    $ip = str_replace($chars, '', $ip);
    $time = str_replace($chars, '', $result[0]['time']);

    return 'MGT_PAY'.$ip.'ZQ'.$time;
  }

	/**
	 * Get the IP server
	 */
	public function get_ip_server() {
    $host = gethostname();
    $ip = gethostbyname($host);
		return $ip;
  }

	/**
	 * Get secret key.
	 * @return string
	 */
	public function get_secret_key() {
		return (self::$test_mode == 1) ? self::$test_secret_key : self::$secret_key;
	}

	/**
	* cypher data.
	* @param string $json_data
	* @return string
	*/
	public static function cypherData($json_data) {
    $merchant_key = self::$merchant_key;
    $margen = (strlen($json_data) == 16) ? 0 : (intdiv(strlen($json_data), 16) + 1) * 16 - strlen($json_data);
    // AES-128-CFB porque la merchant_key es de 16 bytes
    // the option 3 is not documented in php web page
    $data = openssl_encrypt($json_data.str_repeat('#', $margen), 'aes-128-cbc', $merchant_key, 3, self::$iv);
    $json_compress = gzcompress($data);
    $json_b64 = base64_encode($json_compress);
    $json_utf = utf8_decode($json_b64);
    return $json_utf;
  }

	/**
	* cypher data.
	* @param string $codified_data
	* @return string
	*/
	public static function decodeCypherData($codified_data) {
		$merchant_key = self::$merchant_key;
	  $compressed_data = base64_decode($codified_data);
	  $descompressed_data = gzuncompress($compressed_data);
	  $decrypted_data = openssl_decrypt($descompressed_data , 'aes-128-cbc' , $merchant_key, OPENSSL_ZERO_PADDING, $this->iv );
	  $clean_text = str_replace('#','', $decrypted_data);
	  $data = json_decode($clean_text, true);
	  return $data;
	}

	/**
	* return header to API request
	* @param string $token
	* @return string
	*/
	public function get_header($token) {
		return array(
      'Content-Type: application/json',
      'Authorization: JWT '.$token
    );
	}

}
