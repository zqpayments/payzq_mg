<?php
/**
 * Stripe payment method model
 *
 * @category    PayZQ
 * @package     Payment
 * @author      PayZQ
 * @copyright   Inchoo (http://payzq.net)
 */

namespace PayZQ\Payment\Model;

class Payment extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'payzq_payment';

    protected $_code = self::CODE;

    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;

    protected $_api;
    protected $_jwt;
    protected $_curl;

    protected $_merchant_key = false;
    protected $_secret_key = false;
    protected $_test_secret_key = false;
    protected $_mode_test = false;

    protected $_countryFactory;

    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = [
        'AUD',
        'CAD',
        'CZK',
        'DKK',
        'EUR',
        'HKD',
        'HUF',
        'ILS',
        'JPY',
        'MXN',
        'NOK',
        'NZD',
        'PLN',
        'GBP',
        'RUB',
        'SGD',
        'SEK',
        'CHF',
        'TWD',
        'THB',
        'USD',
    ];

    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \PayZQ\Payment\Helper\PayZQAPI $api,
        \PayZQ\Payment\Helper\Curl $curl,
        \PayZQ\Payment\Helper\JWT $jwt,
        array $data = array()
      ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );

        $this->_api = $api;
        $this->_curl = $curl;
        $this->_jwt = $jwt;

        $this->_countryFactory = $countryFactory;

        $this->_test_secret_key = $this->getConfigData('test_api_key');
        $this->_secret_key = $this->getConfigData('api_key');
        $this->_merchant_key = $this->getConfigData('merchant_key');
        $this->_mode_test = $this->getConfigData('test_mode');

        $this->_api->set_test_private_key($this->_test_secret_key);
        $this->_api->set_private_key($this->_secret_key);
        $this->_api->set_test_mode($this->_mode_test);
        $this->_api->set_merchant_key($this->_merchant_key);
    }

    /**
  	 * Send the request to PayZQ's API
  	 *
  	 * @param array $request
  	 * @return array|WP_Error
  	 */
  	public function request( $request) {

      $this->_logger->info('Making a request to PayZQ\'s API');

  		$token = $this->_api->get_secret_key();
  		$merchant_key = $this->_merchant_key;

  		$token_payload = $this->_jwt->decode($token, $this->_api->get_key_jwt(), false);

      $cypher = (in_array('cypher', $token_payload['security'])) ? true : false;

  		$json = json_encode($request, JSON_PRESERVE_ZERO_FRACTION);

      if ($cypher) {
  			$this->_logger->info( "the reques must by cypher " );
        $cypher_data = $this->_api->cypherData($json);
        $json = json_encode(array('request' => $cypher_data));
      }

  		try {

  			list($curl_body, $curl_status, $curl_header) = $this->_curl->request(
          'post',
          $this->_api->get_api_base_url(),
          $this->_api->get_header($token),
          $json,
          false
        );
  		} catch (Exception $e) {
        $this->_logger->error( "Error: ".$e->getMessage() );
        return array(
          'code' => 'payzq_error',
          'message' => __('Error: ').$e->getMessage(),
        );
  		}

  		if ($curl_status == 200 && $message = json_decode($curl_body, true)) {
  	    if ($message['code'] === '00') {
  				return $message;
  			} else {
          $this->_logger->error( "Error: Transaction declined" );
  				return array(
            'code' => 'payzq_error',
            'message' => __('Transaction declined'),
          );
  			}
  		} else {
  			$this->_logger->error( "Error: an error has ocurred calling curl". $curl_body );
        return array(
          'code' => 'payzq_error',
          'message' => __('An error has ocurred calling curl'),
        );
  		}
  	}

    /**
  	 * Generate \Magento\Payment\Model\InfoInterface request array
  	 *
     * @param object $payment
  	 * @param float $payment
  	 * @return array
  	 */
    protected function generate_payment_request($payment, $amount) {
      $this->_logger->info('PayZQ - Generating payment request ');

      $order = $payment->getOrder();

      /** @var \Magento\Sales\Model\Order\Address $billing */
      $billing_order = $order->getBillingAddress();

  		$card_number = $this->_api->clear_card_number($payment->getCcNumber());
  		$expiry = $this->_api->clear_card_date(sprintf('%02d',$payment->getCcExpMonth()).' '.$payment->getCcExpYear());
  		$cardholder_name = $billing_order->getName();

  		$credit_card = array(
        "cardholder" => $cardholder_name,
        "type" => $this->_api->get_card_type($card_number),
        "number" => $card_number,
        "cvv" => $payment->getCcCid(),
        "expiry" => $expiry,
      );

      $avs = array(
        "address" => $billing_order->getStreetLine(1). ' ' .$billing_order->getStreetLine(2),
        "country" => $billing_order->getCountryId(),
        "state_province" => $billing_order->getRegion(),
        "email" => 'q@t.com',
        "cardholder_name" => $cardholder_name,
        "postal_code" => $billing_order->getPostcode(),
        "phone" => '12112',
        "city" => $billing_order->getCity(),
      );

      $billing = array(
        "name" => $billing_order->getName(),
        "fiscal_code" => '',
        "address" => $billing_order->getStreetLine(1). ' ' .$billing_order->getStreetLine(2),
        "country" => $billing_order->getCountryId(),
        "state_province" => $billing_order->getRegion(),
        "postal_code" => $billing_order->getPostcode(),
        "city" => $billing_order->getCity(),
      );

      $shipping = array(
        "name" => $billing_order->getName(),
        "fiscal_code" => '',
        "address" => $billing_order->getStreetLine(1). ' ' .$billing_order->getStreetLine(2),
        "country" => $billing_order->getCountryId(),
        "state_province" => $billing_order->getRegion(),
        "postal_code" => $billing_order->getPostcode(),
        "city" => $billing_order->getCity(),
      );

      $breakdown = array();

  		$items = $order->getAllItems();

      foreach ($items as $key => $item) {

        if ($item->getRowTotal() <= 0) continue;

        for ($i=0; $i < $item->getQtyOrdered(); $i++) {
          $subtotal = floatval(number_format($item->getRowTotal(), 2));
          $total = floatval(number_format($item->getRowTotalInclTax(), 2));

          $breakdown[] = array(
            "description" => $item->getName(),
            "subtotal" => $subtotal,
            "taxes" => $total - $subtotal,
            "total" => $total
          );
        }

      }

      if ($order->getShippingInvoiced() > 0) {
        $breakdown[] = array(
          "description" => 'Shipping cost',
          "subtotal" => floatval($order->getShippingInvoiced() - $order->getShippingTaxInvoiced()),
          "taxes" => floatval($order->getShippingTaxInvoiced()),
          "total" => floatval($order->getShippingInvoiced())
        );
      }

  		$nex_code_transaction = $this->_api->get_payzq_transaction_code();
  		$ip = $this->_api->get_ip_server();

      return array(
        "type" => "authorize_and_capture",
        "transaction_id" => $nex_code_transaction,
        "target_transaction_id" => '',
        "amount" => floatval(number_format($amount, 2, '.', '')),
        "currency" => $order->getBaseCurrencyCode(),
        "credit_card" => $credit_card,
        "avs" => $avs,
        "billing" => $billing,
        "shipping" => $shipping,
        "breakdown" => $breakdown,
        "3ds" => false,
        "ip" => $ip,
      );
  	}

    /**
  	 * Generate refund request array
  	 *
     * @param \Magento\Payment\Model\InfoInterface $payment
  	 * @param float $payment
  	 * @return array
  	 */
    protected function generate_refund_request( $payment, $amount ) {
      $this->_logger->info('PayZQ - Generating refund request ');

      $order = $payment->getOrder();

  		return array(
        "type" => "refund",
        "transaction_id" => $this->_api->get_payzq_transaction_code(),
        "target_transaction_id" => $payment->getParentTransactionId(),
        "amount" => floatval(number_format($amount, 2, '.', '')),
        "currency" => $order->getBaseCurrencyCode(),
        "ip" => $this->_api->get_ip_server(),
      );
  	}

    /**
     * Payment capturing
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
      $this->_logger->info('PayZQ - Making a capture');

      try {
        $request_payment =  $this->generate_payment_request( $payment , $amount);
        $response = $this->request($request_payment);

      } catch (\Exception $e) {
        $this->_logger->info('PayZQ - Payment capturing error.'. $e->getMessage());
        throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
      }

      if ((!is_array($response)) || (is_array($response) && $response['code'] != '00')) {
        $this->_logger->error(__('Error: '.$response['code'].': '.$response['message']));
        throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
      }

      $this->_logger->info('PayZQ - Capture Transaction ID'.$response['transaction_id']);

      $payment->setTransactionId($response['transaction_id'])
          ->setIsTransactionClosed(0);

      return $this;
    }

    /**
     * Payment refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
      $this->_logger->info('PayZQ - Making a refund');

      $transactionId = $payment->getParentTransactionId();

      try {
        $request_refund =  $this->generate_refund_request($payment , $amount);
        $response = $this->request($request_refund);
      } catch (\Exception $e) {
        $this->_logger->error('Payment capturing error.'. $e->getMessage());
        throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
      }

      if ((!is_array($response)) || (is_array($response) && $response['code'] != '00')) {
        $this->_logger->info('Error: '.$response['code'].': '.$response['message']);
        throw new \Magento\Framework\Validator\Exception(__('Refund capturing error.'));
      }

      $this->_logger->info('PayZQ - Refund Transaction ID'.$response['transaction_id']);

      $payment
          ->setTransactionId($response['transaction_id'])
          ->setParentTransactionId($transactionId)
          ->setIsTransactionClosed(1)
          ->setShouldCloseParentTransaction(1);

      return $this;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote && (
            $quote->getBaseGrandTotal() < $this->_minAmount
            || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }

        if (!$this->getConfigData('api_key')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Availability for currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }
}
