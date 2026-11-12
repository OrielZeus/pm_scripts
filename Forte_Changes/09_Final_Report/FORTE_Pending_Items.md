# FORTE Yachts — Elementos Pendientes de Confirmación

Esta lista consolida todo lo que **no se pudo verificar** con la evidencia disponible en esta sesión, sin inventar información. Ver también pestaña `Risks_and_Pending` del tracker maestro.

## Bloqueados por falta de acceso a un archivo/ambiente
1. **Backup real de PROD** — la carpeta `Forte_Prod_Files` está prácticamente vacía; se necesita ejecutar la herramienta de backup contra PROD antes de poder comparar o publicar con seguridad.
2. **Template base del slip** (R12, CL.328, Panamá) — no se encontró ningún .docx/.dotx base en el repositorio conectado; solo un slip ya generado (`Correcciones_Slip_Ejemplo.docx`, antes `correcciones slip (3).docx`). Necesario para REQ-001, 003, 005, 007, 013, 014.
3. **Collection/documento de Ajustadores** — necesario para REQ-013 y REQ-014.
4. **Slip de referencia de Sandybell** (Puerto Rico) — mencionado por Mayra en REQ-005, no fue adjuntado ni se encontró en el repositorio.
5. **Casos reales de prueba** — "Pantera Sunset, LLC" (quoted #1892, REQ-010) y "Misty Ventures" (quoted #1897, REQ-011) no están disponibles en este repositorio para reproducir los defectos exactamente.

## Requieren confirmación del cliente (decisión de negocio, no técnica)
6. **Nombre definitivo de SCOR** (REQ-008): ¿fila nueva genérica "Scor Reinsurance Company" o reutilizar/renombrar una de las 3 variantes regionales que ya comparten el mismo RGRE-418-97-300170?
7. **Número máximo de tenders a soportar** (REQ-010): Mayra menciona un caso con 7; hay que confirmar si el producto debe soportar 7 de forma permanente (o más) antes de invertir en el cambio estructural del OpenL.
8. **Texto en español de "Depreciación (Menores a 20 años)"** (REQ-009): solo se recibió la versión en inglés.
9. **Texto/celda exacta de "Remoción de Restos de Naufragio" al 30%** (REQ-012): el correo confirma el cambio de 20% a 30%, pero no se ubicó el texto en el Excel de clausulas adjunto durante esta pasada — pendiente de una revisión dirigida.

## Pendiente de una siguiente iteración de análisis (tiempo/alcance de esta sesión)
10. Recorrido completo de las 1020 filas de la pestaña `Clauses` del OpenL para aislar las filas exactas de REQ-002 y REQ-004.
11. Comparación fila por fila de `Subjectives Guarantee` entre BaseText en español e inglés (REQ-011).
12. Búsqueda dirigida del texto de "Remoción de Restos de Naufragio" en el Excel `Clauses_Adicionales_14072026.xlsx` (antes `CLAUSES TO BE INCLUDED (ADICIONALES)_14072026.xlsx`) y en la pestaña `P&I` del OpenL (REQ-012).
13. Ubicación del texto completo vigente de la cláusula de guerra (REQ-003) — no se encontró en ningún archivo de este repositorio.
14. Confirmación de qué pantalla/variable mostraba antes el indicador de estado de aprobación (REQ-006) — no hay versión histórica de esas pantallas para comparar.

## Nota administrativa (no técnica, no incluida en el tracker de cambios)
Mayra pregunta en su primer correo si se puede cambiar el horario de la llamada semanal de los jueves porque coincide con su reunión de oficina. Esto queda a criterio de Elmer para responder directamente; no requiere análisis técnico.
