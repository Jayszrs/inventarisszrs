<?php

declare(strict_types=1);

const APP_NAME = 'Fazma Stone Inventory';
const DATA_DIR = __DIR__ . '/data';
const BARANG_FILE = DATA_DIR . '/barang.json';
const USERS_FILE = DATA_DIR . '/users.json';
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'inventaris_fazma';
const DB_USER = 'root';
const DB_PASS = '';

session_name('fazma_inventory_session');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
