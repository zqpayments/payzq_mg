
# PayZQ para WooCommerce en Wordpress por ZQ Payments #

Pasarela de Pago de PayZQ para el plugin WooCommerce en Wordpress

Before Start
============

Asegúrate de poseer las credenciales para el uso de nuestra API. Para ello, deberás darte de alta en nuestra web y obtener el token para empezar a usarlo. Para mayor información revisa la documentación desde nuestra web.


Requerimientos
==============
- Certificado SSL
- PHP >= 5.5
- WooCommerce 2.5 requires WordPress 4.1+
- WooCommerce 2.6 requires WordPress 4.4+


En caso de que el servidor no cumpla con los requerimientos comunicate con tu proveedor de servicios


Install
=======
- Descarga y copia la carpeta ``woocommerce-gateway-payzq`` dentro de la carpeta ``plugins`` de Wordpress
- Ingresa a la web administrativa de **Wordpress** e ingresa en la opción **ajustes** de ``WooCommerce``.
- Accede a la pestaña **Finalizar compra** y luego la opción **PayZQ**. Ingresa el Token que usarás para realizar las transacciones. Nota que puedes colocar dos Tokens: uno para el modo _test_ y otro para el modo _live_. Por último, ingresa la clave que se usará para el cifrado en caso de requerirlo.


Reembolsos
==========
 Desde el detalle de un pedido accediendo desde la opción **Pedidos** de **WooCommerce** podrás realizar devoluciones de transacciones que ya se hayan liquidado. Podrás realizar el reembolso parcial o completo de la transacción. En ningún caso podrás realizar el reembolso de un monto superior. Podrás realizar varios reembolsos para una misma transacción siempre y cuando la suma de los reembolsos no supere al monto liquidado inicialmente.

