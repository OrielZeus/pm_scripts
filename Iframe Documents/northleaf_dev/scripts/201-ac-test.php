<?php
/**
 * Crear usuarios en lote a partir de un array de correos electrónicos.
 * - Compatible con Script Task de ProcessMaker 4 (PHP 7.3+).
 * - Usa únicamente el SDK oficial disponible en $api.
 * - Si el usuario ya existe (por email), se omite su creación.
 * - A los usuarios creados se les asigna una contraseña temporal y
 *   se les fuerza a cambiarla en su primer inicio de sesión.
 *
 * ENTRADA OPCIONAL:
 *   $data['emails'] = ['ana.lopez@acme.com', 'juan.perez@acme.com', ...];
 *
 * SALIDA (return):
 *   [
 *     'summary' => [...],
 *     'details' => [
 *        'created' => [...],
 *        'skipped' => [...],
 *        'errors'  => [...]
 *     ]
 *   ]
 */

use ProcessMaker\Client\Model\UsersEditable;

// Instancia del cliente de usuarios del SDK (expuesto por ProcessMaker)
$apiUsers = $api->users();

// 1) Fuente de correos: si viene en $data['emails'] lo usamos; si no, ejemplo.
$emails = isset($data['emails']) && is_array($data['emails'])
  ? $data['emails']
  : [
      'adrian.jetter@northleafcapital.com',
'alex.jackson@northleafcapital.com',
'alexandra.zmudka@northleafcapital.com',
'andrea.dinisio@northleafcapital.com',
'anuja.weerasinghe@northleafcapital.com',
'brett.lauber@northleafcapital.com',
'caitlin.johnson@northleafcapital.com',
'chris.rigobon@northleafcapital.com',
'daniel.savage@northleafcapital.com',
'david.ross@northleafcapital.com',
'Dimitrios.Siomos@northleafcapital.com',
'edgar.haryanto@northleafcapital.com',
'elena.palasmith@northleafcapital.com',
'erica.yoon@northleafcapital.com',
'gavin.foo@northleafcapital.com',
'george.zakem@northleafcapital.com',
'helen.pappas@northleafcapital.com',
'isabella.crane@northleafcapital.com',
'james.knox@northleafcapital.com',
'jamie.storrow@northleafcapital.com',
'jared.waldron@northleafcapital.com',
'jessica.kennedy@northleafcapital.com',
'johnathan.mo@northleafcapital.com',
'jon.mckeown@northleafcapital.com',
'jonathan.staseff@northleafcapital.com',
'jorge.couret@northleafcapital.com',
'kaushik.ramki@northleafcapital.com',
'kelly.mckenna@northleafcapital.com',
'kevin.chan@northleafcapital.com',
'kirstie.robinson@northleafcapital.com',
'laura.dimitry@northleafcapital.com',
'leigh.brown@northleafcapital.com',
'manju.sharma@northleafcapital.com',
'mark.brophy@northleafcapital.com',
'maxim.olliver@northleafcapital.com',
'michael.gallagher@northleafcapital.com',
'michael.smerdon@northleafcapital.com',
'michelle.davies@northleafcapital.com',
'mike.moscaritolo@northleafcapital.com',
'milana.shur@northleafcapital.com',
'nadine.loehr@northleafcapital.com',
'ntina.tsigaridis@northleafcapital.com',
'paul.gill@northleafcapital.com',
'rebecca.wang@northleafcapital.com',
'Robert.jones@northleafcapital.com',
'sahana.sellathurai@northleafcapital.com',
'Scott.stephens@northleafcapital.com',
'sophie.liu@northleafcapital.com',
'susie.lee@northleafcapital.com',
'sylwia.ligocka@northleafcapital.com',
'thibault.jarlegant@northleafcapital.com',
'Travis.Isbister@northleafcapital.com',
'Xin.Weng@northleafcapital.com'
    ];

/**
 * Genera un username basado en la parte local del email (antes de la @).
 * - Convierte a minúsculas.
 * - Reemplaza caracteres no permitidos por '_'.
 * - Asegura que empiece por una letra (si no, antepone 'u_').
 */
function makeUsernameFromEmail($email) {
  // strtok con '@' obtiene la parte local del email
  $local = trim(strtok($email, '@'));
  // Permitimos solo letras, números, punto, guion y guion bajo
  $local = preg_replace('/[^a-z0-9._-]/', '_', $local);
  // Si no empieza por letra, anteponemos 'u_'
  if (!preg_match('/^[a-z]/', $local)) {
    $local = 'u_' . $local;
  }
  return $local;
}

/**
 * Intenta separar nombre y apellido a partir de la parte local del email.
 * - Patrones soportados: "nombre.apellido" o "nombre_apellido".
 * - Si no se puede, usa "User" como apellido por defecto.
 * - Limpia caracteres que no sean letras para generar nombres legibles.
 */
