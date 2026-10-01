# Acta de aceptacion contable EPSAS

Fecha:

Responsables:

- Responsable administrativo:
- Responsable contable:
- Responsable de sistemas:

## Datos usados

- Ambiente de prueba:
- Periodo facturado:
- Cantidad de socios:
- Respaldo previo identificado:
- Datos personales anonimizados: Si / No

## Casos obligatorios

| Caso | Resultado esperado | Resultado obtenido | Aprobado |
| --- | --- | --- | --- |
| Consumo dentro del minimo | Solo cargos fijos autorizados | | |
| Consumo excedente | Tarifa y redondeo correctos | | |
| Mora y fecha de vencimiento | Se aplican una sola vez | | |
| Pago en efectivo exacto | Factura pagada y caja conciliada | | |
| Pago en efectivo con cambio | Cambio correcto y sin sobrepago | | |
| Pago QR confirmado por banco | Cobro unico y referencia registrada | | |
| Webhook bancario repetido | No duplica cobro | | |
| Referencia bancaria repetida | Operacion rechazada | | |
| Pago parcial autorizado | Saldo restante correcto | | |
| Anulacion de cobro | Trazabilidad y saldo restaurado | | |
| Cierre de caja | Total coincide con cobros registrados | | |
| Reconexion posterior al pago | Solo se genera sin deuda pendiente | | |

## Conciliacion

- Total esperado:
- Total registrado:
- Diferencia:
- Motivo de diferencia:

## Aprobacion

La salida a produccion queda autorizada solamente si todos los casos obligatorios estan aprobados, la diferencia de conciliacion es cero y no existen observaciones abiertas.

- Firma responsable administrativo:
- Firma responsable contable:
- Firma responsable de sistemas:
