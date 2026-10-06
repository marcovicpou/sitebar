<?php
http_response_code(405);
header('Allow: POST');
header('Content-Type: text/plain; charset=utf-8');
echo 'Use o painel para realizar esta operação com segurança.';
