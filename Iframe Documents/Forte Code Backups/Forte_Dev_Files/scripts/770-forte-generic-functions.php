<?php

/***************************
 * FORTE Generic Functions
 *
 * by Cinthia Romero
 **************************/
/**
 * Function that convert html > ul > li to a PHP array
 * @param string $ul
 * @return array $output
 *
 * by Ronald Nina
 */
function ulToArray($ul) {
    $output = [];

    try {
        if (is_string($ul)) {
            $dom = new DOMDocument();
            // Suprime warnings por HTML mal formado
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="UTF-8">' . $ul);
            libxml_clear_errors();

            $ulElements = $dom->getElementsByTagName('ul');
            if ($ulElements->length === 0) {
                throw new Exception("No UL found");
            }

            return domUlToArray($ulElements->item(0));
        }
    } catch (Exception $e) {
        return ['Exception: ' . $e->getMessage()];
    }

    return $output;
}

function domUlToArray(DOMElement $ul) {
    $result = [];

    foreach ($ul->childNodes as $li) {
        if ($li->nodeName === 'li') {
            $text = '';
            $children = [];

            foreach ($li->childNodes as $child) {
                if ($child->nodeName === 'ul') {
                    $children = domUlToArray($child);
                } elseif ($child->nodeType === XML_TEXT_NODE) {
                    $text .= trim($child->textContent);
                }
            }

            $result[] = !empty($children) ? [$text, $children] : $text;
        }
    }

    return $result;
}

/**
 * Function to get the type of document
 * @param (string) $html
 * @return (string) $response
 * Created By Elmer Orihuela
 */
function fixHtmlListStructure($htmlInput)
{
    // 1. Decodifica las entidades HTML (ej. &aacute;, &ntilde;, etc.)
    $html = html_entity_decode($htmlInput, ENT_QUOTES, 'UTF-8');

    // 2. Elimina caracteres problemáticos como &nbsp;
    $html = str_replace('&nbsp;', '', $html);

    // 3. Cuenta las etiquetas <ul> y <li> abiertas y cerradas
    $openedUl = substr_count($html, '<ul>');
    $closedUl  = substr_count($html, '</ul>');
    $openedLi = substr_count($html, '<li>');
    $closedLi  = substr_count($html, '</li>');

    // 4. Añade etiquetas faltantes si es necesario
    if ($openedUl > $closedUl) {
        $html .= str_repeat('</ul>', $openedUl - $closedUl);
    }
    if ($openedLi > $closedLi) {
        $html .= str_repeat('</li>', $openedLi - $closedLi);
    }

    // 5. Corrige problemas de anidamiento de listas
    $html = preg_replace('/(<ul>)\s*<\/li>/', '$1', $html);
    $html = preg_replace('/<\/ul>\s*(?!<\/li>)/', '</ul></li>', $html);

    return $html;
}

/**
 * Clean textarea rich text value
 *
 * @param string $valueToClean
 * @param string $parseValueForSlip
 * @return array $cleanedValues
 *
 * by Cinthia Romero
 */
function cleanTextAreaRichTextValue($valueToClean, $parseValueForSlip)
{
    // Corrige la estructura de la lista HTML.
    $valueToClean = fixHtmlListStructure($valueToClean);
    $cleanedValues = [
        "ORIGINAL_VALUE_CLEANED" => "",
        "SLIP_VALUE_PARSED" => ""
    ];

    // Valida si existe una lista (<li>) en el contenido.
    if (strpos($valueToClean, "<li>") !== false) {
        // Conserva solo las etiquetas <ul> y <li>.
        $valueToClean = strip_tags($valueToClean, '<ul><li>');
        // Extrae el contenido entre el primer <ul> y el último </ul>.
        $start = strpos($valueToClean, "<ul>");
        $end = strrpos($valueToClean, "</ul>");
        if ($start !== false && $end !== false) {
            $valueToClean = substr($valueToClean, $start, $end - $start + 5);
        }
        $cleanedValues["ORIGINAL_VALUE_CLEANED"] = $valueToClean;
        if ($parseValueForSlip == "YES") {
            // Decodifica dos veces por seguridad en caracteres especiales.
            $decodedValue = html_entity_decode(html_entity_decode($valueToClean));
            $cleanedValues["SLIP_VALUE_PARSED"] = ulToArray($decodedValue);
        }
    } else {
        // Si no hay lista, transforma el texto plano en una lista HTML.
        $lines = explode("\n", $valueToClean);
        $html = "<ul>";
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $html .= "\n<li>" . $line . "</li>";
            }
        }
        $html .= "\n</ul>";
        $cleanedValues["ORIGINAL_VALUE_CLEANED"] = $html;
        if ($parseValueForSlip == "YES") {
            $decodedValue = html_entity_decode(html_entity_decode($html));
            $cleanedValues["SLIP_VALUE_PARSED"] = ulToArray($decodedValue);
        }
    }
    return $cleanedValues;
}
/*
$data["YQP_SUBJECTIVES_GUARANTEE"] = "<ul>\n<li>Charles Taylos a trav&eacute;s de PSB Bureau Subjectives</li>\n</ul>";
$cleanedSubjectives = cleanTextAreaRichTextValue($data["YQP_SUBJECTIVES_GUARANTEE"], "YES");
return $cleanedSubjectives;*/