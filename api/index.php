<?php
// api/index.php - Vercel Serverless Function Entry Point for SIMRS Siloam Peduli

// Set current working directory ke project root
chdir(dirname(__DIR__));

// Forward request ke front controller utama
require_once dirname(__DIR__) . '/index.php';

