<#
.SYNOPSIS
  Descarga los documentos del reporte de templates (collection_2) organizados
  en carpetas por base text, usando el API de ProcessMaker.

.EXAMPLE
  .\download_templates_by_basetext.ps1 -CsvPath "C:\Users\egosu\Downloads\Response_ProServiceTools (2).csv" -Token $env:PM_TOKEN
#>
param(
    [Parameter(Mandatory)] [string]$CsvPath,
    [Parameter(Mandatory)] [string]$Token,
    [string]$BaseUrl   = "https://forteunderwriters.dev.cloud.processmaker.net",
    [string]$OutputDir = "$PSScriptRoot\..\Iframe Documents\DLSU\Templates por Base Text"
)

$ErrorActionPreference = 'Stop'
$headers = @{ Authorization = "Bearer $Token" }
$invalid = [System.IO.Path]::GetInvalidFileNameChars() -join ''
$rxInvalid = "[{0}]" -f [regex]::Escape($invalid)

function Sanitize([string]$name) {
    $clean = ($name -replace $rxInvalid, '-').Trim().TrimEnd('.', '-').Trim()
    if ($clean.Length -gt 150) { $clean = $clean.Substring(0, 150).Trim() }
    return $clean
}

$rows = Import-Csv $CsvPath
$results = [System.Collections.Generic.List[object]]::new()
$ok = 0; $fail = 0

foreach ($row in $rows) {
    $baseText = $row.base_text
    if ([string]::IsNullOrWhiteSpace($baseText) -or $baseText -eq 'null') { $baseText = 'Genericos' }
    $folder = Join-Path $OutputDir (Sanitize $baseText)
    New-Item -ItemType Directory -Force $folder | Out-Null

    # El id del file es el ultimo segmento de la url del request
    $fileId = ($row.url -split '/')[-1]
    $fileName = Sanitize $row.document_name
    if (-not $fileName) { $fileName = "file-$fileId.docx" }
    $target = Join-Path $folder $fileName
    if (Test-Path $target) {
        # mismo nombre en la carpeta (p.ej. otro idioma): desambiguar con el id de fila
        $target = Join-Path $folder ("{0}__{1}" -f $row.id, $fileName)
    }

    $apiUrl = "$BaseUrl/api/1.0/files/$fileId/contents"
    try {
        Invoke-WebRequest -Uri $apiUrl -Headers $headers -OutFile $target | Out-Null
        $ok++
        $status = 'OK'
        Write-Host ("[OK]   {0,-60} -> {1}" -f $fileName, (Split-Path $folder -Leaf))
    } catch {
        $fail++
        $status = "ERROR: $($_.Exception.Message)"
        if (Test-Path $target) { Remove-Item $target -Force -Confirm:$false }
        Write-Host ("[FAIL] {0} (file $fileId): {1}" -f $fileName, $_.Exception.Message) -ForegroundColor Red
    }

    $results.Add([pscustomobject]@{
        id = $row.id; base_text = $baseText; doc_order = $row.doc_order
        language = $row.language; type = $row.type; file_id = $fileId
        document_name = $row.document_name; status = $status; path = $target
    })
}

$reportPath = Join-Path $OutputDir "_download_report.csv"
$results | Export-Csv $reportPath -NoTypeInformation -Encoding UTF8

Write-Host ""
Write-Host "Descargados: $ok | Fallidos: $fail | Reporte: $reportPath"
