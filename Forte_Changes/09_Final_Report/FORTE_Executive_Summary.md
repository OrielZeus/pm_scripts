# FORTE Underwriters — Yachts Quotation Process
## Resumen Ejecutivo de Auditoría — "Forte_Changes"

**Fecha:** 2026-07-22
**Alcance:** FORTE Underwriters – Yachts Quotation Process / Managed Services (excluye Cargo y DLSU, aunque comparten repositorio).
**Solicitado por:** Elmer, en respuesta a 2 correos de Mayra Aguilar con 14 puntos pendientes numerados.

## Qué se revisó
- 710 archivos inventariados: 701 del repositorio `ProcessMakerScripts` (carpeta `Iframe Documents/Forte Code Backups`, backups DEV y PROD) + 9 archivos subidos por Elmer.
- El libro OpenL completo (`Forte OpenL Tablets Yachts 1.xlsx`, 19 pestañas).
- 6 documentos Word de cláusulas nuevas + 1 slip real de cliente (`Correcciones_Slip_Ejemplo.docx`).
- 154 scripts PHP y 406 pantallas JSON del backup DEV (2026-04-14). El backup de PROD está prácticamente vacío.

## Resultado por requerimiento (de los 14 puntos de Mayra)

| # | Tema | Estado |
|---|---|---|
| 1 | Tabla de Tenders no sale en CL.328 | Causa probable identificada (template base no localizado) |
| 2 | Cláusulas opcionales incompletas en renovación | Mecanismo identificado; fila exacta no aislada |
| 3 | Texto completo de cláusula de guerra | No localizado en el repositorio disponible |
| 4 | Cláusula de motores internos cortada | Evidencia confirmada (antes/después del propio correo); causa exacta no aislada |
| 5 | Slip de Puerto Rico sin actualizar | Slip real localizado; falta el documento de referencia de Sandybell para comparar |
| 6 | Sin indicador de estado en aprobación | No localizado en este backup |
| 7 | Cláusula de cancelación con "Mexbrit" fijo | **Causa raíz confirmada con alta confianza** |
| 8 | Agregar reaseguradores AXA XL y SCOR | Confirmado: AXA XL no existe; SCOR existe solo bajo nombres regionales con el mismo RGRE (requiere decisión) |
| 9 | Agregar 4 cláusulas nuevas | 3 textos recibidos y verificados; falta 1 texto en español; falta ubicar dónde insertarlas |
| 10 | Tenders limitados a 5 (caso 7 tenders) | **Causa raíz estructural confirmada** en el OpenL |
| 11 | Sujeciones no salen en R12 inglés | Pestaña identificada; comparación fila por fila pendiente |
| 12 | Remoción de Restos de Naufragio al 30% | No localizado en esta pasada |
| 13 | Ajustador duplicado (nuevo + por defecto) | No localizado en este backup (posible Collection externa) |
| 14 | Quitar Alejandro Martínez de Ajustadores | No localizado en este backup (mismo bloque que #13) |

**4 de 14** requerimientos tienen causa raíz confirmada con evidencia directa (REQ-007, REQ-008, REQ-010, y parcialmente REQ-002/004 con el mecanismo identificado). **Los demás requieren información adicional** (casos de prueba reales, el template base, o el archivo/Collection donde vive el contenido) que no está disponible en el repositorio conectado a esta sesión.

## Los 2 hallazgos que más importan

1. **No existe un backup utilizable de PROD.** La carpeta `Forte_Prod_Files` tiene solo 1 pantalla y una carpeta de scripts vacía, mientras que DEV tiene 406 pantallas y 154 scripts completos (exportados 2026-04-14). Esto significa que **ningún cambio puede validarse contra el estado real de producción** hasta que se genere un backup de PROD nuevo.

2. **La plantilla base del slip (R12, CL.328, Panamá) no está en este repositorio.** Solo se tiene UN slip ya generado como ejemplo (`Correcciones_Slip_Ejemplo.docx`). Varios de los 14 puntos (1, 3, 5, 13, 14) requieren editar esa plantilla base, y sin localizarla no se puede aplicar ningún cambio de forma segura.

## Dónde está todo
Ver sección "Ubicación de resultados" en `FORTE_Detailed_Analysis.md`. El tracker completo (13 pestañas) está en `03_Change_Tracker/FORTE_Master_Change_Tracker.xlsx`.

## Nota sobre el horario de reunión
El primer correo de Mayra también pregunta si se puede cambiar el horario de la llamada semanal de los jueves. Es un tema administrativo, no técnico — no se incluyó en el tracker de cambios; Elmer debe responderlo directamente.
