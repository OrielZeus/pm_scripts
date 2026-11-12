# Sujeciones (Subjectives) no aparecen en R12 version en ingles

## Contexto
Fuente: Correo 2 de Mayra Aguilar (Solicitudes Antiguas)
Numero original: Punto 11

## Solicitud del cliente
En la version en Ingles de la R12, no salen las sujeciones en el slip. (Ver quoted 1897 'Misty Ventures').

## Alcance
Verificar y corregir la pestana Subjectives Guarantee para la version en ingles de R12.

## Archivos afectados
- AMERICAN YACHT FORM R12 with Sections/01-General-Information-and-Special-Conditions-American-Form-R12.docx y su copia en Upload Documents/9832__01-General-Information-and-Special-Conditions-American-Form-R12.docx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: N/A (causa raiz esta en el template, no en el OpenL)
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: AMERICAN YACHT FORM R12 with Sections/01-General-Information-and-Special-Conditions-American-Form-R12.docx y su copia en Upload Documents/9832__01-General-Information-and-Special-Conditions-American-Form-R12.docx
- Cláusula/seccion: Sujeciones / Subjectives
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 26-yacht-set-information-slip.php
- ID: N/A
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
Las sujeciones aparecen en el slip para BaseText R12 en ingles, igual que en espanol.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0025.

## Dependencias
26-yacht-set-information-slip.php

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
Copia corregida incluida en 'Paquete para desarrollo' y 'Paquete para produccion'; falta que alguien con acceso al Collection/ambiente de ProcessMaker la suba para reemplazar el documento actual. Ver 'Instrucciones de publicacion.xlsx'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Actualizado

## Evidencia
Causa raiz real confirmada al comparar el template en ingles de R12 contra sus 9 equivalentes en otros idiomas/BaseText: TODOS los demas templates '01-...' tienen la linea 'SUBJECT TO: ${YQP_SUBJECTIVES_GUARANTEE_SLIP}' inmediatamente despues de 'Premium Payment Clause as attached.'; el template en ingles de R12 (American Form R12) es el UNICO que no la tenia -- el parrafo estaba completamente ausente, no solo vacio. Por eso nunca podia aparecer, sin importar el contenido del OpenL: no habia ningun token en el documento donde insertar el texto.

## Observaciones
Se inserto la linea faltante 'SUBJECT TO: ${YQP_SUBJECTIVES_GUARANTEE_SLIP}' en la misma posicion y con el mismo formato que en el template hermano en espanol (misma carpeta, mismo estilo de parrafo copiado del renglon 'TAXES:' del mismo documento para mantener fuente/tabulacion). Verificado: el parrafo nuevo existe, el conteo de parrafos aumento en 1 (esperado), tablas sin cambios, ZIP/XML validos. Se corrigieron 2 copias (documento original y su duplicado en Upload Documents) ya que ambas carecian de la linea.
