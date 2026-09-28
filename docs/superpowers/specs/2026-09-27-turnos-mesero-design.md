# Especificación de Diseño: Turnos de Mesero

**Fecha:** 2026-09-27
**Estado:** Aprobado por el usuario
**Alcance:** módulo de turnos por dispositivo, con PIN por persona y control del admin

---

## 1. Objetivo

Hoy el salón no sabe quién está trabajando. Los pedidos se atribuyen a
`orders.user_id`, que es **quien inició sesión en el dispositivo**, no la persona
que atendió. En un dispositivo compartido eso miente: si el admin está
logueado y el turno es de Juan Pérez, las comandas de Juan quedan del admin.

Este módulo agrega:

- Un **turno de trabajo** por persona, abierto y cerrado desde el propio
  dispositivo.
- **Un PIN de 4 dígitos por mesero**, para confirmar quién es.
- **Cada comanda atada a su turno**, para que el admin pueda ver por mesero
  cuántas comandas hizo, cuánto vendió y cuántas horas trabajó.

No es un turno de caja. `cash_registers` sigue siendo la gaveta de efectivo, con
saldo base, movimientos y arqueo, y solo la usan admin y cajero.

---

## 2. Decisiones tomadas

| Decisión | Valor | Motivo |
|---|---|---|
| Para qué sirve el turno | Control de asistencia | No maneja dinero; el dinero ya está en `cash_registers`. |
| Identificación en el dispositivo | Listado + PIN propio | Varios dispositivos, varias personas. El PIN evita que cualquiera marque el turno de otro. |
| Visibilidad | Admin todo, mesero lo suyo | El mesero solo abre y cierra lo propio. El admin ve todo y puede cerrar turnos ajenos. |
| Alta de meseros | Nombre + PIN + teléfono opcional | El email se arma solo, así el admin no inventa correos. |
| Quién ve el listado de meseros | Solo con `clock_shift` | El cajero y la cocina no cambian de persona en el dispositivo. |
| Sin turno abierto | El mesero queda bloqueado en el POS | Elegido por el usuario. Se bloquea **solo al rol `waiter`**, para que el admin nunca se quede fuera del sistema. |
| Escape si se olvida el PIN | El admin resetea el PIN | Sin apertura de emergencia sin PIN. |
| Control del admin | Comandas, totales y horas | Necesita liquidar y saber quién atiende. |

---

## 3. Modelo de datos

### 3.1 `work_shifts`

```
id          bigint
user_id     foreign -> users, cascadeOnDelete
opened_at   datetime
closed_at   datetime nullable
status      enum('open','closed') default 'open'
notes       text nullable
timestamps
```

Restricción: **una persona no puede tener dos turnos abiertos.** Se valida en el
servicio, no con un índice único, porque el índice impediría reabrir después de
cerrar sin limpiar el histórico.

### 3.2 `users.pin`

String nullable con **hash bcrypt**. Nunca se devuelve en ninguna respuesta de
la API: el modelo lo oculta y el serializador no lo incluye.

El PIN es opcional (`nullable`) porque el admin, el cajero y la cocina no
entran por esta puerta.

### 3.3 `orders.work_shift_id`

Foreign nullable a `work_shifts` con `nullOnDelete`. Se agrega **además** de
`user_id`, no en lugar de él: son dos hechos distintos.

| Columna | Significado |
|---|---|
| `user_id` | Quién tenía la sesión iniciada en el dispositivo. |
| `work_shift_id` | Quién estaba de turno y por lo tanto atendió. |

Una comanda de mostrador queda con `work_shift_id` en `null`, porque no hay turno
de mesero de por medio.

### 3.4 Permiso

`clock_shift`, para `admin` y `waiter`. El cajero y la cocina no lo llevan.

---

## 4. Reglas de negocio

### 4.1 Abrir turno

`POST /api/v1/shifts/open` con `user_id` y `pin`.

1. Quien llama necesita `clock_shift`; si no, 403.
2. El usuario objetivo debe tener rol `waiter`.
3. El usuario objetivo debe tener PIN configurado.
4. El PIN se verifica con `Hash::check`. Si no coincide, 422 con un mensaje
   genérico que **no** dice si el usuario existe.
5. El usuario objetivo **no debe tener ya un turno abierto**; si lo tiene,
   422 diciendo que ya tiene uno abierto.

### 4.2 Anti fuerza bruta

Un PIN de 4 dígitos son 10.000 combinaciones. Tras **5 intentos fallidos** para
el mismo usuario, queda bloqueado **5 minutos** para abrir turno.

El contador vive en `cache` con clave por usuario, no en base de datos: es
volátil a propósito y se limpia solo.

El reseteo del PIN por parte del admin **limpia también** el contador, para que
un mesero no quede esperando después de que le arreglan el PIN.

### 4.3 Cerrar turno

`POST /api/v1/shifts/{shift}/close`.

- El dueño del turno puede cerrarlo.
- El admin puede cerrar el turno de cualquiera. Es el escape para un turno que
  quedó abierto porque el dispositivo se apagó.
- Al cerrar se guarda `closed_at` y una nota opcional.
- Cerrar un turno ya cerrado devuelve 422.

### 4.4 Atribución de comandas

`POST /api/v1/orders` acepta `work_shift_id` opcional.

El servidor **no confía en el cliente**: valida que el turno exista, esté
`open`, y que quien llama tenga `clock_shift`. Un `work_shift_id` inválido se
ignora y el pedido se guarda con `null`, en vez de rechazar el pedido: una
comanda que no se guarda por un turno mal linked es peor que un pedido sin
atribución.

### 4.5 El turno activo vive en el dispositivo

El navegador guarda en `localStorage` el id del turno activo. El servidor no
guarda estado de dispositivo: el `work_shift_id` viaja en cada pedido.

Cada dispositivo tiene su propio turno activo, así que dos dispositivos pueden
tener turnos de dos meseros distintos al mismo tiempo. La regla de "un turno
abierto por persona" es **global**, no por dispositivo.

---

## 5. Interfaz

### 5.1 Módulo `/turnos`

Ruta nueva, en `resources/js/views/TurnosView.vue`, con entrada en la
navegación para `admin` y `waiter`.

**Para el mesero**

- Con turno activo en este dispositivo: tarjeta con nombre, hora de apertura y
  botón **Cerrar turno**.
- Sin turno: listado de meseros con PIN configurado. Al tocar uno se pide el
  PIN y se abre el turno.

**Para el admin, además**

- Tabla con **todos los turnos del día**, abiertos y cerrados, con botón para
  cerrar los que quedaron abiertos.
- **Resumen por mesero**: turnos, horas, comandas, facturas y ventas.
- **Alta de mesero**: nombre, PIN de 4 dígitos, teléfono opcional.
- **Resetear PIN** de un mesero.

### 5.2 El bloqueo del POS

El guard de Vue Router revisa las rutas con `meta.requiresShift`. Si el usuario
tiene rol `waiter` y el dispositivo no tiene turno activo, lo manda a `/turnos`.

El bloqueo aplica **solo al rol `waiter`**. El admin, el cajero y la cocina
trabajan sin turno. Motivo: el admin es quien resetea PINes y corrige
problemas; si estuviera bloqueado, un fallo de PIN dejaría el salón entero sin
sistema.

### 5.3 Entrada en navegación

`Turnos` con icono `Clock` de `lucide-vue-next`, visible para `admin` y
`waiter`. Nunca emojis.

---

## 6. Fuera de alcance

- **Turno de caja en el módulo de turnos.** Sigue siendo `cash_registers`.
- Reemplazar la autenticación por email y contraseña.
- Geolocalización o registro de GPS.
- Ausencias, incapacidades, permisos y justificantes.
- Foto de apertura o cierre de turno.
- Notificaciones al admin cuando alguien abre o cierra turno.
- Histórico de turnos filtrable por mesero: el resumen es del día en curso.
- Nóminas o liquidación automática: el módulo da los datos, no calcula pagos.

---

## 7. Riesgos

| Riesgo | Mitigación |
|---|---|
| PIN de 4 dígitos se fuerza bruta | 5 intentos y bloqueo de 5 minutos (§4.2). |
| Se pierde el PIN de un mesero | El admin lo resetea desde la lista (§4.3). |
| Un dispositivo queda con un turno abierto para siempre | El admin puede cerrarlo desde la lista. |
| Un mesero sin turno bloquea el salón | Solo se bloquea a `waiter`; el admin, cajero y cocina siempre pueden trabajar. |
| Se falsea la atribución mandando un `work_shift_id` ajeno | El servidor valida que el turno esté abierto y que quien llama tenga `clock_shift` (§4.4). |
| Un pedido falla por un turno mal linked | Se ignora el `work_shift_id` y se guarda el pedido (§4.4). |

---

## 8. Verificación

Sin tests automatizados (decisión del proyecto). La verificación es sobre la
API y el navegador:

1. Un mesero abre turno con su PIN: 201. Con PIN incorrecto: 422. Como cajero o
   cocina: 403.
2. Un mesero con turno abierto no puede abrir un segundo turno: 422.
3. Cinco PINs incorrectos dejan el usuario bloqueado; con el PIN correcto
   tampoco entra hasta que pasan los 5 minutos.
4. El admin resetea el PIN y el mesero puede volver a entrar.
5. Un pedido creado con un turno abierto guarda el `work_shift_id`; uno creado
   sin turno lo guarda en `null`.
6. El resumen del admin muestra comandas, facturas, ventas y horas por mesero.
7. Con el rol mesero y sin turno en el dispositivo, entrar al POS redirige a
   `/turnos`.
8. Con turno abierto, el POS carga normal.
9. El admin y el cajero nunca son redirigidos a `/turnos`.
