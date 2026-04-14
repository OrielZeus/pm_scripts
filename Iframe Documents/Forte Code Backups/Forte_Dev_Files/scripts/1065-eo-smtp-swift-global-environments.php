<?php
//Return SMTP Global Environment Variables
return [
    "FORTE_SMTP_ADDRESS" => getenv("FORTE_SMTP_ADDRESS"),
    "FORTE_SMTP_USER" => getenv("FORTE_SMTP_USER"),
    "FORTE_SMTP_USER_NAME" => getenv("FORTE_SMTP_USER_NAME"),
    "FORTE_SMTP_USER_PASSWORD" => getenv("FORTE_SMTP_USER_PASSWORD")
];