# Sistema de Gestión de Repuestos y Talleres

## Procesos del Sistema


A continuación se escriben los procesos del sistema:

* **Solicitud de repuestos**

Actor: Mecánico

Descripción: El mecánico solicita repuestos necesarios para realizar una reparación o mantenimiento.

* **Registro del pedido**

Actor: Sistema (automático), Jefe de Taller

Descripción: Se registra la solicitud como un pedido, con información de repuestos, fechas y estado.

* **Verificación de disponibilidad en inventario**

Actor: Sistema

Descripción: Se verifica si hay repuestos disponibles en stock. 

* **Aprobación de solicitud**

Actor: Jefe de taller

Descripción: El jefe de taller aprueba (o rechaza) la solicitud según criterios técnicos y disponibilidad.

* **Asignación del repuesto**

Actor: Sistema

Descripción: El sistema reserva el repuesto aprobado en el inventario, registrando su salida parcial o total. 

* **Entrega del repuesto al mecánico**

Actor: Jefe de taller

Descripción: El jefe de taller entrega físicamente el repuesto al mecánico y confirma entrega en el sistema.

* **Ingreso de repuestos**

Actor: Jefe de taller

Descripción: El jefe de taller registra la llegada de nuevos repuestos al sistema, asociándolos al proveedor correspondiente.

* **Recepción de entregas del proveedor**

Actor: Sistema / Jefe de taller

Descripción: El sistema genera automáticamente una orden de compra cuando el mecánico solicita repuestos. El jefe de taller verifica y aprueba el pedido recibido del proveedor.

* **Generación de reportes y consultas**

Actor: Jefe de taller

Descripción: El jefe de taller consulta reportes sobre movimientos de inventario, uso de repuestos, solicitudes realizadas y stock actual.

* **Control de stock de seguridad**

Actor: Sistema

Descripción: El sistema monitorea continuamente el inventario y lanza alertas cuando un repuesto alcanza su nivel mínimo de seguridad.

* **Historial de repuestos utilizados**

Actor: Sistema

Descripción: El sistema almacena y permite consultar el historial de repuestos usados por cada mecánico, orden de trabajo o vehículo.