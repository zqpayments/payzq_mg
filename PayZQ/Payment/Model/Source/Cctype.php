<?php
/**
 * PayZQ payment method model
 *
 * @category    PayZQ
 * @package     Payment
 * @author      PayZQ
 * @copyright   PayZQ (http://payzq.net)
 */

namespace PayZQ\Payment\Model\Source;

class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DI', 'JCB', 'OT');
    }
}
