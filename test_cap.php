<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['php' => phpversion(), 'extensions' => get_loaded_extensions()]);