function splitNameFromEmail($email) {
  $local = strtolower(trim(strtok($email, '@')));
  if (strpos($local, '.') !== false) {
    // Caso "nombre.apellido"
    list($first, $last) = array_map('trim', explode('.', $local, 2));
  } elseif (strpos($local, '_') !== false) {
    // Caso "nombre_apellido"
    list($first, $last) = array_map('trim', explode('_', $local, 2));
  } else {
    // Si no hay separadores, todo a nombre y un apellido genérico
    $first = $local;
    $last  = 'User';
  }

  // Reemplaza cualquier cosa no alfabética por espacio y capitaliza
  $first = ucfirst(preg_replace('/[^a-z]/', ' ', $first));
  $last  = ucfirst(preg_replace('/[^a-z]/', ' ', $last));

  // Evita strings vacíos por si el reemplazo dejó cadenas vacías
  $first = trim($first) ?: 'User';
  $last  = trim($last)  ?: 'Account';

  return [$first, $last];
}

/**
 * Genera una contraseña temporal "fuerte" (8+ chars) mezclando:
 * - Prefijo 'Pm!' + 8 caracteres hex aleatorios + sufijo 'A1'
 *   (para cumplir con políticas típicas de mayúsculas/dígitos/símbolos).
 */
function tempPassword() {
  // 8 caracteres hex (4 bytes aleatorios) => entropía razonable
  $base = bin2hex(random_bytes(4));
  return 'Pm!' . $base . 'A1';
}

/**
 * Verifica si ya existe un usuario con EXACTAMENTE ese email.
 * - Usa getUsers con filtro por email (contiene) y luego comprueba igualdad exacta.
 * - Devuelve el ID si existe; null si no existe.
 */
function userExistsByEmail($apiUsers, $email) {
  // getUsers($status=null, $filter=null, $order_by='id', $order_direction='asc', $per_page=10, $include='')
  $result = $apiUsers->getUsers(null, $email, 'id', 'asc', 50, '');
  foreach ($result->getData() as $u) {
    if (strtolower($u->getEmail()) === strtolower($email)) {
      return $u->getId();
    }
  }
  return null;
}

// Estructura de reporte: creados, omitidos y errores
$report = [
  'created' => [],
  'skipped' => [],
  'errors'  => []
];

// 2) Recorremos cada email y procesamos creación/omisión
foreach ($emails as $email) {
  // Limpia espacios por si el origen tiene saltos o espacios extra
  $email = trim($email);

  // Valida formato de email antes de intentar crear
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $report['errors'][] = ['email' => $email, 'error' => 'Email inválido'];
    continue; // pasa al siguiente
  }

  // Evita duplicados: si ya existe, lo marcamos como omitido
  $existingId = userExistsByEmail($apiUsers, $email);
  if ($existingId) {
    $report['skipped'][] = ['email' => $email, 'reason' => 'Ya existía', 'id' => $existingId];
    continue;
  }

  // Deriva nombres y username a partir del email
  list($first, $last) = splitNameFromEmail($email);
  $username = makeUsernameFromEmail($email);

  // Genera una contraseña temporal segura
  $password = "Sample.123";

  // 3) Intento de creación con el SDK
  try {
    // Modelo editable de usuario del SDK
    $user = new UsersEditable();
    $user->setFirstname($first);          // Nombre
    $user->setLastname($last);            // Apellido
    $user->setUsername($username);        // Username único
    $user->setPassword($password);        // Contraseña temporal
    $user->setEmail($email);              // Email (login/account)
    $user->setStatus('ACTIVE');           // Estado inicial: ACTIVO
    $user['must_change_password'] = true;   // << Forzar cambio en primer login

    // Llamada al endpoint de creación vía SDK
    $created = $apiUsers->createUser($user);

    // Guardamos resultado exitoso para el reporte
    $report['created'][] = [
      'email'        => $email,
      'id'           => $created->getId(),
      'username'     => $username,
      // Exponemos la contraseña temporal para que puedas comunicarla
      // (el sistema forzará el cambio en el primer inicio de sesión)
      'temp_password' => $password
    ];
  } catch (\Throwable $e) {
    // Cualquier excepción (por validación, permisos, etc.) se captura aquí
    $report['errors'][] = ['email' => $email, 'error' => $e->getMessage()];
  }
}

// 4) Resumen final que puedes usar en un paso siguiente del flujo
return [
  'summary' => [
    'total_input' => count($emails),           // Total de correos recibidos
    'created'     => count($report['created']),// Cuántos se crearon
    'skipped'     => count($report['skipped']),// Cuántos ya existían
    'errors'      => count($report['errors']), // Cuántos fallaron
  ],
  'details' => $report // Detalle completo por elemento
];