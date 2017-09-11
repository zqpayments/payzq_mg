magento2-PayZQ_Payment
======================

PayZQ payment gateway for Magento2 extension

Requirements
=======
- SSL certificate
- PHP >= 5.5
- Magento 2


Install
=======
- Download and copy PayZQ folder into app/code
- To enable the module run command: bin/magento module:enable --clear-static-content PayZQ_Payment
- To update the database run command: bin/magento setup:upgrade
- To recompile run command: bin/magento setup:di:compile
- To configure PayZQ go to Magento Admin Stores/Configuration/Payment Methods/PayZQ
