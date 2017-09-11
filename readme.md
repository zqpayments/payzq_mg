
# PayZQ para Magento 2 por ZQ Payments #

Pasarela de Pago de PayZQ para Magento 2

Antes de empezar
============

Asegúrate de poseer las credenciales para el uso de nuestra API. Para ello, deberás darte de alta en nuestra web y obtener el token para empezar a usarlo. Para mayor información revisa la documentación desde nuestra web.


Requerimientos
==============
- Certificado SSL
- PHP >= 5.5
- Magento 2


En caso de que el servidor no cumpla con los requerimientos comunicate con tu proveedor de servicios


Instalación
=======
- Descarga y copia la carpeta ``PayZQ`` dentro de la directorio ``app/code`` de Magento
- Para habilitar el módulo ejecuta el siguiente comando desde la consola ``bin/magento module:enable --clear-static-content PayZQ_Payment``
- Actualiza la Base de Datos ejecutando ``bin/magento setup:upgrade``
- Para recompilar ejecuta el comando ``bin/magento setup:di:compile``
- Ingresa a la web administrativa de **Magento** e ingresa en la opción **Stores / Configuration / Payment**, selecciona el método **PayZQ** e ingresa el Token que usarás para realizar las transacciones. Nota que puedes colocar dos Tokens: uno para el modo _test_ y otro para el modo _live_. Por último, ingresa la clave que se usará para el cifrado en caso de requerirlo.


Reembolsos
==========
 Para realizar reembolsos a través de PayZQ deberás hacerlo siguiendo los pasos para la devolución de dinero de **Magento**. Ingresa a la opción **Sales / Invoices** y selecciona la orden que deseas hacer una devolución.

