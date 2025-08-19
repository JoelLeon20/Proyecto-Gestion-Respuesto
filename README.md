# Proyecto-Gestion-Respuesto
Proyecto académico para gestionar repuestos y pedidos en talleres.


---

-Ramas del Proyecto

| Rama                     | Contenido Principal                                  |
|--------------------------|------------------------------------------------------|
| `main`                   | Descripción general y guía del repositorio           |
| `Documentación-Proyecto` | Documentación Caso de Uso                            |
| `DiagramasUML`           | Diagramas de clases, casos de uso, secuencia, etc    |
| `Arquitectura`           | Diagramas de despliegue y diseño arquitectónico      |
| `Procesos`               | Procesos del Proyecto                                |
| `ProyectoPDF`            | Todo el Proyecto en un PDF                           |

---

-Integrantes del Equipo

- **Joel León**  
  - Diagrama de Casos de Uso  
  - Diagrama de Secuencia  
  - Documentación de Casos de Uso  
- **Sebstián Allauca**  
  - Documentación de los procesos del sistema **(en conjunto con Joel León)**
  - Diseño arquitectónico Cliente-Servidor
  - Diagrama UML de Estados
- **Joseph Montesdeoca**  
  - Diagrama de Clases
  - Diagrama de Actividades
- **Michael Olvera**
  - Diagrama de Componentes
  - Diagrama de Despliegue
  - Correcciones generales

---

# 🚀 Proyecto — Gestión de Órdenes y Solicitudes  

Este proyecto lo desarrollamos en equipo, dividiendo responsabilidades entre **UI/UX, Autenticación & Seguridad, Back-end y Base de Datos**.  

A continuación detallamos nuestro aporte individual:  

---

## 🎨 Joel León — *Front-end Lead & UI/UX*

Me encargué de la **maquetación general** (tipografías, espaciados y alineación del header con el botón `Cerrar sesión` y el usuario al mismo nivel).  

También trabajé en los **estilos globales y componentes reutilizables**, como:  
- Botones grises/negros  
- Botón “Añadir” oscuro  
- Badges de estado  
- Inputs de fecha  
- Buscadores  
- Tablas y paginación  

Además, realicé ajustes visuales solicitados:  
- Centrados de opciones  
- Botones más grandes  
- Botones de acción alineados a la derecha  
- Consistencia de colores  

Me encargué de la **accesibilidad básica** (`tabindex`, contraste y foco visible).  

**Pantallas que desarrollé o pulí:**  
- `mecanicos.php`  
- `proveedores.php`  
- `repuestos.php`  
- `ordenes.php`  
- `reportes.php`  
- `index.html` (dashboard tarjetas)  
- `estilos.css`  

Finalmente, participé en la **simulación de impresión** (modal/overlay y vista “printable”) en conjunto con Sebastián.  

---

## 🔐 Michael Olvera — *Auth, Seguridad & QA*

Me enfoqué en la parte de **autenticación y sesiones**, implementando:  
- `login.php`  
- `validar_login.php`  
- `cerrar_sesion.php`  
- Middlewares ligeros por rol  

Desarrollé el **Módulo Proveedor – Gestión de Pedidos**:  
- `pedidos.php`: listado con estados + botón Completar/Entregado  
- `historial_pedidos.php`: historial de entregas con filtros por rango  

En cuanto a **seguridad**, apliqué:  
- Sanitización de entradas  
- Consultas preparadas  
- Manejo de errores  
- Consistencia en mensajes, estados y navegación  

También me encargué de **QA y pruebas**:  
- Colección en **Postman** para CRUD de órdenes/solicitudes y cambios de estado  
- Pruebas ligeras de carga (iteraciones en Postman)  
- Checklist OWASP básica: XSS, SQLi triviales, manejo de sesión y logout  

---

## ⚙️ Sebastián Allauca — *Back-end Órdenes & Solicitudes*

Mi aporte principal fue en el **Módulo Órdenes de Trabajo**, implementando:  
- CRUD completo  
- Filtros por fecha y palabra clave  
- Paginación server-side  
- Selección de filas para imprimir  
- Vista previa y disparo de impresión  

En el **Módulo Solicitudes de Repuesto** desarrollé:  
- Filtro por estado  
- Acciones de cambio de estado  
- Funciones de “Marcar entregado” y “Completado” (para proveedor)  

Trabajé la **lógica PHP y consultas** en:  
- `ordenes.php`  
- `solicitudes.php`  
- `reportes.php`  

Colaboré con Joel en la **estructura de la plantilla imprimible** y el uso de `window.print()`.  

Además, implementé **roles y permisos** mediante guardas por sesión, y la lógica de **paginación y ordenamiento** con parámetros `?page=`, `?q=`, `?from=`, `?to=`.  

---

## 🗄️ Joseph Montesdeoca — *Base de Datos & Data Layer*

Me encargué del **diseño del esquema MySQL**, creando las tablas:  
- `mecanicos`, `proveedores`, `repuestos`, `ordenes`, `solicitudes`, `reportes`, `usuarios`  

Optimizamos con **índices y claves foráneas** para mantener consistencia y rendimiento.  

Generé **datos iniciales (seeds)**: más de 15 registros entre repuestos, mecánicos, proveedores, órdenes, etc.  

Desarrollé la **capa de conexión y helpers** en `conexion.php` (driver `mysqli`/`PDO`), incluyendo manejo de errores.  

Hice las **consultas para filtros, estados, joins, totales y paginación**, además de implementar la **tabla de auditoría/entregas (`historial_entregas`)** y corrección de claves foráneas.  

---

✨ Con este trabajo en conjunto logramos un sistema **funcional, seguro, escalable y con una interfaz consistente**, integrando todas las capas: Front-end, Back-end, Seguridad y Base de Datos.  
